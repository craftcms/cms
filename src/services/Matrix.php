<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\services;

use Craft;
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
use craft\migrations\CreateMatrixContentTable;
use craft\models\FieldLayout;
use craft\models\MatrixBlockType;
use craft\models\Site;
use craft\records\MatrixBlockType as MatrixBlockTypeRecord;
use craft\validators\StringValidator;
use craft\web\assets\matrix\MatrixAsset;
use craft\web\View;
use Illuminate\Support\Collection;
use Throwable;
use yii\base\Component;
use yii\base\Exception;
use yii\base\InvalidArgumentException;

/**
 * The Matrix service provides APIs for managing Matrix fields.
 *
 * An instance of the service is available via [[\craft\base\ApplicationTrait::getMatrix()|`Craft::$app->matrix`]].
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class Matrix extends Component
{
    /**
     * @var MatrixBlockType[]|null[]
     */
    private array $_blockTypesById = [];

    /**
     * @var MatrixBlockType[][]
     */
    private array $_blockTypesByFieldId = [];

    /**
     * @var bool[]
     */
    private array $_fetchedAllBlockTypesForFieldId = [];

    /**
     * @var MatrixBlockTypeRecord[]
     */
    private array $_blockTypeRecordsById = [];

    /**
     * @var string[]
     */
    private array $_uniqueBlockTypeAndFieldHandles = [];


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
            ->innerJoin(['f' => Table::FIELDS], '[[f.id]] = [[bt.fieldId]]')
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
    public function getBlockTypeById(int $blockTypeId): ?MatrixBlockType
    {
        if (array_key_exists($blockTypeId, $this->_blockTypesById)) {
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
     * @return bool Whether the block type validated.
     */
    public function validateBlockType(MatrixBlockType $blockType): bool
    {
        $validates = true;

        $blockTypeRecord = $this->_getBlockTypeRecord($blockType);

        $blockTypeRecord->fieldId = $blockType->fieldId;
        $blockTypeRecord->name = $blockType->name;
        $blockTypeRecord->handle = $blockType->handle;

        if (!$blockTypeRecord->validate()) {
            $validates = false;
            $blockType->addErrors($blockTypeRecord->getErrors());
        }

        foreach ($blockType->getCustomFields() as $field) {
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
                    $error = Craft::t('app', '{attribute} “{value}” has already been taken.',
                        [
                            'attribute' => Craft::t('app', 'Handle'),
                            'value' => $field->handle,
                        ]);

                    $field->addError('handle', $error);
                } else {
                    $this->_uniqueBlockTypeAndFieldHandles[] = $blockTypeAndFieldHandle;
                }

                if (!$field->hasErrors('handle')) {
                    // Make sure the handle isn't too long when including the block type handle as individual fields
                    // don't account for it.
                    $maxHandleLength = Craft::$app->getDb()->getSchema()->maxObjectNameLength;
                    $maxHandleLength -= strlen(Craft::$app->getContent()->fieldColumnPrefix . '_');
                    $maxHandleLength -= strlen($blockType->handle . '_');
                    $maxHandleLength -= strlen('_' . $field->columnSuffix);

                    $validator = new StringValidator([
                        'max' => $maxHandleLength,
                    ]);

                    /** @var Field $field */
                    $validator->validateAttribute($field, 'handle');
                }
            }

            if ($field->hasErrors()) {
                $blockType->hasFieldErrors = true;
                $validates = false;
            }
        }

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
     * @throws Throwable if reasons
     */
    public function saveBlockType(MatrixBlockType $blockType, bool $runValidation = true): bool
    {
        if ($runValidation && !$blockType->validate()) {
            return false;
        }

        $isNewBlockType = $blockType->getIsNew();
        $configPath = ProjectConfig::PATH_MATRIX_BLOCK_TYPES . '.' . $blockType->uid;
        $configData = $blockType->getConfig();
        $field = $blockType->getField();

        Craft::$app->getProjectConfig()->set($configPath, $configData, "Save matrix block type “{$blockType->handle}” for parent field “{$field->handle}”");

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
    public function handleChangedBlockType(ConfigEvent $event): void
    {
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

        // Store the current contexts.
        $originalContentTable = $contentService->contentTable;
        $originalFieldContext = $contentService->fieldContext;
        $originalFieldColumnPrefix = $contentService->fieldColumnPrefix;
        $originalOldFieldColumnPrefix = $fieldsService->oldFieldColumnPrefix;

        try {
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

            // Ignore it, if the parent field is not a Matrix field.
            if ($matrixField instanceof MatrixField) {
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

                if (
                    !empty($data['fieldLayouts']) &&
                    ($layoutConfig = reset($data['fieldLayouts']))
                ) {
                    // Save the field layout
                    $layout = FieldLayout::createFromConfig($layoutConfig);
                    $layout->id = $blockTypeRecord->fieldLayoutId;
                    $layout->type = MatrixBlock::class;
                    $layout->uid = key($data['fieldLayouts']);
                    $fieldsService->saveLayout($layout, false);
                    $blockTypeRecord->fieldLayoutId = $layout->id;
                } elseif ($blockTypeRecord->fieldLayoutId) {
                    // Delete the field layout
                    $fieldsService->deleteLayoutById($blockTypeRecord->fieldLayoutId);
                    $blockTypeRecord->fieldLayoutId = null;
                }

                // Save it
                $blockTypeRecord->save(false);
            }

            $transaction->commit();
        } catch (Throwable $e) {
            $transaction->rollBack();
            throw $e;
        }

        // Restore the previous contexts.
        $contentService->fieldContext = $originalFieldContext;
        $contentService->fieldColumnPrefix = $originalFieldColumnPrefix;
        $contentService->contentTable = $originalContentTable;
        $fieldsService->oldFieldColumnPrefix = $originalOldFieldColumnPrefix;

        // Clear caches
        unset(
            $this->_blockTypesById[$blockTypeRecord->id],
            $this->_blockTypesByFieldId[$blockTypeRecord->fieldId]
        );
        $this->_fetchedAllBlockTypesForFieldId[$blockTypeRecord->fieldId] = false;

        // Invalidate Matrix block caches
        Craft::$app->getElements()->invalidateCachesForElementType(MatrixBlock::class);
    }

    /**
     * Deletes a block type.
     *
     * @param MatrixBlockType $blockType The block type.
     * @return bool Whether the block type was deleted successfully.
     */
    public function deleteBlockType(MatrixBlockType $blockType): bool
    {
        Craft::$app->getProjectConfig()->remove(ProjectConfig::PATH_MATRIX_BLOCK_TYPES . '.' . $blockType->uid, "Delete matrix block type “{$blockType->handle}” for parent field “{$blockType->getField()->handle}”");
        return true;
    }

    /**
     * Handle block type change
     *
     * @param ConfigEvent $event
     * @throws Throwable if reasons
     */
    public function handleDeletedBlockType(ConfigEvent $event): void
    {
        $blockTypeUid = $event->tokenMatches[0];
        $blockTypeRecord = $this->_getBlockTypeRecord($blockTypeUid);

        if (!$blockTypeRecord->id) {
            return;
        }

        $transaction = Craft::$app->getDb()->beginTransaction();

        try {
            $blockType = $this->getBlockTypeById($blockTypeRecord->id);

            if (!$blockType) {
                return;
            }

            // First delete the blocks of this type
            foreach (Craft::$app->getSites()->getAllSiteIds() as $siteId) {
                /** @var MatrixBlock[] $blocks */
                $blocks = MatrixBlock::find()
                    ->typeId($blockType->id)
                    ->siteId($siteId)
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

            // Ignore it, if the parent field is not a Matrix field.
            if ($matrixField instanceof MatrixField) {
                $contentService->contentTable = $matrixField->contentTable;

                // Set the new fieldColumnPrefix + oldFieldColumnPrefix
                $originalFieldColumnPrefix = $contentService->fieldColumnPrefix;
                $originalOldFieldColumnPrefix = $fieldsService->oldFieldColumnPrefix;

                $contentService->fieldColumnPrefix = "field_{$blockType->handle}_";
                $fieldsService->oldFieldColumnPrefix = "field_{$blockType->handle}_";

                // Now delete the block type fields
                foreach ($blockType->getCustomFields() as $field) {
                    $fieldsService->deleteField($field);
                }

                // Restore the contentTable and the fieldColumnPrefix to original values.
                $contentService->contentTable = $originalContentTable;
                $contentService->fieldColumnPrefix = $originalFieldColumnPrefix;
                $fieldsService->oldFieldColumnPrefix = $originalOldFieldColumnPrefix;

                // Delete the field layout
                $fieldLayoutId = (new Query())
                    ->select(['fieldLayoutId'])
                    ->from([Table::MATRIXBLOCKTYPES])
                    ->where(['id' => $blockTypeRecord->id])
                    ->scalar();

                // Delete the field layout
                if ($fieldLayoutId) {
                    $fieldsService->deleteLayoutById($fieldLayoutId);
                }

                // Finally delete the actual block type
                Db::delete(Table::MATRIXBLOCKTYPES, [
                    'id' => $blockTypeRecord->id,
                ]);
            }

            $transaction->commit();
        } catch (Throwable $e) {
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

        // Invalidate Matrix block caches
        Craft::$app->getElements()->invalidateCachesForElementType(MatrixBlock::class);
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
            if (!$this->validateBlockType($blockType)) {
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
                    $blockType->addError($attribute, Craft::t('app', '{attribute} “{value}” has already been taken.',
                        [
                            'attribute' => $blockType->getAttributeLabel($attribute),
                            'value' => Html::encode($value),
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
     * @throws Throwable if reasons
     */
    public function saveSettings(MatrixField $matrixField, bool $validate = true): bool
    {
        if (!isset($matrixField->contentTable)) {
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
                    Db::renameTable($oldContentTable, $matrixField->contentTable);
                } else {
                    $this->_createContentTable($matrixField->contentTable);
                }
            }

            // Only make block type changes if we're not in the middle of applying external changes
            if (!Craft::$app->getProjectConfig()->getIsApplyingExternalChanges()) {
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
        } catch (Throwable $e) {
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
     * @throws Throwable
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
        } catch (Throwable $e) {
            $transaction->rollBack();

            throw $e;
        }
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
        } while ($name !== ($field->contentTable ?? null) && $db->tableExists($name));
        return $name;
    }

    /**
     * Returns a block by its ID.
     *
     * @param int $blockId The Matrix block’s ID.
     * @param int|null $siteId The site ID to return. Defaults to the current site.
     * @return MatrixBlock|null The Matrix block, or `null` if it didn’t exist.
     */
    public function getBlockById(int $blockId, ?int $siteId = null): ?MatrixBlock
    {
        return Craft::$app->getElements()->getElementById($blockId, MatrixBlock::class, $siteId);
    }

    /**
     * Saves a Matrix field.
     *
     * @param MatrixField $field The Matrix field
     * @param ElementInterface $owner The element the field is associated with
     * @throws Throwable if reasons
     */
    public function saveField(MatrixField $field, ElementInterface $owner): void
    {
        $elementsService = Craft::$app->getElements();

        /** @var MatrixBlockQuery|Collection $value */
        $value = $owner->getFieldValue($field->handle);
        if ($value instanceof Collection) {
            $blocks = $value->all();
            $saveAll = true;
        } else {
            $blocks = $value->getCachedResult();
            if ($blocks !== null) {
                $saveAll = false;
            } else {
                $blocks = (clone $value)->status(null)->all();
                $saveAll = true;
            }
        }

        $blockIds = [];
        $collapsedBlockIds = [];
        $sortOrder = 0;

        $transaction = Craft::$app->getDb()->beginTransaction();
        try {
            /** @var MatrixBlock[] $blocks */
            foreach ($blocks as $block) {
                $sortOrder++;
                if ($saveAll || !$block->id || $block->dirty) {
                    $block->primaryOwnerId = $block->ownerId = $owner->id;
                    $block->sortOrder = $sortOrder;
                    $elementsService->saveElement($block, false);

                    // If this is a draft, we can shed the draft data now
                    if ($block->getIsDraft()) {
                        $canonicalBlockId = $block->getCanonicalId();
                        Craft::$app->getDrafts()->removeDraftData($block);
                        Db::delete(Table::MATRIXBLOCKS_OWNERS, [
                            'blockId' => $canonicalBlockId,
                            'ownerId' => $owner->id,
                        ]);
                    }
                } elseif ((int)$block->sortOrder !== $sortOrder) {
                    // Just update its sortOrder
                    $block->sortOrder = $sortOrder;
                    Db::update(Table::MATRIXBLOCKS_OWNERS, [
                        'sortOrder' => $sortOrder,
                    ], [
                        'blockId' => $block->id,
                        'ownerId' => $owner->id,
                    ], [], false);
                }

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
                $fieldSiteIds = $this->getSupportedSiteIds($field->propagationMethod, $owner, $field->propagationKeyFormat);
                $otherSiteIds = array_diff($ownerSiteIds, $fieldSiteIds);

                // If propagateAll isn't set, only deal with sites that the element was just propagated to for the first time
                if (!$owner->propagateAll) {
                    $preexistingOtherSiteIds = array_diff($otherSiteIds, $owner->newSiteIds);
                    $otherSiteIds = array_intersect($otherSiteIds, $owner->newSiteIds);
                } else {
                    $preexistingOtherSiteIds = [];
                }

                if (!empty($otherSiteIds)) {
                    // Get the owner element across each of those sites
                    $localizedOwners = $owner::find()
                        ->drafts($owner->getIsDraft())
                        ->provisionalDrafts($owner->isProvisionalDraft)
                        ->revisions($owner->getIsRevision())
                        ->id($owner->id)
                        ->siteId($otherSiteIds)
                        ->status(null)
                        ->all();

                    // Duplicate Matrix blocks, ensuring we don't process the same blocks more than once
                    $handledSiteIds = [];

                    if ($value instanceof MatrixBlockQuery) {
                        $cachedQuery = (clone $value)->status(null);
                        $cachedQuery->setCachedResult($blocks);
                        $owner->setFieldValue($field->handle, $cachedQuery);
                    }

                    foreach ($localizedOwners as $localizedOwner) {
                        // Make sure we haven't already duplicated blocks for this site, via propagation from another site
                        if (isset($handledSiteIds[$localizedOwner->siteId])) {
                            continue;
                        }

                        // Find all of the field’s supported sites shared with this target
                        $sourceSupportedSiteIds = $this->getSupportedSiteIds($field->propagationMethod, $localizedOwner, $field->propagationKeyFormat);

                        // Do blocks in this target happen to share supported sites with a preexisting site?
                        if (
                            !empty($preexistingOtherSiteIds) &&
                            !empty($sharedPreexistingOtherSiteIds = array_intersect($preexistingOtherSiteIds, $sourceSupportedSiteIds)) &&
                            $preexistingLocalizedOwner = $owner::find()
                                ->drafts($owner->getIsDraft())
                                ->provisionalDrafts($owner->isProvisionalDraft)
                                ->revisions($owner->getIsRevision())
                                ->id($owner->id)
                                ->siteId($sharedPreexistingOtherSiteIds)
                                ->status(null)
                                ->one()
                        ) {
                            // Just resave Matrix blocks for that one site, and let them propagate over to the new site(s) from there
                            $this->saveField($field, $preexistingLocalizedOwner);
                        } else {
                            // Duplicate the blocks, but **don't track** the duplications, so the edit page doesn’t think
                            // its blocks have been replaced by the other sites’ blocks
                            $this->duplicateBlocks($field, $owner, $localizedOwner, trackDuplications: false, force: true);
                        }

                        // Make sure we don't duplicate blocks for any of the sites that were just propagated to
                        $handledSiteIds = array_merge($handledSiteIds, array_flip($sourceSupportedSiteIds));
                    }

                    if ($value instanceof MatrixBlockQuery) {
                        $owner->setFieldValue($field->handle, $value);
                    }
                }
            }

            $transaction->commit();
        } catch (Throwable $e) {
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
     * @param bool $checkOtherSites Whether to duplicate blocks for the source element’s other supported sites
     * @param bool $deleteOtherBlocks Whether to delete any blocks that belong to the element, which weren’t included in the duplication
     * @param bool $trackDuplications whether to keep track of the duplications from [[\craft\services\Elements::$duplicatedElementIds]]
     * and [[\craft\services\Elements::$duplicatedElementSourceIds]]
     * @param bool $force Whether to force duplication, even if it looks like only the block ownership was duplicated
     * @throws Throwable if reasons
     * @since 3.2.0
     */
    public function duplicateBlocks(
        MatrixField $field,
        ElementInterface $source,
        ElementInterface $target,
        bool $checkOtherSites = false,
        bool $deleteOtherBlocks = true,
        bool $trackDuplications = true,
        bool $force = false,
    ): void {
        $elementsService = Craft::$app->getElements();
        /** @var MatrixBlockQuery|Collection $value */
        $value = $source->getFieldValue($field->handle);
        if ($value instanceof Collection) {
            $blocks = $value->all();
        } else {
            $blocks = $value->getCachedResult() ?? (clone $value)->status(null)->all();
        }

        $newBlockIds = [];

        $transaction = Craft::$app->getDb()->beginTransaction();
        try {
            /** @var MatrixBlock[] $blocks */
            foreach ($blocks as $block) {
                $newAttributes = [
                    // Only set the canonicalId if the target owner element is a derivative
                    'canonicalId' => $target->getIsDerivative() ? $block->id : null,
                    'primaryOwnerId' => $target->id,
                    'owner' => $target,
                    'siteId' => $target->siteId,
                    'propagating' => false,
                ];

                if ($target->updatingFromDerivative && $block->getIsDerivative()) {
                    if (
                        ElementHelper::isRevision($source) ||
                        !empty($target->newSiteIds) ||
                        (!$source::trackChanges() || $source->isFieldModified($field->handle, true))
                    ) {
                        $newBlockId = $elementsService->updateCanonicalElement($block, $newAttributes)->id;
                    } else {
                        $newBlockId = $block->getCanonicalId();
                    }
                } elseif (!$force && $block->primaryOwnerId === $target->id) {
                    // Only the block ownership was duplicated, so just update its sort order for the target element
                    Db::update(Table::MATRIXBLOCKS_OWNERS, [
                        'sortOrder' => $block->sortOrder,
                    ], ['blockId' => $block->id, 'ownerId' => $target->id], updateTimestamp: false);
                    $newBlockId = $block->id;
                } else {
                    $newBlockId = $elementsService->duplicateElement($block, $newAttributes, trackDuplication: $trackDuplications)->id;
                }

                $newBlockIds[] = $newBlockId;
            }

            if ($deleteOtherBlocks) {
                // Delete any blocks that shouldn't be there anymore
                $this->_deleteOtherBlocks($field, $target, $newBlockIds);
            }

            $transaction->commit();
        } catch (Throwable $e) {
            $transaction->rollBack();
            throw $e;
        }

        // Duplicate blocks for other sites as well?
        if ($checkOtherSites && $field->propagationMethod !== MatrixField::PROPAGATION_METHOD_ALL) {
            // Find the target's site IDs that *aren't* supported by this site's Matrix blocks
            $targetSiteIds = ArrayHelper::getColumn(ElementHelper::supportedSitesForElement($target), 'siteId');
            $fieldSiteIds = $this->getSupportedSiteIds($field->propagationMethod, $target, $field->propagationKeyFormat);
            $otherSiteIds = array_diff($targetSiteIds, $fieldSiteIds);

            if (!empty($otherSiteIds)) {
                // Get the original element and duplicated element for each of those sites
                $otherSources = $target::find()
                    ->drafts($source->getIsDraft())
                    ->provisionalDrafts($source->isProvisionalDraft)
                    ->revisions($source->getIsRevision())
                    ->id($source->id)
                    ->siteId($otherSiteIds)
                    ->status(null)
                    ->all();
                $otherTargets = $target::find()
                    ->drafts($target->getIsDraft())
                    ->provisionalDrafts($target->isProvisionalDraft)
                    ->revisions($target->getIsRevision())
                    ->id($target->id)
                    ->siteId($otherSiteIds)
                    ->status(null)
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
                    if (in_array($otherSource->siteId, $handledSiteIds, false)) {
                        continue;
                    }

                    $otherTargets[$otherSource->siteId]->updatingFromDerivative = $target->updatingFromDerivative;
                    $this->duplicateBlocks($field, $otherSource, $otherTargets[$otherSource->siteId]);

                    // Make sure we don't duplicate blocks for any of the sites that were just propagated to
                    $sourceSupportedSiteIds = $this->getSupportedSiteIds($field->propagationMethod, $otherSource, $field->propagationKeyFormat);
                    $handledSiteIds = array_merge($handledSiteIds, $sourceSupportedSiteIds);
                }
            }
        }
    }

    /**
     * Duplicates block ownership relations for a new draft element.
     *
     * @param MatrixField $field The Matrix field
     * @param ElementInterface $canonical The canonical element
     * @param ElementInterface $draft The draft element
     * @since 4.0.0
     */
    public function duplicateOwnership(MatrixField $field, ElementInterface $canonical, ElementInterface $draft): void
    {
        if (!$canonical->getIsCanonical()) {
            throw new InvalidArgumentException('The source element must be canonical.');
        }

        if (!$draft->getIsDraft()) {
            throw new InvalidArgumentException('The target element must be a draft.');
        }

        $blocksTable = Table::MATRIXBLOCKS;
        $ownersTable = Table::MATRIXBLOCKS_OWNERS;

        Craft::$app->getDb()->createCommand(<<<SQL
INSERT INTO $ownersTable ([[blockId]], [[ownerId]], [[sortOrder]]) 
SELECT [[o.blockId]], '$draft->id', [[o.sortOrder]] 
FROM $ownersTable AS [[o]]
INNER JOIN $blocksTable AS [[b]] ON [[b.id]] = [[o.blockId]] AND [[b.primaryOwnerId]] = '$canonical->id' AND [[b.fieldId]] = '$field->id'
WHERE [[o.ownerId]] = '$canonical->id'
SQL
        )->execute();
    }

    /**
     * Creates revisions for all the blocks that belong to the given canonical element, and assigns those
     * revisions to the given owner revision.
     *
     * @param MatrixField $field The Matrix field
     * @param ElementInterface $canonical The canonical element
     * @param ElementInterface $revision The revision element
     * @since 4.0.0
     */
    public function createRevisionBlocks(MatrixField $field, ElementInterface $canonical, ElementInterface $revision): void
    {
        // Only fetch blocks in the sites the owner element supports
        $siteIds = ArrayHelper::getColumn(ElementHelper::supportedSitesForElement($canonical), 'siteId');

        /** @var MatrixBlock[] $blocks */
        $blocks = MatrixBlock::find()
            ->ownerId($canonical->id)
            ->fieldId($field->id)
            ->siteId($siteIds)
            ->preferSites([$canonical->siteId])
            ->unique()
            ->status(null)
            ->all();

        $revisionsService = Craft::$app->getRevisions();
        $ownershipData = [];

        foreach ($blocks as $block) {
            $blockRevisionId = $revisionsService->createRevision($block, null, null, [
                'primaryOwnerId' => $revision->id,
                'saveOwnership' => false,
            ]);
            $ownershipData[] = [$blockRevisionId, $revision->id, $block->sortOrder];
        }

        Db::batchInsert(Table::MATRIXBLOCKS_OWNERS, ['blockId', 'ownerId', 'sortOrder'], $ownershipData);
    }

    /**
     * Merges recent canonical Matrix block changes into the given Matrix field’s blocks.
     *
     * @param MatrixField $field The Matrix field
     * @param ElementInterface $owner The element the field is associated with
     * @since 3.7.0
     */
    public function mergeCanonicalChanges(MatrixField $field, ElementInterface $owner): void
    {
        // Get the owner across all sites
        $localizedOwners = $owner::find()
            ->id($owner->id ?: false)
            ->siteId(['not', $owner->siteId])
            ->drafts($owner->getIsDraft())
            ->provisionalDrafts($owner->isProvisionalDraft)
            ->revisions($owner->getIsRevision())
            ->status(null)
            ->ignorePlaceholders()
            ->indexBy('siteId')
            ->all();
        $localizedOwners[$owner->siteId] = $owner;

        // Get the canonical owner across all sites
        $canonicalOwners = $owner::find()
            ->id($owner->getCanonicalId())
            ->siteId(array_keys($localizedOwners))
            ->status(null)
            ->ignorePlaceholders()
            ->all();

        $elementsService = Craft::$app->getElements();
        $handledSiteIds = [];

        foreach ($canonicalOwners as $canonicalOwner) {
            if (isset($handledSiteIds[$canonicalOwner->siteId])) {
                continue;
            }

            // Get all the canonical owner’s blocks, including soft-deleted ones
            /** @var MatrixBlock[] $canonicalBlocks */
            $canonicalBlocks = MatrixBlock::find()
                ->fieldId($field->id)
                ->primaryOwnerId($canonicalOwner->id)
                ->siteId($canonicalOwner->siteId)
                ->status(null)
                ->trashed(null)
                ->ignorePlaceholders()
                ->all();

            // Get all the derivative owner’s blocks, so we can compare
            /** @var MatrixBlock[] $derivativeBlocks */
            $derivativeBlocks = MatrixBlock::find()
                ->fieldId($field->id)
                ->primaryOwnerId($owner->id)
                ->siteId($canonicalOwner->siteId)
                ->status(null)
                ->trashed(null)
                ->ignorePlaceholders()
                ->indexBy('canonicalId')
                ->all();

            foreach ($canonicalBlocks as $canonicalBlock) {
                if (isset($derivativeBlocks[$canonicalBlock->id])) {
                    $derivativeBlock = $derivativeBlocks[$canonicalBlock->id];

                    // Has it been soft-deleted?
                    if ($canonicalBlock->trashed) {
                        // Delete the derivative block too, unless any changes were made to it
                        if ($derivativeBlock->dateUpdated == $derivativeBlock->dateCreated) {
                            $elementsService->deleteElement($derivativeBlock);
                        }
                    } elseif (!$derivativeBlock->trashed && ElementHelper::isOutdated($derivativeBlock)) {
                        // Merge the upstream changes into the derivative block
                        $elementsService->mergeCanonicalChanges($derivativeBlock);
                    }
                } elseif (!$canonicalBlock->trashed && $canonicalBlock->dateCreated > $owner->dateCreated) {
                    // This is a new block, so duplicate it into the derivative owner
                    $elementsService->duplicateElement($canonicalBlock, [
                        'canonicalId' => $canonicalBlock->id,
                        'primaryOwnerId' => $owner->id,
                        'owner' => $localizedOwners[$canonicalBlock->siteId],
                        'siteId' => $canonicalBlock->siteId,
                        'propagating' => false,
                    ]);
                }
            }

            // Keep track of the sites we've already covered
            $siteIds = $this->getSupportedSiteIds($field->propagationMethod, $canonicalOwner, $field->propagationKeyFormat);
            foreach ($siteIds as $siteId) {
                $handledSiteIds[$siteId] = true;
            }
        }
    }

    /**
     * Returns the site IDs that are supported by Matrix blocks for the given propagation method and owner element.
     *
     * @param string $propagationMethod
     * @param ElementInterface $owner
     * @param string|null $propagationKeyFormat
     * @return int[]
     * @since 3.3.18
     */
    public function getSupportedSiteIds(string $propagationMethod, ElementInterface $owner, ?string $propagationKeyFormat = null): array
    {
        /** @var Site[] $allSites */
        $allSites = ArrayHelper::index(Craft::$app->getSites()->getAllSites(), 'id');
        $ownerSiteIds = ArrayHelper::getColumn(ElementHelper::supportedSitesForElement($owner), 'siteId');
        $siteIds = [];

        $view = Craft::$app->getView();
        $elementsService = Craft::$app->getElements();

        if ($propagationMethod === MatrixField::PROPAGATION_METHOD_CUSTOM && $propagationKeyFormat !== null) {
            $propagationKey = $view->renderObjectTemplate($propagationKeyFormat, $owner);
        }

        foreach ($ownerSiteIds as $siteId) {
            switch ($propagationMethod) {
                case MatrixField::PROPAGATION_METHOD_NONE:
                    $include = $siteId == $owner->siteId;
                    break;
                case MatrixField::PROPAGATION_METHOD_SITE_GROUP:
                    $include = $allSites[$siteId]->groupId == $allSites[$owner->siteId]->groupId;
                    break;
                case MatrixField::PROPAGATION_METHOD_LANGUAGE:
                    $include = $allSites[$siteId]->language == $allSites[$owner->siteId]->language;
                    break;
                case MatrixField::PROPAGATION_METHOD_CUSTOM:
                    if (!isset($propagationKey)) {
                        $include = true;
                    } else {
                        $siteOwner = $elementsService->getElementById($owner->id, get_class($owner), $siteId);
                        $include = $siteOwner && $propagationKey === $view->renderObjectTemplate($propagationKeyFormat, $siteOwner);
                    }
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
                'bt.uid',
            ])
            ->from(['bt' => Table::MATRIXBLOCKTYPES])
            ->orderBy(['bt.sortOrder' => SORT_ASC]);
    }

    /**
     * Returns a block type record by its model or UID or creates a new one.
     *
     * @param string|MatrixBlockType $blockType
     * @return MatrixBlockTypeRecord
     * @throws MatrixBlockTypeNotFoundException if $blockType->id is invalid
     */
    private function _getBlockTypeRecord(string|MatrixBlockType $blockType): MatrixBlockTypeRecord
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
    private function _createContentTable(string $tableName): void
    {
        $migration = new CreateMatrixContentTable([
            'tableName' => $tableName,
        ]);

        ob_start();
        $migration->up();
        ob_end_clean();
    }

    /**
     * Deletes blocks from an owner element
     *
     * @param MatrixField $field The Matrix field
     * @param ElementInterface $owner The owner element
     * @param int[] $except Block IDs that should be left alone
     */
    private function _deleteOtherBlocks(MatrixField $field, ElementInterface $owner, array $except): void
    {
        /** @var MatrixBlock[] $blocks */
        $blocks = MatrixBlock::find()
            ->ownerId($owner->id)
            ->fieldId($field->id)
            ->status(null)
            ->siteId($owner->siteId)
            ->andWhere(['not', ['elements.id' => $except]])
            ->all();

        $elementsService = Craft::$app->getElements();
        $deleteOwnership = [];

        foreach ($blocks as $block) {
            if ($block->primaryOwnerId === $owner->id) {
                $elementsService->deleteElement($block);
            } else {
                // Just delete the ownership relation
                $deleteOwnership[] = $block->id;
            }
        }

        if ($deleteOwnership) {
            Db::delete(Table::MATRIXBLOCKS_OWNERS, [
                'blockId' => $deleteOwnership,
                'ownerId' => $owner->id,
            ]);
        }
    }
}
