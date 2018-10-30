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
use craft\elements\db\ElementQueryInterface;
use craft\elements\db\MatrixBlockQuery;
use craft\elements\MatrixBlock;
use craft\errors\MatrixBlockTypeNotFoundException;
use craft\fields\BaseRelationField;
use craft\fields\Matrix as MatrixField;
use craft\helpers\ArrayHelper;
use craft\helpers\ElementHelper;
use craft\helpers\Html;
use craft\helpers\MigrationHelper;
use craft\helpers\StringHelper;
use craft\migrations\CreateMatrixContentTable;
use craft\models\FieldLayout;
use craft\models\FieldLayoutTab;
use craft\models\MatrixBlockType;
use craft\records\MatrixBlockType as MatrixBlockTypeRecord;
use craft\web\assets\matrix\MatrixAsset;
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
            ->where(['fieldId' => $fieldId])
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
            ->where(['id' => $blockTypeId])
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
     * @param bool $validate Whether the block type should be validated before being saved.
     * Defaults to `true`.
     * @return bool
     * @throws Exception if an error occurs when saving the block type
     * @throws \Throwable if reasons
     */
    public function saveBlockType(MatrixBlockType $blockType, bool $validate = true): bool
    {
        if (!$validate || $this->validateBlockType($blockType)) {
            $transaction = Craft::$app->getDb()->beginTransaction();
            try {
                $contentService = Craft::$app->getContent();
                $fieldsService = Craft::$app->getFields();

                $originalFieldContext = $contentService->fieldContext;
                $originalFieldColumnPrefix = $contentService->fieldColumnPrefix;
                $originalOldFieldColumnPrefix = $fieldsService->oldFieldColumnPrefix;

                // Get the block type record
                $blockTypeRecord = $this->_getBlockTypeRecord($blockType);
                $isNewBlockType = $blockType->getIsNew();

                if (!$isNewBlockType) {
                    // Get the old block type fields
                    $result = $this->_createBlockTypeQuery()
                        ->where(['id' => $blockType->id])
                        ->one();

                    $oldBlockType = new MatrixBlockType($result);

                    $contentService->fieldContext = 'matrixBlockType:' . $blockType->id;
                    $contentService->fieldColumnPrefix = 'field_' . $oldBlockType->handle . '_';
                    $fieldsService->oldFieldColumnPrefix = 'field_' . $oldBlockType->handle . '_';

                    $oldFieldsById = [];

                    foreach ($oldBlockType->getFields() as $field) {
                        /** @var Field $field */
                        $oldFieldsById[$field->id] = $field;
                    }

                    // Figure out which ones are still around
                    foreach ($blockType->getFields() as $field) {
                        /** @var Field $field */
                        if (!$field->getIsNew()) {
                            unset($oldFieldsById[$field->id]);
                        }
                    }

                    // Drop the old fields that aren't around anymore
                    foreach ($oldFieldsById as $field) {
                        $fieldsService->deleteField($field);
                    }

                    // Refresh the schema cache
                    Craft::$app->getDb()->getSchema()->refresh();
                }

                // Set the basic info on the new block type record
                $blockTypeRecord->fieldId = $blockType->fieldId;
                $blockTypeRecord->name = $blockType->name;
                $blockTypeRecord->handle = $blockType->handle;
                $blockTypeRecord->sortOrder = $blockType->sortOrder;

                // Save it, minus the field layout for now
                $blockTypeRecord->save(false);

                if ($isNewBlockType) {
                    // Set the new ID on the model
                    $blockType->id = $blockTypeRecord->id;
                }

                // Save the fields and field layout
                // -------------------------------------------------------------

                $fieldLayoutFields = [];
                $sortOrder = 0;

                // Resetting the fieldContext here might be redundant if this isn't a new blocktype but whatever
                $contentService->fieldContext = 'matrixBlockType:' . $blockType->id;
                $contentService->fieldColumnPrefix = 'field_' . $blockType->handle . '_';

                foreach ($blockType->getFields() as $field) {
                    // Hack to allow blank field names
                    if (!$field->name) {
                        $field->name = '__blank__';
                    }

                    if (!$fieldsService->saveField($field, false)) {
                        throw new Exception('An error occurred while saving this Matrix block type.');
                    }

                    $field->sortOrder = ++$sortOrder;

                    $fieldLayoutFields[] = $field;
                }

                $contentService->fieldContext = $originalFieldContext;
                $contentService->fieldColumnPrefix = $originalFieldColumnPrefix;
                $fieldsService->oldFieldColumnPrefix = $originalOldFieldColumnPrefix;

                $fieldLayoutTab = new FieldLayoutTab();
                $fieldLayoutTab->name = 'Content';
                $fieldLayoutTab->sortOrder = 1;
                $fieldLayoutTab->setFields($fieldLayoutFields);

                $fieldLayout = new FieldLayout();
                $fieldLayout->type = MatrixBlock::class;

                if (isset($oldBlockType)) {
                    $fieldLayout->id = $oldBlockType->fieldLayoutId;
                }

                $fieldLayout->setTabs([$fieldLayoutTab]);
                $fieldLayout->setFields($fieldLayoutFields);
                $fieldsService->saveLayout($fieldLayout);
                $blockType->setFieldLayout($fieldLayout);
                $blockType->fieldLayoutId = (int)$fieldLayout->id;
                $blockTypeRecord->fieldLayoutId = $fieldLayout->id;

                // Update the block type with the field layout ID
                $blockTypeRecord->save(false);

                $transaction->commit();
            } catch (\Throwable $e) {
                $transaction->rollBack();

                throw $e;
            }

            return true;
        }

        return false;
    }

    /**
     * Deletes a block type.
     *
     * @param MatrixBlockType $blockType The block type.
     * @return bool Whether the block type was deleted successfully.
     * @throws \Throwable if reasons
     */
    public function deleteBlockType(MatrixBlockType $blockType): bool
    {
        $transaction = Craft::$app->getDb()->beginTransaction();
        try {
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
            Craft::$app->getFields()->deleteLayoutById($blockType->fieldLayoutId);

            // Finally delete the actual block type
            $affectedRows = Craft::$app->getDb()->createCommand()
                ->delete('{{%matrixblocktypes}}', ['id' => $blockType->id])
                ->execute();

            $transaction->commit();

            return (bool)$affectedRows;
        } catch (\Throwable $e) {
            $transaction->rollBack();

            throw $e;
        }
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
        if (!$validate || $this->validateFieldSettings($matrixField)) {
            $db = Craft::$app->getDb();
            $transaction = $db->beginTransaction();
            try {
                // Create the content table first since the block type fields will need it
                $oldContentTable = $matrixField->getOldContentTable();
                $newContentTable = $matrixField->contentTable;

                // Do we need to create/rename the content table?
                if (!$db->tableExists($newContentTable)) {
                    if ($oldContentTable && $db->tableExists($oldContentTable)) {
                        MigrationHelper::renameTable($oldContentTable, $newContentTable);
                    } else {
                        $this->_createContentTable($newContentTable);
                    }
                }

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
                Craft::$app->getContent()->contentTable = $newContentTable;

                foreach ($matrixField->getBlockTypes() as $blockType) {
                    $sortOrder++;
                    $blockType->fieldId = $matrixField->id;
                    $blockType->sortOrder = $sortOrder;
                    $this->saveBlockType($blockType, false);
                }

                Craft::$app->getContent()->contentTable = $originalContentTable;

                $transaction->commit();

                // Update our cache of this field's block types
                $this->_blockTypesByFieldId[$matrixField->id] = $matrixField->getBlockTypes();

                return true;
            } catch (\Throwable $e) {
                $transaction->rollBack();

                throw $e;
            }
        } else {
            return false;
        }
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
        $transaction = Craft::$app->getDb()->beginTransaction();
        try {
            $originalContentTable = Craft::$app->getContent()->contentTable;
            Craft::$app->getContent()->contentTable = $matrixField->contentTable;

            // Delete the block types
            $blockTypes = $this->getBlockTypesByFieldId($matrixField->id);

            foreach ($blockTypes as $blockType) {
                $this->deleteBlockType($blockType);
            }

            // Drop the content table
            Craft::$app->getDb()->createCommand()
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
        /** @var MatrixBlock|null $block */
        $block = Craft::$app->getElements()->getElementById($blockId, MatrixBlock::class, $siteId);

        return $block;
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
        /** @var MatrixBlockQuery $query */
        /** @var MatrixBlock[] $blocks */
        $query = $owner->getFieldValue($field->handle);

        // If the element is brand new and propagating, and the field manages blocks on a per-site basis,
        // then we will need to duplicate the blocks for this site
        $duplicateBlocks = !$query->ownerId && $owner->propagating && $field->localizeBlocks;

        // Skip if the element is propagating right now, and we don't need to duplicate the blocks
        if ($owner->propagating && !$duplicateBlocks) {
            return;
        }

        if (($blocks = $query->getCachedResult()) === null) {
            $query = clone $query;
            $query->anyStatus();
            $blocks = $query->all();
        }

        $elementsService = Craft::$app->getElements();

        $transaction = Craft::$app->getDb()->beginTransaction();
        try {
            // If this is a preexisting element, make sure that the blocks for this field/owner respect the field's translation setting
            if ($query->ownerId) {
                $this->_applyFieldTranslationSetting($owner, $field);
            }

            // If the query is set to fetch blocks of a different owner, we're probably duplicating an element
            if ($query->ownerId && $query->ownerId != $owner->id) {
                // Make sure this owner doesn't already have blocks
                $newQuery = clone $query;
                $newQuery->ownerId = $owner->id;
                if (!$newQuery->exists()) {
                    // Duplicate the blocks for the new owner
                    foreach ($blocks as $block) {
                        $elementsService->duplicateElement($block, [
                            'ownerId' => $owner->id,
                            'ownerSiteId' => $field->localizeBlocks ? $owner->siteId : null
                        ]);
                    }
                }
            } else {
                $blockIds = [];
                $collapsedBlockIds = [];

                // Only propagate the blocks if the owner isn't being propagated
                $propagate = !$owner->propagating;

                foreach ($blocks as $block) {
                    if ($duplicateBlocks) {
                        $block = $elementsService->duplicateElement($block, [
                            'ownerId' => $owner->id,
                            'ownerSiteId' => ($field->localizeBlocks ? $owner->siteId : null),
                            'siteId' => $owner->siteId,
                            'propagating' => false,
                        ]);
                    } else {
                        $block->ownerId = $owner->id;
                        $block->ownerSiteId = ($field->localizeBlocks ? $owner->siteId : null);
                        $block->propagating = $owner->propagating;
                        $elementsService->saveElement($block, false, $propagate);
                    }

                    $blockIds[] = $block->id;

                    // Tell the browser to collapse this block?
                    if ($block->collapsed) {
                        $collapsedBlockIds[] = $block->id;
                    }
                }

                // Delete any blocks that shouldn't be there anymore
                $deleteBlocksQuery = MatrixBlock::find()
                    ->anyStatus()
                    ->ownerId($owner->id)
                    ->fieldId($field->id)
                    ->where(['not', ['elements.id' => $blockIds]]);

                if ($field->localizeBlocks) {
                    $deleteBlocksQuery->ownerSiteId($owner->siteId);
                } else {
                    $deleteBlocksQuery->siteId($owner->siteId);
                }

                foreach ($deleteBlocksQuery->all() as $deleteBlock) {
                    Craft::$app->getElements()->deleteElement($deleteBlock);
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
                Craft::$app->getSession()->addJsFlash('Craft.MatrixInput.rememberCollapsedBlockId(' . $blockId . ');');
            }
        }
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
                'id',
                'fieldId',
                'fieldLayoutId',
                'name',
                'handle',
                'sortOrder'
            ])
            ->from(['{{%matrixblocktypes}}'])
            ->orderBy(['sortOrder' => SORT_ASC]);
    }

    /**
     * Returns a block type record by its ID or creates a new one.
     *
     * @param MatrixBlockType $blockType
     * @return MatrixBlockTypeRecord
     * @throws MatrixBlockTypeNotFoundException if $blockType->id is invalid
     */
    private function _getBlockTypeRecord(MatrixBlockType $blockType): MatrixBlockTypeRecord
    {
        if ($blockType->getIsNew()) {
            return new MatrixBlockTypeRecord();
        }

        if ($this->_blockTypeRecordsById !== null && array_key_exists($blockType->id, $this->_blockTypeRecordsById)) {
            return $this->_blockTypeRecordsById[$blockType->id];
        }

        if (($this->_blockTypeRecordsById[$blockType->id] = MatrixBlockTypeRecord::findOne($blockType->id)) === null) {
            throw new MatrixBlockTypeNotFoundException('Invalid block type ID: ' . $blockType->id);
        }

        return $this->_blockTypeRecordsById[$blockType->id];
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
     * Applies the field's translation setting to a set of blocks.
     *
     * @param ElementInterface $owner
     * @param MatrixField $field
     */
    private function _applyFieldTranslationSetting(ElementInterface $owner, MatrixField $field)
    {
        /** @var Element $owner */
        // If the field is translatable, see if there are any global blocks that should be localized
        if ($field->localizeBlocks) {
            $blockQuery = MatrixBlock::find()
                ->fieldId($field->id)
                ->ownerId($owner->id)
                ->anyStatus()
                ->siteId($owner->siteId)
                ->ownerSiteId(':empty:');
            $blocks = $blockQuery->all();

            if (!empty($blocks)) {
                // Duplicate the blocks for each of the owner's other sites
                $elementsService = Craft::$app->getElements();
                $siteIds = ArrayHelper::getColumn(ElementHelper::supportedSitesForElement($owner), 'siteId');

                foreach ($siteIds as $siteId) {
                    if ($siteId != $owner->siteId) {
                        $blockQuery->siteId = $siteId;
                        $siteBlocks = $blockQuery->all();

                        foreach ($siteBlocks as $siteBlock) {
                            $elementsService->duplicateElement($siteBlock, [
                                'siteId' => (int)$siteId,
                                'ownerSiteId' => (int)$siteId,
                            ]);
                        }
                    }
                }

                // Now resave the blocks for this site
                foreach ($blocks as $block) {
                    $block->ownerSiteId = $owner->siteId;
                    Craft::$app->getElements()->saveElement($block, false);
                }
            }
        } else {
            // Otherwise, see if the field has any localized blocks that should be deleted
            $elementsService = Craft::$app->getElements();
            foreach (Craft::$app->getSites()->getAllSiteIds() as $siteId) {
                if ($siteId != $owner->siteId) {
                    $blocks = MatrixBlock::find()
                        ->fieldId($field->id)
                        ->ownerId($owner->id)
                        ->anyStatus()
                        ->siteId($siteId)
                        ->ownerSiteId($siteId)
                        ->all();

                    foreach ($blocks as $block) {
                        $elementsService->deleteElement($block);
                    }
                }
            }
        }
    }
}
