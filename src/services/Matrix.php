<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\app\services;

use Craft;
use craft\app\base\Element;
use craft\app\base\ElementInterface;
use craft\app\base\Field;
use craft\app\db\Query;
use craft\app\elements\db\MatrixBlockQuery;
use craft\app\errors\MatrixBlockNotFoundException;
use craft\app\errors\MatrixBlockTypeNotFoundException;
use craft\app\fields\Matrix as MatrixField;
use craft\app\helpers\Html;
use craft\app\helpers\MigrationHelper;
use craft\app\helpers\StringHelper;
use craft\app\migrations\CreateMatrixContentTable;
use craft\app\models\FieldLayout;
use craft\app\models\FieldLayoutTab;
use craft\app\elements\MatrixBlock;
use craft\app\models\MatrixBlockType;
use craft\app\records\MatrixBlock as MatrixBlockRecord;
use craft\app\records\MatrixBlockType as MatrixBlockTypeRecord;
use yii\base\Component;
use yii\base\Exception;

/**
 * The Matrix service provides APIs for managing Matrix fields.
 *
 * An instance of the Matrix service is globally accessible in Craft via [[Application::matrix `Craft::$app->getMatrix()`]].
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
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
     * @var
     */
    private $_blockRecordsById;

    /**
     * @var
     */
    private $_uniqueBlockTypeAndFieldHandles;

    /**
     * @var
     */
    private $_parentMatrixFields;

    // Public Methods
    // =========================================================================

    /**
     * Returns the block types for a given Matrix field.
     *
     * @param integer $fieldId The Matrix field ID.
     * @param string  $indexBy The property the block types should be indexed by. Defaults to `null`.
     *
     * @return MatrixBlockType[] An array of block types.
     */
    public function getBlockTypesByFieldId($fieldId, $indexBy = null)
    {
        if (empty($this->_fetchedAllBlockTypesForFieldId[$fieldId])) {
            $this->_blockTypesByFieldId[$fieldId] = [];

            $results = $this->_createBlockTypeQuery()
                ->where('fieldId = :fieldId', [':fieldId' => $fieldId])
                ->all();

            foreach ($results as $result) {
                $blockType = new MatrixBlockType($result);
                $this->_blockTypesById[$blockType->id] = $blockType;
                $this->_blockTypesByFieldId[$fieldId][] = $blockType;
            }

            $this->_fetchedAllBlockTypesForFieldId[$fieldId] = true;
        }

        if (!$indexBy) {
            return $this->_blockTypesByFieldId[$fieldId];
        }

        $blockTypes = [];

        foreach ($this->_blockTypesByFieldId[$fieldId] as $blockType) {
            $blockTypes[$blockType->$indexBy] = $blockType;
        }

        return $blockTypes;
    }

    /**
     * Returns a block type by its ID.
     *
     * @param integer $blockTypeId The block type ID.
     *
     * @return MatrixBlockType|null The block type, or `null` if it didn’t exist.
     */
    public function getBlockTypeById($blockTypeId)
    {
        if (!isset($this->_blockTypesById) || !array_key_exists($blockTypeId,
                $this->_blockTypesById)
        ) {
            $result = $this->_createBlockTypeQuery()
                ->where('id = :id', [':id' => $blockTypeId])
                ->one();

            if ($result) {
                $blockType = new MatrixBlockType($result);
            } else {
                $blockType = null;
            }

            $this->_blockTypesById[$blockTypeId] = $blockType;
        }

        return $this->_blockTypesById[$blockTypeId];
    }

    /**
     * Validates a block type.
     *
     * If the block type doesn’t validate, any validation errors will be stored on the block type.
     *
     * @param MatrixBlockType $blockType            The block type.
     * @param boolean         $validateUniques      Whether the Name and Handle attributes should be validated to
     *                                              ensure they’re unique. Defaults to `true`.
     *
     * @return boolean Whether the block type validated.
     */
    public function validateBlockType(MatrixBlockType $blockType, $validateUniques = true)
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
        $contentService->fieldColumnPrefix = 'field_'.$blockType->handle.'_';

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
                $blockTypeAndFieldHandle = $blockType->handle.'_'.$field->handle;

                if (in_array($blockTypeAndFieldHandle, $this->_uniqueBlockTypeAndFieldHandles)) {
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
     * @param MatrixBlockType $blockType      The block type to be saved.
     * @param boolean         $validate       Whether the block type should be validated before being saved.
     *                                        Defaults to `true`.
     *
     * @return boolean
     * @throws Exception if an error occurs when saving the block type
     * @throws \Exception if reasons
     */
    public function saveBlockType(MatrixBlockType $blockType, $validate = true)
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
                    $oldBlockTypeRecord = MatrixBlockTypeRecord::findOne($blockType->id);
                    $oldBlockType = MatrixBlockType::create($oldBlockTypeRecord);

                    $contentService->fieldContext = 'matrixBlockType:'.$blockType->id;
                    $contentService->fieldColumnPrefix = 'field_'.$oldBlockType->handle.'_';
                    $fieldsService->oldFieldColumnPrefix = 'field_'.$oldBlockType->handle.'_';

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
                $contentService->fieldContext = 'matrixBlockType:'.$blockType->id;
                $contentService->fieldColumnPrefix = 'field_'.$blockType->handle.'_';

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
                $fieldLayout->setTabs([$fieldLayoutTab]);
                $fieldLayout->setFields($fieldLayoutFields);

                $fieldsService->saveLayout($fieldLayout);

                // Update the block type model & record with our new field layout ID
                $blockType->setFieldLayout($fieldLayout);
                $blockType->fieldLayoutId = $fieldLayout->id;
                $blockTypeRecord->fieldLayoutId = $fieldLayout->id;

                // Update the block type with the field layout ID
                $blockTypeRecord->save(false);

                if (isset($oldBlockType)) {
                    // Delete the old field layout
                    $fieldsService->deleteLayoutById($oldBlockType->fieldLayoutId);
                }

                $transaction->commit();
            } catch (\Exception $e) {
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
     *
     * @return boolean Whether the block type was deleted successfully.
     * @throws \Exception if reasons
     */
    public function deleteBlockType(MatrixBlockType $blockType)
    {
        $transaction = Craft::$app->getDb()->beginTransaction();
        try {
            // First delete the blocks of this type
            $blockIds = (new Query())
                ->select('id')
                ->from('{{%matrixblocks}}')
                ->where(['typeId' => $blockType->id])
                ->column();

            $this->deleteBlockById($blockIds);

            // Set the new contentTable
            $contentService = Craft::$app->getContent();
            $fieldsService = Craft::$app->getFields();
            $originalContentTable = $contentService->contentTable;
            /** @var MatrixField $matrixField */
            $matrixField = $fieldsService->getFieldById($blockType->fieldId);
            $newContentTable = $this->getContentTableName($matrixField);
            $contentService->contentTable = $newContentTable;

            // Set the new fieldColumnPrefix
            $originalFieldColumnPrefix = Craft::$app->getContent()->fieldColumnPrefix;
            Craft::$app->getContent()->fieldColumnPrefix = 'field_'.$blockType->handle.'_';

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
        } catch (\Exception $e) {
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
     *
     * @return boolean Whether the settings validated.
     */
    public function validateFieldSettings(MatrixField $matrixField)
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

                if ($value && (!isset($uniqueAttributeValues[$attribute]) || !in_array($value,
                            $uniqueAttributeValues[$attribute]))
                ) {
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
     * @param boolean     $validate    Whether the settings should be validated before being saved.
     *
     * @return boolean Whether the settings saved successfully.
     * @throws \Exception if reasons
     */
    public function saveSettings(MatrixField $matrixField, $validate = true)
    {
        if (!$validate || $this->validateFieldSettings($matrixField)) {
            $transaction = Craft::$app->getDb()->beginTransaction();
            try {
                // Create the content table first since the block type fields will need it
                $oldContentTable = $this->getContentTableName($matrixField, true);
                $newContentTable = $this->getContentTableName($matrixField);

                // Do we need to create/rename the content table?
                if (!Craft::$app->getDb()->tableExists($newContentTable)) {
                    if ($oldContentTable && Craft::$app->getDb()->tableExists($oldContentTable)) {
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
            } catch (\Exception $e) {
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
     *
     * @return boolean Whether the field was deleted successfully.
     * @throws \Exception if reasons
     */
    public function deleteMatrixField(MatrixField $matrixField)
    {
        $transaction = Craft::$app->getDb()->beginTransaction();
        try {
            $originalContentTable = Craft::$app->getContent()->contentTable;
            $contentTable = $this->getContentTableName($matrixField);
            Craft::$app->getContent()->contentTable = $contentTable;

            // Delete the block types
            $blockTypes = $this->getBlockTypesByFieldId($matrixField->id);

            foreach ($blockTypes as $blockType) {
                $this->deleteBlockType($blockType);
            }

            // Drop the content table
            Craft::$app->getDb()->createCommand()
                ->dropTable($contentTable)
                ->execute();

            Craft::$app->getContent()->contentTable = $originalContentTable;

            $transaction->commit();

            return true;
        } catch (\Exception $e) {
            $transaction->rollBack();

            throw $e;
        }
    }

    /**
     * Returns the content table name for a given Matrix field.
     *
     * @param MatrixField $matrixField  The Matrix field.
     * @param boolean     $useOldHandle Whether the method should use the field’s old handle when determining the table
     *                                  name (e.g. to get the existing table name, rather than the new one).
     *
     * @return string|false The table name, or `false` if $useOldHandle was set to `true` and there was no old handle.
     */
    public function getContentTableName(MatrixField $matrixField, $useOldHandle = false)
    {
        $name = '';

        do {
            if ($useOldHandle) {
                if (!$matrixField->oldHandle) {
                    return false;
                }

                $handle = $matrixField->oldHandle;
            } else {
                $handle = $matrixField->handle;
            }

            $name = '_'.StringHelper::toLowerCase($handle).$name;
        } while ($matrixField = $this->getParentMatrixField($matrixField));

        return '{{%matrixcontent'.$name.'}}';
    }

    /**
     * Returns a block by its ID.
     *
     * @param integer $blockId  The Matrix block’s ID.
     * @param integer $siteId   The site ID to return. Defaults to the current site.
     *
     * @return MatrixBlock|null The Matrix block, or `null` if it didn’t exist.
     */
    public function getBlockById($blockId, $siteId = null)
    {
        return Craft::$app->getElements()->getElementById($blockId, MatrixBlock::class, $siteId);
    }

    /**
     * Validates a block.
     *
     * If the block doesn’t validate, any validation errors will be stored on the block.
     *
     * @param MatrixBlock $block The Matrix block to validate.
     *
     * @return boolean Whether the block validated.
     */
    public function validateBlock(MatrixBlock $block)
    {
        $block->clearErrors();

        $blockRecord = $this->_getBlockRecord($block);

        $blockRecord->fieldId = $block->fieldId;
        $blockRecord->ownerId = $block->ownerId;
        $blockRecord->typeId = $block->typeId;
        $blockRecord->sortOrder = $block->sortOrder;

        $blockRecord->validate();
        $block->addErrors($blockRecord->getErrors());

        $originalFieldContext = Craft::$app->getContent()->fieldContext;
        Craft::$app->getContent()->fieldContext = 'matrixBlockType:'.$block->typeId;
        Craft::$app->getContent()->validateContent($block);
        Craft::$app->getContent()->fieldContext = $originalFieldContext;

        return !$block->hasErrors();
    }

    /**
     * Saves a new or existing Matrix block.
     *
     * ```php
     * $block = new MatrixBlock();
     * $block->fieldId = 5;
     * $block->ownerId = 100;
     * $block->ownerSiteId = 1;
     * $block->typeId = 2;
     * $block->sortOrder = 10;
     *
     * $block->setFieldValues([
     *     'fieldHandle' => 'value',
     *     // ...
     * ]);
     *
     * $success = Craft::$app->matrix->saveBlock($block);
     * ```
     *
     * @param MatrixBlock $block    The Matrix block.
     * @param boolean     $validate Whether the block should be validated before being saved.
     *                              Defaults to `true`.
     *
     * @return boolean Whether the block was saved successfully.
     * @throws \Exception if reasons
     */
    public function saveBlock(MatrixBlock $block, $validate = true)
    {
        if (!$validate || $this->validateBlock($block)) {
            $blockRecord = $this->_getBlockRecord($block);
            $isNewBlock = $blockRecord->getIsNewRecord();

            $blockRecord->fieldId = $block->fieldId;
            $blockRecord->ownerId = $block->ownerId;
            $blockRecord->ownerSiteId = $block->ownerSiteId;
            $blockRecord->typeId = $block->typeId;
            $blockRecord->sortOrder = $block->sortOrder;

            $transaction = Craft::$app->getDb()->beginTransaction();
            try {
                if (Craft::$app->getElements()->saveElement($block, false)) {
                    if ($isNewBlock) {
                        $blockRecord->id = $block->id;
                    }

                    $blockRecord->save(false);

                    $transaction->commit();

                    return true;
                }
            } catch (\Exception $e) {
                $transaction->rollBack();

                throw $e;
            }
        }

        return false;
    }

    /**
     * Deletes a block(s) by its ID.
     *
     * @param integer|array $blockIds The Matrix block ID(s).
     *
     * @return boolean Whether the block(s) were deleted successfully.
     */
    public function deleteBlockById($blockIds)
    {
        if (!$blockIds) {
            return false;
        }

        if (!is_array($blockIds)) {
            $blockIds = [$blockIds];
        }

        if (!Craft::$app->getRequest()->getIsConsoleRequest()) {
            // Tell the browser to forget about these
            Craft::$app->getSession()->addJsResourceFlash('js/MatrixInput.js');

            foreach ($blockIds as $blockId) {
                Craft::$app->getSession()->addJsFlash('Craft.MatrixInput.forgetCollapsedBlockId('.$blockId.');');
            }
        }

        // Pass this along to the Elements service for the heavy lifting.
        return Craft::$app->getElements()->deleteElementById($blockIds);
    }

    /**
     * Saves a Matrix field.
     *
     * @param MatrixField      $field The Matrix field
     * @param ElementInterface $owner The element the field is associated with
     *
     * @return boolean Whether the field was saved successfully.
     * @throws \Exception if reasons
     */
    public function saveField(MatrixField $field, ElementInterface $owner)
    {
        /** @var Element $owner */
        /** @var MatrixBlockQuery $query */
        /** @var MatrixBlock[] $blocks */
        $query = $owner->getFieldValue($field->handle);
        $blocks = $query->all();

        $transaction = Craft::$app->getDb()->beginTransaction();
        try {
            // First thing's first. Let's make sure that the blocks for this field/owner respect the field's translation
            // setting
            $this->_applyFieldTranslationSetting($owner, $field, $blocks);

            $blockIds = [];
            $collapsedBlockIds = [];

            foreach ($blocks as $block) {
                $block->ownerId = $owner->id;
                $block->ownerSiteId = ($field->localizeBlocks ? $owner->siteId : null);

                $this->saveBlock($block, false);

                $blockIds[] = $block->id;

                // Tell the browser to collapse this block?
                if ($block->collapsed) {
                    $collapsedBlockIds[] = $block->id;
                }
            }

            // Get the IDs of blocks that are row deleted
            $deletedBlockConditions = [
                'and',
                'ownerId = :ownerId',
                'fieldId = :fieldId',
                ['not in', 'id', $blockIds]
            ];

            $deletedBlockParams = [
                ':ownerId' => $owner->id,
                ':fieldId' => $field->id
            ];

            if ($field->localizeBlocks) {
                $deletedBlockConditions[] = 'ownerSiteId  = :ownerSiteId';
                $deletedBlockParams[':ownerSiteId'] = $owner->siteId;
            }

            $deletedBlockIds = (new Query())
                ->select('id')
                ->from('{{%matrixblocks}}')
                ->where($deletedBlockConditions, $deletedBlockParams)
                ->column();

            $this->deleteBlockById($deletedBlockIds);

            $transaction->commit();
        } catch (\Exception $e) {
            $transaction->rollBack();

            throw $e;
        }

        // Tell the browser to collapse any new block IDs
        if (!Craft::$app->getRequest()->getIsConsoleRequest() && $collapsedBlockIds) {
            Craft::$app->getSession()->addJsResourceFlash('js/MatrixInput.js');

            foreach ($collapsedBlockIds as $blockId) {
                Craft::$app->getSession()->addJsFlash('Craft.MatrixInput.rememberCollapsedBlockId('.$blockId.');');
            }
        }

        return true;
    }

    /**
     * Returns the parent Matrix field, if any.
     *
     * @param MatrixField $matrixField The Matrix field.
     *
     * @return MatrixField|null The Matrix field’s parent Matrix field, or `null` if there is none.
     */
    public function getParentMatrixField(MatrixField $matrixField)
    {
        if (!isset($this->_parentMatrixFields) || !array_key_exists($matrixField->id,
                $this->_parentMatrixFields)
        ) {
            // Does this Matrix field belong to another one?
            $parentMatrixFieldId = (new Query())
                ->select('fields.id')
                ->from('{{%fields}} fields')
                ->innerJoin('{{%matrixblocktypes}} blocktypes', 'blocktypes.fieldId = fields.id')
                ->innerJoin('{{%fieldlayoutfields}} fieldlayoutfields', 'fieldlayoutfields.layoutId = blocktypes.fieldLayoutId')
                ->where('fieldlayoutfields.fieldId = :matrixFieldId',
                    [':matrixFieldId' => $matrixField->id])
                ->scalar();

            if ($parentMatrixFieldId) {
                $this->_parentMatrixFields[$matrixField->id] = Craft::$app->getFields()->getFieldById($parentMatrixFieldId);
            } else {
                $this->_parentMatrixFields[$matrixField->id] = null;
            }
        }

        return $this->_parentMatrixFields[$matrixField->id];
    }

    // Private Methods
    // =========================================================================

    /**
     * Returns a Query object prepped for retrieving block types.
     *
     * @return Query
     */
    private function _createBlockTypeQuery()
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
            ->from('{{%matrixblocktypes}}')
            ->orderBy('sortOrder');
    }

    /**
     * Returns a block type record by its ID or creates a new one.
     *
     * @param MatrixBlockType $blockType
     *
     * @return MatrixBlockTypeRecord
     * @throws MatrixBlockTypeNotFoundException if $blockType->id is invalid
     */
    private function _getBlockTypeRecord(MatrixBlockType $blockType)
    {
        if (!$blockType->getIsNew()) {
            if (!isset($this->_blockTypeRecordsById) || !array_key_exists($blockType->id, $this->_blockTypeRecordsById)) {
                $this->_blockTypeRecordsById[$blockType->id] = MatrixBlockTypeRecord::findOne($blockType->id);

                if (!$this->_blockTypeRecordsById[$blockType->id]) {
                    throw new MatrixBlockTypeNotFoundException("No block type exists with the ID '{$blockType->id}'");
                }
            }

            return $this->_blockTypeRecordsById[$blockType->id];
        }

        return new MatrixBlockTypeRecord();
    }

    /**
     * Returns a block record by its ID or creates a new one.
     *
     * @param MatrixBlock $block
     *
     * @return MatrixBlockRecord
     * @throws MatrixBlockNotFoundException if $block->id is invalid
     */
    private function _getBlockRecord(MatrixBlock $block)
    {
        if ($block->id) {
            if (!isset($this->_blockRecordsById) || !array_key_exists($block->id, $this->_blockRecordsById)) {
                $this->_blockRecordsById[$block->id] = MatrixBlockRecord::find()
                    ->where(['id' => $block->id])
                    ->with('element')
                    ->one();

                if (!$this->_blockRecordsById[$block->id]) {
                    throw new MatrixBlockNotFoundException("No Matrix block exists with the ID '{$block->id}'");
                }
            }

            return $this->_blockRecordsById[$block->id];
        }

        return new MatrixBlockRecord();
    }

    /**
     * Creates the content table for a Matrix field.
     *
     * @param string $tableName
     *
     * @return void
     */
    private function _createContentTable($tableName)
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
     * @param MatrixField      $field
     * @param MatrixBlock[]    $blocks
     *
     * @return void
     */
    private function _applyFieldTranslationSetting($owner, $field, $blocks)
    {
        /** @var Element $owner */
        // Does it look like any work is needed here?
        $applyNewTranslationSetting = false;

        foreach ($blocks as $block) {
            if ($block->id && (
                    ($field->localizeBlocks && !$block->ownerSiteId) ||
                    (!$field->localizeBlocks && $block->ownerSiteId)
                )
            ) {
                $applyNewTranslationSetting = true;
                break;
            }
        }

        if ($applyNewTranslationSetting) {
            // Get all of the blocks for this field/owner that use the other sites, whose ownerSiteId attribute is set
            // incorrectly
            /** @var array $blocksInOtherSites */
            $blocksInOtherSites = [];

            $query = MatrixBlock::find()
                ->fieldId($field->id)
                ->ownerId($owner->id)
                ->status(null)
                ->enabledForSite(false)
                ->limit(null);

            if ($field->localizeBlocks) {
                $query->ownerSiteId(':empty:');
            }

            foreach (Craft::$app->getSites()->getAllSiteIds() as $siteId) {
                if ($siteId == $owner->siteId) {
                    continue;
                }

                $query->siteId($siteId);

                if (!$field->localizeBlocks) {
                    $query->ownerSiteId($siteId);
                }

                $blocksInOtherSite = $query->all();

                if ($blocksInOtherSite) {
                    $blocksInOtherSites[$siteId] = $blocksInOtherSite;
                }
            }

            if ($blocksInOtherSites) {
                if ($field->localizeBlocks) {
                    $newBlockIds = [];

                    // Duplicate the other-site blocks so each site has their own unique set of blocks
                    foreach ($blocksInOtherSites as $siteId => $blocksInOtherSite) {
                        foreach ($blocksInOtherSite as $blockInOtherSite) {
                            /** @var MatrixBlock $blockInOtherSite */
                            $originalBlockId = $blockInOtherSite->id;

                            $blockInOtherSite->id = null;
                            $blockInOtherSite->contentId = null;
                            $blockInOtherSite->ownerSiteId = $siteId;
                            $this->saveBlock($blockInOtherSite, false);

                            $newBlockIds[$originalBlockId][$siteId] = $blockInOtherSite->id;
                        }
                    }

                    // Duplicate the relations, too.  First by getting all of the existing relations for the original
                    // blocks
                    $relations = (new Query())
                        ->select([
                            'fieldId',
                            'sourceId',
                            'sourceSiteId',
                            'targetId',
                            'sortOrder'
                        ])
                        ->from('{{%relations}}')
                        ->where(['in', 'sourceId', array_keys($newBlockIds)])
                        ->all();

                    if ($relations) {
                        // Now duplicate each one for the other sites' new blocks
                        $rows = [];

                        foreach ($relations as $relation) {
                            $originalBlockId = $relation['sourceId'];

                            // Just to be safe...
                            if (isset($newBlockIds[$originalBlockId])) {
                                foreach ($newBlockIds[$originalBlockId] as $siteId => $newBlockId) {
                                    $rows[] = [
                                        $relation['fieldId'],
                                        $newBlockId,
                                        $relation['sourceSiteId'],
                                        $relation['targetId'],
                                        $relation['sortOrder']
                                    ];
                                }
                            }
                        }

                        Craft::$app->getDb()->createCommand()
                            ->batchInsert(
                                'relations',
                                [
                                    'fieldId',
                                    'sourceId',
                                    'sourceSiteId',
                                    'targetId',
                                    'sortOrder'
                                ],
                                $rows)
                            ->execute();
                    }
                } else {
                    // Delete all of these blocks
                    $blockIdsToDelete = [];

                    foreach ($blocksInOtherSites as $blocksInOtherSite) {
                        foreach ($blocksInOtherSite as $blockInOtherSite) {
                            $blockIdsToDelete[] = $blockInOtherSite->id;
                        }
                    }

                    $this->deleteBlockById($blockIdsToDelete);
                }
            }
        }
    }
}
