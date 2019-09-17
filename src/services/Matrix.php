<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\services;

use Craft;
use craft\base\Element;
use craft\base\ElementInterface;
use craft\base\Field;
use craft\db\Query;
use craft\db\Table;
use craft\elements\db\MatrixBlockQuery;
use craft\elements\MatrixBlock;
use craft\errors\MatrixBlockTypeNotFoundException;
use craft\events\ConfigEvent;
use craft\fields\Matrix as MatrixField;
use craft\helpers\ArrayHelper;
use craft\helpers\Db;
use craft\helpers\ElementHelper;
use craft\helpers\Html;
use craft\helpers\MigrationHelper;
use craft\helpers\StringHelper;
use craft\migrations\CreateMatrixContentTable;
use craft\models\FieldLayout;
use craft\models\FieldLayoutTab;
use craft\models\MatrixBlockType;
use craft\models\Site;
use craft\records\MatrixBlockType as MatrixBlockTypeRecord;
use craft\web\assets\matrix\MatrixAsset;
use craft\web\View;
use yii\base\Component;
use yii\base\Exception;

/**
 * The Matrix service provides APIs for managing Matrix fields.
 * An instance of the Matrix service is globally accessible in Craft via [[\craft\base\ApplicationTrait::getMatrix()|`Craft::$app->matrix`]].
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class Matrix extends Component
{
    // Properties
    // =========================================================================

    /**
     * @var bool Whether to ignore changes to the project config.
     * @deprecated in 3.1.2. Use [[\craft\services\ProjectConfig::$muteEvents]] instead.
     */
    public $ignoreProjectConfigChanges = false;

    /**
     * @var
     */
    private $_blockTypesById;

    /**
     * @var
     */
    private $_blockTypesByFieldId;

    /**
     * @var
     */
    private $_fetchedAllBlockTypesForFieldId;

    /**
     * @var
     */
    private $_blockTypeRecordsById;

    /**
     * @var string[]
     */
    private $_uniqueBlockTypeAndFieldHandles = [];

    const CONFIG_BLOCKTYPE_KEY = 'matrixBlockTypes';


    // Public Methods
    // =========================================================================

    /**
     * Returns the block types for a given Matrix field.
     *
     * @param int $fieldId The Matrix field ID.
     * @return MatrixBlockType[] An array of block types.
     */
    public function getBlockTypesByFieldId(int $fieldId): array
    {
        if (!empty($this->_fetchedAllBlockTypesForFieldId[$fieldId])) {
            return $this->_blockTypesByFieldId[$fieldId];
        }

        $this->_blockTypesByFieldId[$fieldId] = [];

        $results = $this->_createBlockTypeQuery()
            ->where(['bt.fieldId' => $fieldId])
            ->all();

        foreach ($results as $result) {
            $blockType = new MatrixBlockType($result);
            $this->_blockTypesById[$blockType->id] = $blockType;
            $this->_blockTypesByFieldId[$fieldId][] = $blockType;
        }

        $this->_fetchedAllBlockTypesForFieldId[$fieldId] = true;

        return $this->_blockTypesByFieldId[$fieldId];
    }

    /**
     * Returns all the block types.
     *
     * @return MatrixBlockType[] An array of block types.
     * @since 3.3.0
     */
    public function getAllBlockTypes(): array
    {
        $results = $this->_createBlockTypeQuery()
            ->innerJoin(Table::FIELDS . ' f', '[[f.id]] = [[bt.fieldId]]')
            ->where(['f.type' => MatrixField::class])
            ->all();

        foreach ($results as $key => $result) {
            $results[$key] = new MatrixBlockType($result);
        }

        return $results;
    }

    /**
     * Returns a block type by its ID.
     *
     * @param int $blockTypeId The block type ID.
     * @return MatrixBlockType|null The block type, or `null` if it didn’t exist.
     */
    public function getBlockTypeById(int $blockTypeId)
    {
        if ($this->_blockTypesById !== null && array_key_exists($blockTypeId, $this->_blockTypesById)) {
            return $this->_blockTypesById[$blockTypeId];
        }

        $result = $this->_createBlockTypeQuery()
            ->where(['bt.id' => $blockTypeId])
            ->one();

        return $this->_blockTypesById[$blockTypeId] = $result ? new MatrixBlockType($result) : null;
    }

    /**
     * Validates a block type.
     *
     * If the block type doesn’t validate, any validation errors will be stored on the block type.
     *
     * @param MatrixBlockType $blockType The block type.
     * @param bool $validateUniques Whether the Name and Handle attributes should be validated to
     * ensure they’re unique. Defaults to `true`.
     * @return bool Whether the block type validated.
     */
    public function validateBlockType(MatrixBlockType $blockType, bool $validateUniques = true): bool
    {
        $validates = true;

        $blockTypeRecord = $this->_getBlockTypeRecord($blockType);

        $blockTypeRecord->fieldId = $blockType->fieldId;
        $blockTypeRecord->name = $blockType->name;
        $blockTypeRecord->handle = $blockType->handle;

        $blockTypeRecord->validateUniques = $validateUniques;

        if (!$blockTypeRecord->validate()) {
            $validates = false;
            $blockType->addErrors($blockTypeRecord->getErrors());
        }

        $blockTypeRecord->validateUniques = true;

        // Can't validate multiple new rows at once so we'll need to give these temporary context to avoid false unique
        // handle validation errors, and just validate those manually. Also apply the future fieldColumnPrefix so that
        // field handle validation takes its length into account.
        $contentService = Craft::$app->getContent();
        $originalFieldContext = $contentService->fieldContext;
        $originalFieldColumnPrefix = $contentService->fieldColumnPrefix;

        $contentService->fieldContext = StringHelper::randomString(10);
        $contentService->fieldColumnPrefix = 'field_' . $blockType->handle . '_';

        foreach ($blockType->getFields() as $field) {
            /** @var Field $field */
            // Hack to allow blank field names
            if (!$field->name) {
                $field->name = '__blank__';
            }

            $field->validate();

            // Make sure the block type handle + field handle combo is unique for the whole field. This prevents us from
            // worrying about content column conflicts like "a" + "b_c" == "a_b" + "c".
            if ($blockType->handle && $field->handle) {
                $blockTypeAndFieldHandle = $blockType->handle . '_' . $field->handle;

                if (in_array($blockTypeAndFieldHandle, $this->_uniqueBlockTypeAndFieldHandles, true)) {
                    // This error *might* not be entirely accurate, but it's such an edge case that it's probably better
                    // for the error to be worded for the common problem (two duplicate handles within the same block
                    // type).
                    $error = Craft::t('app', '{attribute} "{value}" has already been taken.',
                        [
                            'attribute' => Craft::t('app', 'Handle'),
                            'value' => $field->handle
                        ]);

                    $field->addError('handle', $error);
                } else {
                    $this->_uniqueBlockTypeAndFieldHandles[] = $blockTypeAndFieldHandle;
                }
            }

            if ($field->hasErrors()) {
                $blockType->hasFieldErrors = true;
                $validates = false;
            }
        }

        $contentService->fieldContext = $originalFieldContext;
        $contentService->fieldColumnPrefix = $originalFieldColumnPrefix;

        return $validates;
    }

    /**
     * Saves a block type.
     *
     * @param MatrixBlockType $blockType The block type to be saved.
     * @param bool $runValidation Whether the block type should be validated before being saved.
     * Defaults to `true`.
     * @return bool
     * @throws Exception if an error occurs when saving the block type
     * @throws \Throwable if reasons
     */
    public function saveBlockType(MatrixBlockType $blockType, bool $runValidation = true): bool
    {
        if ($runValidation && !$blockType->validate()) {
            return false;
        }

        $fieldsService = Craft::$app->getFields();

        /** @var Field $parentField */
        $parentField = $fieldsService->getFieldById($blockType->fieldId);
        $isNewBlockType = $blockType->getIsNew();
        $projectConfig = Craft::$app->getProjectConfig();

        $configData = [
            'field' => $parentField->uid,
            'name' => $blockType->name,
            'handle' => $blockType->handle,
            'sortOrder' => (int)$blockType->sortOrder,
        ];

        // Now, take care of the field layout for this block type
        // -------------------------------------------------------------
        $fieldLayoutFields = [];
        $sortOrder = 0;

        $configData['fields'] = [];

        foreach ($blockType->getFields() as $field) {
            /** @var Field $field */
            $configData['fields'][$field->uid] = $fieldsService->createFieldConfig($field);

            $field->sortOrder = ++$sortOrder;
            $fieldLayoutFields[] = $field;
        }

        $fieldLayoutTab = new FieldLayoutTab();
        $fieldLayoutTab->name = 'Content';
        $fieldLayoutTab->sortOrder = 1;
        $fieldLayoutTab->setFields($fieldLayoutFields);

        $fieldLayout = $blockType->getFieldLayout();

        if ($fieldLayout->uid) {
            $layoutUid = $fieldLayout->uid;
        } else {
            $layoutUid = StringHelper::UUID();
            $fieldLayout->uid = $layoutUid;
        }

        $fieldLayout->setTabs([$fieldLayoutTab]);
        $fieldLayout->setFields($fieldLayoutFields);

        $fieldLayoutConfig = $fieldLayout->getConfig();

        $configData['fieldLayouts'] = [
            $layoutUid => $fieldLayoutConfig
        ];

        $configPath = self::CONFIG_BLOCKTYPE_KEY . '.' . $blockType->uid;
        $projectConfig->set($configPath, $configData);

        if ($isNewBlockType) {
            $blockType->id = Db::idByUid(Table::MATRIXBLOCKTYPES, $blockType->uid);
        }

        return true;
    }

    /**
     * Handle block type change
     *
     * @param ConfigEvent $event
     */
    public function handleChangedBlockType(ConfigEvent $event)
    {
        if ($this->ignoreProjectConfigChanges) {
            return;
        }

        $blockTypeUid = $event->tokenMatches[0];
        $data = $event->newValue;
        $previousData = $event->oldValue;

        // Make sure the field has been synced
        $fieldId = Db::idByUid(Table::FIELDS, $data['field']);
        if ($fieldId === null) {
            Craft::$app->getProjectConfig()->defer($event, [$this, __FUNCTION__]);
            return;
        }

        $fieldsService = Craft::$app->getFields();
        $contentService = Craft::$app->getContent();

        $transaction = Craft::$app->getDb()->beginTransaction();

        try {
            // Store the current contexts.
            $originalContentTable = $contentService->contentTable;
            $originalFieldContext = $contentService->fieldContext;
            $originalFieldColumnPrefix = $contentService->fieldColumnPrefix;
            $originalOldFieldColumnPrefix = $fieldsService->oldFieldColumnPrefix;

            // Get the block type record
            $blockTypeRecord = $this->_getBlockTypeRecord($blockTypeUid);

            // Set the basic info on the new block type record
            $blockTypeRecord->fieldId = $fieldId;
            $blockTypeRecord->name = $data['name'];
            $blockTypeRecord->handle = $data['handle'];
            $blockTypeRecord->sortOrder = $data['sortOrder'];
            $blockTypeRecord->uid = $blockTypeUid;

            // Make sure that alterations, if any, occur in the correct context.
            $contentService->fieldContext = 'matrixBlockType:' . $blockTypeUid;
            $contentService->fieldColumnPrefix = 'field_' . $blockTypeRecord->handle . '_';
            /** @var MatrixField $matrixField */
            $matrixField = $fieldsService->getFieldById($blockTypeRecord->fieldId);
            $contentService->contentTable = $matrixField->contentTable;
            $fieldsService->oldFieldColumnPrefix = 'field_' . ($blockTypeRecord->getOldAttribute('handle') ?? $data['handle']) . '_';

            $oldFields = $previousData['fields'] ?? [];
            $newFields = $data['fields'] ?? [];

            // Remove fields that this block type no longer has
            foreach ($oldFields as $fieldUid => $fieldData) {
                if (!array_key_exists($fieldUid, $newFields)) {
                    $fieldsService->applyFieldDelete($fieldUid);
                }
            }

            // (Re)save all the fields that now exist for this block.
            foreach ($newFields as $fieldUid => $fieldData) {
                $fieldsService->applyFieldSave($fieldUid, $fieldData, 'matrixBlockType:' . $blockTypeUid);
            }

            // Refresh the schema cache
            Craft::$app->getDb()->getSchema()->refresh();

            $contentService->fieldContext = $originalFieldContext;
            $contentService->fieldColumnPrefix = $originalFieldColumnPrefix;
            $contentService->contentTable = $originalContentTable;
            $fieldsService->oldFieldColumnPrefix = $originalOldFieldColumnPrefix;

            if (!empty($data['fieldLayouts'])) {
                // Save the field layout
                $layout = FieldLayout::createFromConfig(reset($data['fieldLayouts']));
                $layout->id = $blockTypeRecord->fieldLayoutId;
                $layout->type = MatrixBlock::class;
                $layout->uid = key($data['fieldLayouts']);
                $fieldsService->saveLayout($layout);
                $blockTypeRecord->fieldLayoutId = $layout->id;
            } else if ($blockTypeRecord->fieldLayoutId) {
                // Delete the field layout
                $fieldsService->deleteLayoutById($blockTypeRecord->fieldLayoutId);
                $blockTypeRecord->fieldLayoutId = null;
            }

            // Save it
            $blockTypeRecord->save(false);
            $transaction->commit();
        } catch (\Throwable $e) {
            $transaction->rollBack();
            throw $e;
        }

        // Clear caches
        unset(
            $this->_blockTypesById[$blockTypeRecord->id],
            $this->_blockTypesByFieldId[$blockTypeRecord->fieldId]
        );
        $this->_fetchedAllBlockTypesForFieldId[$blockTypeRecord->fieldId] = false;
    }

    /**
     * Deletes a block type.
     *
     * @param MatrixBlockType $blockType The block type.
     * @return bool Whether the block type was deleted successfully.
     */
    public function deleteBlockType(MatrixBlockType $blockType): bool
    {
        Craft::$app->getProjectConfig()->remove(self::CONFIG_BLOCKTYPE_KEY . '.' . $blockType->uid);
        return true;
    }

    /**
     * Handle block type change
     *
     * @param ConfigEvent $event
     * @throws \Throwable if reasons
     */
    public function handleDeletedBlockType(ConfigEvent $event)
    {
        if ($this->ignoreProjectConfigChanges) {
            return;
        }

        $blockTypeUid = $event->tokenMatches[0];
        $blockTypeRecord = $this->_getBlockTypeRecord($blockTypeUid);

        if (!$blockTypeRecord->id) {
            return;
        }

        $db = Craft::$app->getDb();
        $transaction = $db->beginTransaction();

        try {
            $blockType = $this->getBlockTypeById($blockTypeRecord->id);

            if (!$blockType) {
                return;
            }

            // First delete the blocks of this type
            foreach (Craft::$app->getSites()->getAllSiteIds() as $siteId) {
                $blocks = MatrixBlock::find()
                    ->siteId($siteId)
                    ->typeId($blockType->id)
                    ->all();

                foreach ($blocks as $block) {
                    Craft::$app->getElements()->deleteElement($block);
                }
            }

            // Set the new contentTable
            $contentService = Craft::$app->getContent();
            $fieldsService = Craft::$app->getFields();
            $originalContentTable = $contentService->contentTable;

            /** @var MatrixField $matrixField */
            $matrixField = $fieldsService->getFieldById($blockType->fieldId);
            $contentService->contentTable = $matrixField->contentTable;

            // Set the new fieldColumnPrefix
            $originalFieldColumnPrefix = Craft::$app->getContent()->fieldColumnPrefix;
            Craft::$app->getContent()->fieldColumnPrefix = 'field_' . $blockType->handle . '_';

            // Now delete the block type fields
            foreach ($blockType->getFields() as $field) {
                Craft::$app->getFields()->deleteField($field);
            }

            // Restore the contentTable and the fieldColumnPrefix to original values.
            Craft::$app->getContent()->fieldColumnPrefix = $originalFieldColumnPrefix;
            $contentService->contentTable = $originalContentTable;

            // Delete the field layout
            $fieldLayoutId = (new Query())
                ->select(['fieldLayoutId'])
                ->from([Table::MATRIXBLOCKTYPES])
                ->where(['id' => $blockTypeRecord->id])
                ->scalar();

            // Delete the field layout
            Craft::$app->getFields()->deleteLayoutById($fieldLayoutId);

            // Finally delete the actual block type
            $db->createCommand()
                ->delete(Table::MATRIXBLOCKTYPES, ['id' => $blockTypeRecord->id])
                ->execute();

            $transaction->commit();
        } catch (\Throwable $e) {
            $transaction->rollBack();
            throw $e;
        }

        // Clear caches
        unset(
            $this->_blockTypesById[$blockTypeRecord->id],
            $this->_blockTypesByFieldId[$blockTypeRecord->fieldId],
            $this->_blockTypeRecordsById[$blockTypeRecord->id]
        );
        $this->_fetchedAllBlockTypesForFieldId[$blockTypeRecord->fieldId] = false;
    }

    /**
     * Validates a Matrix field's settings.
     *
     * If the settings don’t validate, any validation errors will be stored on the settings model.
     *
     * @param MatrixField $matrixField The Matrix field
     * @return bool Whether the settings validated.
     */
    public function validateFieldSettings(MatrixField $matrixField): bool
    {
        $validates = true;

        $this->_uniqueBlockTypeAndFieldHandles = [];

        $uniqueAttributes = ['name', 'handle'];
        $uniqueAttributeValues = [];

        foreach ($matrixField->getBlockTypes() as $blockType) {
            if (!$this->validateBlockType($blockType, false)) {
                // Don't break out of the loop because we still want to get validation errors for the remaining block
                // types.
                $validates = false;
            }

            // Do our own unique name/handle validation, since the DB-based validation can't be trusted when saving
            // multiple records at once
            foreach ($uniqueAttributes as $attribute) {
                $value = $blockType->$attribute;

                if ($value && (!isset($uniqueAttributeValues[$attribute]) || !in_array($value, $uniqueAttributeValues[$attribute], true))) {
                    $uniqueAttributeValues[$attribute][] = $value;
                } else {
                    $blockType->addError($attribute, Craft::t('app', '{attribute} "{value}" has already been taken.',
                        [
                            'attribute' => $blockType->getAttributeLabel($attribute),
                            'value' => Html::encode($value)
                        ]));

                    $validates = false;
                }
            }
        }

        return $validates;
    }

    /**
     * Saves a Matrix field's settings.
     *
     * @param MatrixField $matrixField The Matrix field
     * @param bool $validate Whether the settings should be validated before being saved.
     * @return bool Whether the settings saved successfully.
     * @throws \Throwable if reasons
     */
    public function saveSettings(MatrixField $matrixField, bool $validate = true): bool
    {
        if (!$matrixField->contentTable) {
            throw new Exception('Unable to save a Matrix field’s settings without knowing its content table.');
        }

        if ($validate && !$this->validateFieldSettings($matrixField)) {
            return false;
        }

        $db = Craft::$app->getDb();
        $transaction = $db->beginTransaction();
        try {
            // Do we need to create/rename the content table?
            if (!$db->tableExists($matrixField->contentTable)) {
                $oldContentTable = $matrixField->oldSettings['contentTable'] ?? null;
                if ($oldContentTable && $db->tableExists($oldContentTable)) {
                    MigrationHelper::renameTable($oldContentTable, $matrixField->contentTable);
                } else {
                    $this->_createContentTable($matrixField->contentTable);
                }
            }

            if (!Craft::$app->getProjectConfig()->areChangesPending(self::CONFIG_BLOCKTYPE_KEY)) {
                // Delete the old block types first, in case there's a handle conflict with one of the new ones
                $oldBlockTypes = $this->getBlockTypesByFieldId($matrixField->id);
                $oldBlockTypesById = [];

                foreach ($oldBlockTypes as $blockType) {
                    $oldBlockTypesById[$blockType->id] = $blockType;
                }

                foreach ($matrixField->getBlockTypes() as $blockType) {
                    if (!$blockType->getIsNew()) {
                        unset($oldBlockTypesById[$blockType->id]);
                    }
                }

                foreach ($oldBlockTypesById as $blockType) {
                    $this->deleteBlockType($blockType);
                }

                // Save the new ones
                $sortOrder = 0;

                $originalContentTable = Craft::$app->getContent()->contentTable;
                Craft::$app->getContent()->contentTable = $matrixField->contentTable;

                foreach ($matrixField->getBlockTypes() as $blockType) {
                    $sortOrder++;
                    $blockType->fieldId = $matrixField->id;
                    $blockType->sortOrder = $sortOrder;
                    $this->saveBlockType($blockType, false);
                }

                Craft::$app->getContent()->contentTable = $originalContentTable;
            }

            $transaction->commit();
        } catch (\Throwable $e) {
            $transaction->rollBack();
            throw $e;
        }

        // Clear caches
        unset(
            $this->_blockTypesByFieldId[$matrixField->id],
            $this->_fetchedAllBlockTypesForFieldId[$matrixField->id]
        );

        return true;
    }

    /**
     * Deletes a Matrix field.
     *
     * @param MatrixField $matrixField The Matrix field.
     * @return bool Whether the field was deleted successfully.
     * @throws \Throwable
     */
    public function deleteMatrixField(MatrixField $matrixField): bool
    {
        // Clear the schema cache
        $db = Craft::$app->getDb();
        $db->getSchema()->refresh();

        $transaction = $db->beginTransaction();
        try {
            $originalContentTable = Craft::$app->getContent()->contentTable;
            Craft::$app->getContent()->contentTable = $matrixField->contentTable;

            // Delete the block types
            $blockTypes = $this->getBlockTypesByFieldId($matrixField->id);

            foreach ($blockTypes as $blockType) {
                $this->deleteBlockType($blockType);
            }

            // Drop the content table
            $db->createCommand()
                ->dropTable($matrixField->contentTable)
                ->execute();

            Craft::$app->getContent()->contentTable = $originalContentTable;

            $transaction->commit();

            return true;
        } catch (\Throwable $e) {
            $transaction->rollBack();

            throw $e;
        }
    }

    /**
     * Returns the content table name for a given Matrix field.
     *
     * @param MatrixField $matrixField The Matrix field.
     * @return string|false The table name, or `false` if $useOldHandle was set to `true` and there was no old handle.
     * @deprecated in 3.0.23. Use [[MatrixField::contentTableName]] instead.
     */
    public function getContentTableName(MatrixField $matrixField)
    {
        return $matrixField->contentTable;
    }

    /**
     * Defines a new Matrix content table name.
     *
     * @param MatrixField $field
     * @return string
     * @since 3.0.23
     */
    public function defineContentTableName(MatrixField $field): string
    {
        $baseName = 'matrixcontent_' . strtolower($field->handle);
        $db = Craft::$app->getDb();
        $i = -1;
        do {
            $i++;
            $name = '{{%' . $baseName . ($i !== 0 ? '_' . $i : '') . '}}';
        } while ($name !== $field->contentTable && $db->tableExists($name));
        return $name;
    }

    /**
     * Returns a block by its ID.
     *
     * @param int $blockId The Matrix block’s ID.
     * @param int|null $siteId The site ID to return. Defaults to the current site.
     * @return MatrixBlock|null The Matrix block, or `null` if it didn’t exist.
     */
    public function getBlockById(int $blockId, int $siteId = null)
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return Craft::$app->getElements()->getElementById($blockId, MatrixBlock::class, $siteId);
    }

    /**
     * Saves a Matrix field.
     *
     * @param MatrixField $field The Matrix field
     * @param ElementInterface $owner The element the field is associated with
     * @throws \Throwable if reasons
     */
    public function saveField(MatrixField $field, ElementInterface $owner)
    {
        /** @var Element $owner */
        $elementsService = Craft::$app->getElements();
        /** @var MatrixBlockQuery $query */
        $query = $owner->getFieldValue($field->handle);
        /** @var MatrixBlock[] $blocks */
        if (($blocks = $query->getCachedResult()) === null) {
            $blocksQuery = clone $query;
            $blocks = $blocksQuery->anyStatus()->all();
        }
        $blockIds = [];
        $collapsedBlockIds = [];

        $transaction = Craft::$app->getDb()->beginTransaction();
        try {
            foreach ($blocks as $block) {
                $block->ownerId = $owner->id;
                $elementsService->saveElement($block, false);
                $blockIds[] = $block->id;

                // Tell the browser to collapse this block?
                if ($block->collapsed) {
                    $collapsedBlockIds[] = $block->id;
                }
            }

            // Delete any blocks that shouldn't be there anymore
            $this->_deleteOtherBlocks($field, $owner, $blockIds);

            // Should we duplicate the blocks to other sites?
            if (
                $field->propagationMethod !== MatrixField::PROPAGATION_METHOD_ALL &&
                ($owner->propagateAll || !empty($owner->newSiteIds))
            ) {
                // Find the owner's site IDs that *aren't* supported by this site's Matrix blocks
                $ownerSiteIds = ArrayHelper::getColumn(ElementHelper::supportedSitesForElement($owner), 'siteId');
                $fieldSiteIds = $this->getSupportedSiteIdsForField($field, $owner);
                $otherSiteIds = array_diff($ownerSiteIds, $fieldSiteIds);

                // If propagateAll isn't set, only deal with sites that the element was just propagated to for the first time
                if (!$owner->propagateAll) {
                    $otherSiteIds = array_intersect($otherSiteIds, $owner->newSiteIds);
                }

                if (!empty($otherSiteIds)) {
                    // Get the original element and duplicated element for each of those sites
                    /** @var Element[] $otherTargets */
                    $otherTargets = $owner::find()
                        ->drafts($owner->getIsDraft())
                        ->revisions($owner->getIsRevision())
                        ->id($owner->id)
                        ->siteId($otherSiteIds)
                        ->anyStatus()
                        ->all();

                    // Duplicate Matrix blocks, ensuring we don't process the same blocks more than once
                    $handledSiteIds = [];

                    $cachedQuery = clone $query;
                    $cachedQuery->anyStatus();
                    $cachedQuery->setCachedResult($blocks);
                    $owner->setFieldValue($field->handle, $cachedQuery);

                    foreach ($otherTargets as $otherTarget) {
                        // Make sure we haven't already duplicated blocks for this site, via propagation from another site
                        if (isset($handledSiteIds[$otherTarget->siteId])) {
                            continue;
                        }

                        $this->duplicateBlocks($field, $owner, $otherTarget);

                        // Make sure we don't duplicate blocks for any of the sites that were just propagated to
                        $sourceSupportedSiteIds = $this->getSupportedSiteIdsForField($field, $otherTarget);
                        $handledSiteIds = array_merge($handledSiteIds, array_flip($sourceSupportedSiteIds));
                    }

                    $owner->setFieldValue($field->handle, $query);
                }
            }

            $transaction->commit();
        } catch (\Throwable $e) {
            $transaction->rollBack();
            throw $e;
        }

        // Tell the browser to collapse any new block IDs
        if (!Craft::$app->getRequest()->getIsConsoleRequest() && !Craft::$app->getResponse()->isSent && !empty($collapsedBlockIds)) {
            Craft::$app->getSession()->addAssetBundleFlash(MatrixAsset::class);

            foreach ($collapsedBlockIds as $blockId) {
                Craft::$app->getSession()->addJsFlash('Craft.MatrixInput.rememberCollapsedBlockId(' . $blockId . ');', View::POS_END);
            }
        }
    }

    /**
     * Duplicates Matrix blocks from one owner element to another.
     *
     * @param MatrixField $field The Matrix field to duplicate blocks for
     * @param ElementInterface $source The source element blocks should be duplicated from
     * @param ElementInterface $target The target element blocks should be duplicated to
     * @param bool $checkOtherSites Whether to duplicate blocks for the source element's other supported sites
     * @throws \Throwable if reasons
     */
    public function duplicateBlocks(MatrixField $field, ElementInterface $source, ElementInterface $target, bool $checkOtherSites = false)
    {
        /** @var Element $source */
        /** @var Element $target */
        $elementsService = Craft::$app->getElements();
        /** @var MatrixBlockQuery $query */
        $query = $source->getFieldValue($field->handle);
        /** @var MatrixBlock[] $blocks */
        if (($blocks = $query->getCachedResult()) === null) {
            $blocksQuery = clone $query;
            $blocks = $blocksQuery->anyStatus()->all();
        }
        $newBlockIds = [];

        $transaction = Craft::$app->getDb()->beginTransaction();
        try {
            foreach ($blocks as $block) {
                /** @var MatrixBlock $newBlock */
                $newBlock = $elementsService->duplicateElement($block, [
                    'ownerId' => $target->id,
                    'owner' => $target,
                    'siteId' => $target->siteId,
                    'propagating' => false,
                ]);
                $newBlockIds[] = $newBlock->id;
            }

            // Delete any blocks that shouldn't be there anymore
            $this->_deleteOtherBlocks($field, $target, $newBlockIds);

            $transaction->commit();
        } catch (\Throwable $e) {
            $transaction->rollBack();
            throw $e;
        }

        // Duplicate blocks for other sites as well?
        if ($checkOtherSites && $field->propagationMethod !== MatrixField::PROPAGATION_METHOD_ALL) {
            // Find the target's site IDs that *aren't* supported by this site's Matrix blocks
            $targetSiteIds = ArrayHelper::getColumn(ElementHelper::supportedSitesForElement($target), 'siteId');
            $fieldSiteIds = $this->getSupportedSiteIdsForField($field, $target);
            $otherSiteIds = array_diff($targetSiteIds, $fieldSiteIds);

            if (!empty($otherSiteIds)) {
                // Get the original element and duplicated element for each of those sites
                /** @var Element[] $otherSources */
                $otherSources = $target::find()
                    ->drafts($source->getIsDraft())
                    ->revisions($source->getIsRevision())
                    ->id($source->id)
                    ->siteId($otherSiteIds)
                    ->anyStatus()
                    ->all();
                /** @var Element[] $otherTargets */
                $otherTargets = $target::find()
                    ->drafts($target->getIsDraft())
                    ->revisions($target->getIsRevision())
                    ->id($target->id)
                    ->siteId($otherSiteIds)
                    ->anyStatus()
                    ->indexBy('siteId')
                    ->all();

                // Duplicate Matrix blocks, ensuring we don't process the same blocks more than once
                $handledSiteIds = [];

                foreach ($otherSources as $otherSource) {
                    // Make sure the target actually exists for this site
                    if (!isset($otherTargets[$otherSource->siteId])) {
                        continue;
                    }

                    // Make sure we haven't already duplicated blocks for this site, via propagation from another site
                    if (isset($handledSiteIds[$otherSource->siteId])) {
                        continue;
                    }

                    $this->duplicateBlocks($field, $otherSource, $otherTargets[$otherSource->siteId]);

                    // Make sure we don't duplicate blocks for any of the sites that were just propagated to
                    $sourceSupportedSiteIds = $this->getSupportedSiteIdsForField($field, $otherSource);
                    $handledSiteIds = array_merge($handledSiteIds, array_flip($sourceSupportedSiteIds));
                }
            }
        }
    }

    /**
     * Returns the site IDs that are supported by Matrix blocks for the given Matrix field and owner element.
     *
     * @param MatrixField $field
     * @param ElementInterface $owner
     * @return int[]
     */
    public function getSupportedSiteIdsForField(MatrixField $field, ElementInterface $owner): array
    {
        /** @var Element $owner */
        /** @var Site[] $allSites */
        $allSites = ArrayHelper::index(Craft::$app->getSites()->getAllSites(), 'id');
        $ownerSiteIds = ArrayHelper::getColumn(ElementHelper::supportedSitesForElement($owner), 'siteId');
        $siteIds = [];

        foreach ($ownerSiteIds as $siteId) {
            switch ($field->propagationMethod) {
                case MatrixField::PROPAGATION_METHOD_NONE:
                    $include = $siteId == $owner->siteId;
                    break;
                case MatrixField::PROPAGATION_METHOD_SITE_GROUP:
                    $include = $allSites[$siteId]->groupId == $allSites[$owner->siteId]->groupId;
                    break;
                case MatrixField::PROPAGATION_METHOD_LANGUAGE:
                    $include = $allSites[$siteId]->language == $allSites[$owner->siteId]->language;
                    break;
                default:
                    $include = true;
                    break;
            }

            if ($include) {
                $siteIds[] = $siteId;
            }
        }

        return $siteIds;
    }

    // Private Methods
    // =========================================================================

    /**
     * Returns a Query object prepped for retrieving block types.
     *
     * @return Query
     */
    private function _createBlockTypeQuery(): Query
    {
        return (new Query())
            ->select([
                'bt.id',
                'bt.fieldId',
                'bt.fieldLayoutId',
                'bt.name',
                'bt.handle',
                'bt.sortOrder',
                'bt.uid'
            ])
            ->from(['bt' => Table::MATRIXBLOCKTYPES])
            ->orderBy(['bt.sortOrder' => SORT_ASC]);
    }

    /**
     * Returns a block type record by its model or UID or creates a new one.
     *
     * @param MatrixBlockType|string $blockType
     * @return MatrixBlockTypeRecord
     * @throws MatrixBlockTypeNotFoundException if $blockType->id is invalid
     */
    private function _getBlockTypeRecord($blockType): MatrixBlockTypeRecord
    {
        if (is_string($blockType)) {
            $blockTypeRecord = MatrixBlockTypeRecord::findOne(['uid' => $blockType]) ?? new MatrixBlockTypeRecord();

            if (!$blockTypeRecord->getIsNewRecord()) {
                $this->_blockTypeRecordsById[$blockTypeRecord->id] = $blockTypeRecord;
            }

            return $blockTypeRecord;
        }

        if ($blockType->getIsNew()) {
            return new MatrixBlockTypeRecord();
        }

        if (isset($this->_blockTypeRecordsById[$blockType->id])) {
            return $this->_blockTypeRecordsById[$blockType->id];
        }

        $blockTypeRecord = MatrixBlockTypeRecord::findOne($blockType->id);

        if ($blockTypeRecord === null) {
            throw new MatrixBlockTypeNotFoundException('Invalid block type ID: ' . $blockType->id);
        }

        return $this->_blockTypeRecordsById[$blockType->id] = $blockTypeRecord;
    }

    /**
     * Creates the content table for a Matrix field.
     *
     * @param string $tableName
     */
    private function _createContentTable(string $tableName)
    {
        $migration = new CreateMatrixContentTable([
            'tableName' => $tableName
        ]);

        ob_start();
        $migration->up();
        ob_end_clean();
    }

    /**
     * Deletes blocks from an owner element
     *
     * @param MatrixField $field The Matrix field
     * @param ElementInterface The owner element
     * @param int[] $except Block IDs that should be left alone
     */
    private function _deleteOtherBlocks(MatrixField $field, ElementInterface $owner, array $except)
    {
        /** @var Element $owner */
        $deleteBlocks = MatrixBlock::find()
            ->anyStatus()
            ->ownerId($owner->id)
            ->fieldId($field->id)
            ->siteId($owner->siteId)
            ->andWhere(['not', ['elements.id' => $except]])
            ->all();

        $elementsService = Craft::$app->getElements();

        foreach ($deleteBlocks as $deleteBlock) {
            $elementsService->deleteElement($deleteBlock);
        }
    }
}
