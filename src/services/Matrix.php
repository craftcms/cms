<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2015 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\services;

use Craft;
use craft\app\base\Element;
use craft\app\base\ElementInterface;
use craft\app\db\Query;
use craft\app\elements\db\MatrixBlockQuery;
use craft\app\enums\ColumnType;
use craft\app\errors\Exception;
use craft\app\fields\Matrix as MatrixField;
use craft\app\helpers\HtmlHelper;
use craft\app\helpers\MigrationHelper;
use craft\app\helpers\StringHelper;
use craft\app\models\FieldLayout as FieldLayoutModel;
use craft\app\models\FieldLayoutTab as FieldLayoutTabModel;
use craft\app\elements\MatrixBlock;
use craft\app\models\MatrixBlockType as MatrixBlockTypeModel;
use craft\app\records\MatrixBlock as MatrixBlockRecord;
use craft\app\records\MatrixBlockType as MatrixBlockTypeRecord;
use yii\base\Component;

/**
 * The Matrix service provides APIs for managing Matrix fields.
 *
 * An instance of the Matrix service is globally accessible in Craft via [[Application::matrix `Craft::$app->getMatrix()`]].
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
	 * @param int    $fieldId The Matrix field ID.
	 * @param string $indexBy The property the block types should be indexed by. Defaults to `null`.
	 *
	 * @return MatrixBlockTypeModel[] An array of block types.
	 */
	public function getBlockTypesByFieldId($fieldId, $indexBy = null)
	{
		if (empty($this->_fetchedAllBlockTypesForFieldId[$fieldId]))
		{
			$this->_blockTypesByFieldId[$fieldId] = [];

			$results = $this->_createBlockTypeQuery()
				->where('fieldId = :fieldId', [':fieldId' => $fieldId])
				->all();

			foreach ($results as $result)
			{
				$blockType = new MatrixBlockTypeModel($result);
				$this->_blockTypesById[$blockType->id] = $blockType;
				$this->_blockTypesByFieldId[$fieldId][] = $blockType;
			}

			$this->_fetchedAllBlockTypesForFieldId[$fieldId] = true;
		}

		if (!$indexBy)
		{
			return $this->_blockTypesByFieldId[$fieldId];
		}
		else
		{
			$blockTypes = [];

			foreach ($this->_blockTypesByFieldId[$fieldId] as $blockType)
			{
				$blockTypes[$blockType->$indexBy] = $blockType;
			}

			return $blockTypes;
		}
	}

	/**
	 * Returns a block type by its ID.
	 *
	 * @param int $blockTypeId The block type ID.
	 *
	 * @return MatrixBlockTypeModel|null The block type, or `null` if it didn’t exist.
	 */
	public function getBlockTypeById($blockTypeId)
	{
		if (!isset($this->_blockTypesById) || !array_key_exists($blockTypeId, $this->_blockTypesById))
		{
			$result = $this->_createBlockTypeQuery()
				->where('id = :id', [':id' => $blockTypeId])
				->one();

			if ($result)
			{
				$blockType = new MatrixBlockTypeModel($result);
			}
			else
			{
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
	 * @param MatrixBlockTypeModel $blockType       The block type.
	 * @param bool                 $validateUniques Whether the Name and Handle attributes should be validated to
	 *                                              ensure they’re unique. Defaults to `true`.
	 *
	 * @return bool Whether the block type validated.
	 */
	public function validateBlockType(MatrixBlockTypeModel $blockType, $validateUniques = true)
	{
		$validates = true;

		$blockTypeRecord = $this->_getBlockTypeRecord($blockType);

		$blockTypeRecord->fieldId = $blockType->fieldId;
		$blockTypeRecord->name    = $blockType->name;
		$blockTypeRecord->handle  = $blockType->handle;

		$blockTypeRecord->validateUniques = $validateUniques;

		if (!$blockTypeRecord->validate())
		{
			$validates = false;
			$blockType->addErrors($blockTypeRecord->getErrors());
		}

		$blockTypeRecord->validateUniques = true;

		// Can't validate multiple new rows at once so we'll need to give these temporary context to avoid false unique
		// handle validation errors, and just validate those manually. Also apply the future fieldColumnPrefix so that
		// field handle validation takes its length into account.
		$contentService = Craft::$app->getContent();
		$originalFieldContext      = $contentService->fieldContext;
		$originalFieldColumnPrefix = $contentService->fieldColumnPrefix;

		$contentService->fieldContext      = StringHelper::randomString(10);
		$contentService->fieldColumnPrefix = 'field_'.$blockType->handle.'_';

		foreach ($blockType->getFields() as $field)
		{
			// Hack to allow blank field names
			if (!$field->name)
			{
				$field->name = '__blank__';
			}

			$field->validate();

			// Make sure the block type handle + field handle combo is unique for the whole field. This prevents us from
			// worrying about content column conflicts like "a" + "b_c" == "a_b" + "c".
			if ($blockType->handle && $field->handle)
			{
				$blockTypeAndFieldHandle = $blockType->handle.'_'.$field->handle;

				if (in_array($blockTypeAndFieldHandle, $this->_uniqueBlockTypeAndFieldHandles))
				{
					// This error *might* not be entirely accurate, but it's such an edge case that it's probably better
					// for the error to be worded for the common problem (two duplicate handles within the same block
					// type).
					$error = Craft::t('app', '{attribute} "{value}" has already been taken.', [
						'attribute' => Craft::t('app', 'Handle'),
						'value' => $field->handle
					]);

					$field->addError('handle', $error);
				}
				else
				{
					$this->_uniqueBlockTypeAndFieldHandles[] = $blockTypeAndFieldHandle;
				}
			}

			if ($field->hasErrors())
			{
				$blockType->hasFieldErrors = true;
				$validates = false;
			}
		}

		$contentService->fieldContext      = $originalFieldContext;
		$contentService->fieldColumnPrefix = $originalFieldColumnPrefix;

		return $validates;
	}

	/**
	 * Saves a block type.
	 *
	 * @param MatrixBlockTypeModel $blockType The block type to be saved.
	 * @param bool                 $validate  Whether the block type should be validated before being saved.
	 *                                        Defaults to `true`.
	 *
	 * @return bool
	 * @throws Exception
	 * @throws \Exception
	 */
	public function saveBlockType(MatrixBlockTypeModel $blockType, $validate = true)
	{
		if (!$validate || $this->validateBlockType($blockType))
		{
			$transaction = Craft::$app->getDb()->getTransaction() === null ? Craft::$app->getDb()->beginTransaction() : null;
			try
			{
				$contentService = Craft::$app->getContent();
				$fieldsService  = Craft::$app->getFields();

				$originalFieldContext         = $contentService->fieldContext;
				$originalFieldColumnPrefix    = $contentService->fieldColumnPrefix;
				$originalOldFieldColumnPrefix = $fieldsService->oldFieldColumnPrefix;

				// Get the block type record
				$blockTypeRecord = $this->_getBlockTypeRecord($blockType);
				$isNewBlockType = $blockType->isNew();

				if (!$isNewBlockType)
				{
					// Get the old block type fields
					$oldBlockTypeRecord = MatrixBlockTypeRecord::findOne($blockType->id);
					$oldBlockType = MatrixBlockTypeModel::create($oldBlockTypeRecord);

					$contentService->fieldContext        = 'matrixBlockType:'.$blockType->id;
					$contentService->fieldColumnPrefix   = 'field_'.$oldBlockType->handle.'_';
					$fieldsService->oldFieldColumnPrefix = 'field_'.$oldBlockType->handle.'_';

					$oldFieldsById = [];

					foreach ($oldBlockType->getFields() as $field)
					{
						$oldFieldsById[$field->id] = $field;
					}

					// Figure out which ones are still around
					foreach ($blockType->getFields() as $field)
					{
						if (!$field->isNew())
						{
							unset($oldFieldsById[$field->id]);
						}
					}

					// Drop the old fields that aren't around anymore
					foreach ($oldFieldsById as $field)
					{
						$fieldsService->deleteField($field);
					}

					// Refresh the schema cache
					Craft::$app->getDb()->getSchema()->refresh();
				}

				// Set the basic info on the new block type record
				$blockTypeRecord->fieldId   = $blockType->fieldId;
				$blockTypeRecord->name      = $blockType->name;
				$blockTypeRecord->handle    = $blockType->handle;
				$blockTypeRecord->sortOrder = $blockType->sortOrder;

				// Save it, minus the field layout for now
				$blockTypeRecord->save(false);

				if ($isNewBlockType)
				{
					// Set the new ID on the model
					$blockType->id = $blockTypeRecord->id;
				}

				// Save the fields and field layout
				// -------------------------------------------------------------

				$fieldLayoutFields = [];
				$sortOrder = 0;

				// Resetting the fieldContext here might be redundant if this isn't a new blocktype but whatever
				$contentService->fieldContext      = 'matrixBlockType:'.$blockType->id;
				$contentService->fieldColumnPrefix = 'field_'.$blockType->handle.'_';

				foreach ($blockType->getFields() as $field)
				{
					// Hack to allow blank field names
					if (!$field->name)
					{
						$field->name = '__blank__';
					}

					if (!$fieldsService->saveField($field, false))
					{
						throw new Exception(Craft::t('app', 'An error occurred while saving this Matrix block type.'));
					}

					$field->required = $field->required;
					$field->sortOrder = ++$sortOrder;

					$fieldLayoutFields[] = $field;
				}

				$contentService->fieldContext        = $originalFieldContext;
				$contentService->fieldColumnPrefix   = $originalFieldColumnPrefix;
				$fieldsService->oldFieldColumnPrefix = $originalOldFieldColumnPrefix;

				$fieldLayoutTab = new FieldLayoutTabModel();
				$fieldLayoutTab->name = 'Content';
				$fieldLayoutTab->sortOrder = 1;
				$fieldLayoutTab->setFields($fieldLayoutFields);

				$fieldLayout = new FieldLayoutModel();
				$fieldLayout->type = MatrixBlock::className();
				$fieldLayout->setTabs([$fieldLayoutTab]);
				$fieldLayout->setFields($fieldLayoutFields);

				$fieldsService->saveLayout($fieldLayout);

				// Update the block type model & record with our new field layout ID
				$blockType->setFieldLayout($fieldLayout);
				$blockType->fieldLayoutId = $fieldLayout->id;
				$blockTypeRecord->fieldLayoutId = $fieldLayout->id;

				// Update the block type with the field layout ID
				$blockTypeRecord->save(false);

				if (!$isNewBlockType)
				{
					// Delete the old field layout
					$fieldsService->deleteLayoutById($oldBlockType->fieldLayoutId);
				}

				if ($transaction !== null)
				{
					$transaction->commit();
				}
			}
			catch (\Exception $e)
			{
				if ($transaction !== null)
				{
					$transaction->rollback();
				}

				throw $e;
			}

			return true;
		}
		else
		{
			return false;
		}
	}

	/**
	 * Deletes a block type.
	 *
	 * @param MatrixBlockTypeModel $blockType The block type.
	 *
	 * @throws \Exception
	 * @return bool Whether the block type was deleted successfully.
	 */
	public function deleteBlockType(MatrixBlockTypeModel $blockType)
	{
		$transaction = Craft::$app->getDb()->getTransaction() === null ? Craft::$app->getDb()->beginTransaction() : null;
		try
		{
			// First delete the blocks of this type
			$blockIds = (new Query())
				->select('id')
				->from('{{%matrixblocks}}')
				->where(['typeId' => $blockType->id])
				->column();

			$this->deleteBlockById($blockIds);

			// Now delete the block type fields
			$originalFieldColumnPrefix = Craft::$app->getContent()->fieldColumnPrefix;
			Craft::$app->getContent()->fieldColumnPrefix = 'field_'.$blockType->handle.'_';

			foreach ($blockType->getFields() as $field)
			{
				Craft::$app->getFields()->deleteField($field);
			}

			Craft::$app->getContent()->fieldColumnPrefix = $originalFieldColumnPrefix;

			// Delete the field layout
			Craft::$app->getFields()->deleteLayoutById($blockType->fieldLayoutId);

			// Finally delete the actual block type
			$affectedRows = Craft::$app->getDb()->createCommand()->delete('{{%matrixblocktypes}}', ['id' => $blockType->id])->execute();

			if ($transaction !== null)
			{
				$transaction->commit();
			}

			return (bool) $affectedRows;
		}
		catch (\Exception $e)
		{
			if ($transaction !== null)
			{
				$transaction->rollback();
			}

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
	 * @return bool Whether the settings validated.
	 */
	public function validateFieldSettings(MatrixField $matrixField)
	{
		$validates = true;

		$this->_uniqueBlockTypeAndFieldHandles = [];

		$uniqueAttributes      = ['name', 'handle'];
		$uniqueAttributeValues = [];

		foreach ($matrixField->getBlockTypes() as $blockType)
		{
			if (!$this->validateBlockType($blockType, false))
			{
				// Don't break out of the loop because we still want to get validation errors for the remaining block
				// types.
				$validates = false;
			}

			// Do our own unique name/handle validation, since the DB-based validation can't be trusted when saving
			// multiple records at once
			foreach ($uniqueAttributes as $attribute)
			{
				$value = $blockType->$attribute;

				if ($value && (!isset($uniqueAttributeValues[$attribute]) || !in_array($value, $uniqueAttributeValues[$attribute])))
				{
					$uniqueAttributeValues[$attribute][] = $value;
				}
				else
				{
					$blockType->addError($attribute, Craft::t('app', '{attribute} "{value}" has already been taken.', [
						'attribute' => $blockType->getAttributeLabel($attribute),
						'value'     => HtmlHelper::encode($value)
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
	 * @param bool        $validate Whether the settings should be validated before being saved.
	 *
	 * @throws \Exception
	 * @return bool Whether the settings saved successfully.
	 */
	public function saveSettings(MatrixField $matrixField, $validate = true)
	{
		if (!$validate || $this->validateFieldSettings($matrixField))
		{
			$transaction = Craft::$app->getDb()->getTransaction() === null ? Craft::$app->getDb()->beginTransaction() : null;
			try
			{
				// Create the content table first since the block type fields will need it
				$oldContentTable = $this->getContentTableName($matrixField, true);
				$newContentTable = $this->getContentTableName($matrixField);

				// Do we need to create/rename the content table?
				if (!Craft::$app->getDb()->tableExists($newContentTable))
				{
					if ($oldContentTable && Craft::$app->getDb()->tableExists($oldContentTable))
					{
						MigrationHelper::renameTable($oldContentTable, $newContentTable);
					}
					else
					{
						$this->_createContentTable($newContentTable);
					}
				}

				// Delete the old block types first, in case there's a handle conflict with one of the new ones
				$oldBlockTypes = $this->getBlockTypesByFieldId($matrixField->id);
				$oldBlockTypesById = [];

				foreach ($oldBlockTypes as $blockType)
				{
					$oldBlockTypesById[$blockType->id] = $blockType;
				}

				foreach ($matrixField->getBlockTypes() as $blockType)
				{
					if (!$blockType->isNew())
					{
						unset($oldBlockTypesById[$blockType->id]);
					}
				}

				foreach ($oldBlockTypesById as $blockType)
				{
					$this->deleteBlockType($blockType);
				}

				// Save the new ones
				$sortOrder = 0;

				$originalContentTable = Craft::$app->getContent()->contentTable;
				Craft::$app->getContent()->contentTable = $newContentTable;

				foreach ($matrixField->getBlockTypes() as $blockType)
				{
					$sortOrder++;
					$blockType->fieldId = $matrixField->id;
					$blockType->sortOrder = $sortOrder;
					$this->saveBlockType($blockType, false);
				}

				Craft::$app->getContent()->contentTable = $originalContentTable;

				if ($transaction !== null)
				{
					$transaction->commit();
				}

				// Update our cache of this field's block types
				$this->_blockTypesByFieldId[$matrixField->id] = $matrixField->getBlockTypes();

				return true;
			}
			catch (\Exception $e)
			{
				if ($transaction !== null)
				{
					$transaction->rollback();
				}

				throw $e;
			}
		}
		else
		{
			return false;
		}
	}

	/**
	 * Deletes a Matrix field.
	 *
	 * @param MatrixField $matrixField The Matrix field.
	 *
	 * @throws \Exception
	 * @return bool Whether the field was deleted successfully.
	 */
	public function deleteMatrixField(MatrixField $matrixField)
	{
		$transaction = Craft::$app->getDb()->getTransaction() === null ? Craft::$app->getDb()->beginTransaction() : null;
		try
		{
			$originalContentTable = Craft::$app->getContent()->contentTable;
			$contentTable = $this->getContentTableName($matrixField);
			Craft::$app->getContent()->contentTable = $contentTable;

			// Delete the block types
			$blockTypes = $this->getBlockTypesByFieldId($matrixField->id);

			foreach ($blockTypes as $blockType)
			{
				$this->deleteBlockType($blockType);
			}

			// Drop the content table
			Craft::$app->getDb()->createCommand()->dropTable($contentTable)->execute();

			Craft::$app->getContent()->contentTable = $originalContentTable;

			if ($transaction !== null)
			{
				$transaction->commit();
			}

			return true;
		}
		catch (\Exception $e)
		{
			if ($transaction !== null)
			{
				$transaction->rollback();
			}

			throw $e;
		}
	}

	/**
	 * Returns the content table name for a given Matrix field.
	 *
	 * @param MatrixField $matrixField  The Matrix field.
	 * @param bool        $useOldHandle Whether the method should use the field’s old handle when determining the table
	 *                                  name (e.g. to get the existing table name, rather than the new one).
	 *
	 * @return string|false The table name, or `false` if $useOldHandle was set to `true` and there was no old handle.
	 */
	public function getContentTableName(MatrixField $matrixField, $useOldHandle = false)
	{
		$name = '';

		do
		{
			if ($useOldHandle)
			{
				if (!$matrixField->oldHandle)
				{
					return false;
				}

				$handle = $matrixField->oldHandle;
			}
			else
			{
				$handle = $matrixField->handle;
			}

			$name = '_'.StringHelper::toLowerCase($handle).$name;
		}
		while ($matrixField = $this->getParentMatrixField($matrixField));

		return '{{%matrixcontent'.$name.'}}';
	}

	/**
	 * Returns a block by its ID.
	 *
	 * @param int    $blockId  The Matrix block’s ID.
	 * @param string $localeId The locale ID to return.
	 *                         Defaults to [[\craft\app\web\Application::language `Craft::$app->language`]].
	 *
	 * @return MatrixBlock|null The Matrix block, or `null` if it didn’t exist.
	 */
	public function getBlockById($blockId, $localeId = null)
	{
		return Craft::$app->getElements()->getElementById($blockId, MatrixBlock::className(), $localeId);
	}

	/**
	 * Validates a block.
	 *
	 * If the block doesn’t validate, any validation errors will be stored on the block.
	 *
	 * @param MatrixBlock $block The Matrix block to validate.
	 *
	 * @return bool Whether the block validated.
	 */
	public function validateBlock(MatrixBlock $block)
	{
		$block->clearErrors();

		$blockRecord = $this->_getBlockRecord($block);

		$blockRecord->fieldId   = $block->fieldId;
		$blockRecord->ownerId   = $block->ownerId;
		$blockRecord->typeId    = $block->typeId;
		$blockRecord->sortOrder = $block->sortOrder;

		$blockRecord->validate();
		$block->addErrors($blockRecord->getErrors());

		$originalFieldContext = Craft::$app->getContent()->fieldContext;
		Craft::$app->getContent()->fieldContext = 'matrixBlockType:'.$block->typeId;

		if (!Craft::$app->getContent()->validateContent($block))
		{
			$block->addErrors($block->getContent()->getErrors());
		}

		Craft::$app->getContent()->fieldContext = $originalFieldContext;

		return !$block->hasErrors();
	}

	/**
	 * Saves a block.
	 *
	 * @param MatrixBlock $block    The Matrix block.
	 * @param bool        $validate Whether the block should be validated before being saved.
	 *                              Defaults to `true`.
	 *
	 * @throws \Exception
	 * @return bool Whether the block was saved successfully.
	 */
	public function saveBlock(MatrixBlock $block, $validate = true)
	{
		if (!$validate || $this->validateBlock($block))
		{
			$blockRecord = $this->_getBlockRecord($block);
			$isNewBlock = $blockRecord->getIsNewRecord();

			$blockRecord->fieldId     = $block->fieldId;
			$blockRecord->ownerId     = $block->ownerId;
			$blockRecord->ownerLocale = $block->ownerLocale;
			$blockRecord->typeId      = $block->typeId;
			$blockRecord->sortOrder   = $block->sortOrder;

			$transaction = Craft::$app->getDb()->getTransaction() === null ? Craft::$app->getDb()->beginTransaction() : null;
			try
			{
				if (Craft::$app->getElements()->saveElement($block, false))
				{
					if ($isNewBlock)
					{
						$blockRecord->id = $block->id;
					}

					$blockRecord->save(false);

					if ($transaction !== null)
					{
						$transaction->commit();
					}

					return true;
				}
			}
			catch (\Exception $e)
			{
				if ($transaction !== null)
				{
					$transaction->rollback();
				}

				throw $e;
			}
		}

		return false;
	}

	/**
	 * Deletes a block(s) by its ID.
	 *
	 * @param int|array $blockIds The Matrix block ID(s).
	 *
	 * @return bool Whether the block(s) were deleted successfully.
	 */
	public function deleteBlockById($blockIds)
	{
		if (!$blockIds)
		{
			return false;
		}

		if (!is_array($blockIds))
		{
			$blockIds = [$blockIds];
		}

		// Tell the browser to forget about these
		Craft::$app->getSession()->addJsResourceFlash('js/MatrixInput.js');

		foreach ($blockIds as $blockId)
		{
			Craft::$app->getSession()->addJsFlash('Craft.MatrixInput.forgetCollapsedBlockId('.$blockId.');');
		}

		// Pass this along to the Elements service for the heavy lifting.
		return Craft::$app->getElements()->deleteElementById($blockIds);
	}

	/**
	 * Saves a Matrix field.
	 *
	 * @param MatrixField $field The Matrix field
	 * @param ElementInterface|Element $owner The element the field is associated with
	 *
	 * @throws \Exception
	 * @return bool Whether the field was saved successfully.
	 */
	public function saveField(MatrixField $field, ElementInterface $owner)
	{
		$handle = $field->handle;
		$blocks = $owner->getContent()->$handle;

		if ($blocks === null)
		{
			return true;
		}

		if ($blocks instanceof MatrixBlockQuery)
		{
			$blocks = $blocks->getResult();
		}
		else
		{
			$blocks = [];
		}

		$transaction = Craft::$app->getDb()->getTransaction() === null ? Craft::$app->getDb()->beginTransaction() : null;
		try
		{
			// First thing's first. Let's make sure that the blocks for this field/owner respect the field's translation
			// setting
			$this->_applyFieldTranslationSetting($owner, $field, $blocks);

			$blockIds          = [];
			$collapsedBlockIds = [];

			foreach ($blocks as $block)
			{
				$block->ownerId = $owner->id;
				$block->ownerLocale = ($field->translatable ? $owner->locale : null);

				$this->saveBlock($block, false);

				$blockIds[] = $block->id;

				// Tell the browser to collapse this block?
				if ($block->collapsed)
				{
					$collapsedBlockIds[] = $block->id;
				}
			}

			// Get the IDs of blocks that are row deleted
			$deletedBlockConditions = ['and',
				'ownerId = :ownerId',
				'fieldId = :fieldId',
				['not in', 'id', $blockIds]
			];

			$deletedBlockParams = [
				':ownerId' => $owner->id,
				':fieldId' => $field->id
			];

			if ($field->translatable)
			{
				$deletedBlockConditions[] = 'ownerLocale  = :ownerLocale';
				$deletedBlockParams[':ownerLocale'] = $owner->locale;
			}

			$deletedBlockIds = (new Query())
				->select('id')
				->from('{{%matrixblocks}}')
				->where($deletedBlockConditions, $deletedBlockParams)
				->column();

			$this->deleteBlockById($deletedBlockIds);

			if ($transaction !== null)
			{
				$transaction->commit();
			}
		}
		catch (\Exception $e)
		{
			if ($transaction !== null)
			{
				$transaction->rollback();
			}

			throw $e;
		}

		// Tell the browser to collapse any new block IDs
		if ($collapsedBlockIds)
		{
			Craft::$app->getSession()->addJsResourceFlash('js/MatrixInput.js');

			foreach ($collapsedBlockIds as $blockId)
			{
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
		if (!isset($this->_parentMatrixFields) || !array_key_exists($matrixField->id, $this->_parentMatrixFields))
		{
			// Does this Matrix field belong to another one?
			$parentMatrixFieldId = (new Query())
				->select('fields.id')
				->from('{{%fields}} fields')
				->innerJoin('{{%matrixblocktypes}} blocktypes', 'blocktypes.fieldId = fields.id')
				->innerJoin('{{%fieldlayoutfields}} fieldlayoutfields', 'fieldlayoutfields.layoutId = blocktypes.fieldLayoutId')
				->where('fieldlayoutfields.fieldId = :matrixFieldId', [':matrixFieldId' => $matrixField->id])
				->scalar();

			if ($parentMatrixFieldId)
			{
				$this->_parentMatrixFields[$matrixField->id] = Craft::$app->getFields()->getFieldById($parentMatrixFieldId);
			}
			else
			{
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
			->select(['id', 'fieldId', 'fieldLayoutId', 'name', 'handle', 'sortOrder'])
			->from('{{%matrixblocktypes}}')
			->orderBy('sortOrder');
	}

	/**
	 * Returns a block type record by its ID or creates a new one.
	 *
	 * @param MatrixBlockTypeModel $blockType
	 *
	 * @throws Exception
	 * @return MatrixBlockTypeRecord
	 */
	private function _getBlockTypeRecord(MatrixBlockTypeModel $blockType)
	{
		if (!$blockType->isNew())
		{
			$blockTypeId = $blockType->id;

			if (!isset($this->_blockTypeRecordsById) || !array_key_exists($blockTypeId, $this->_blockTypeRecordsById))
			{
				$this->_blockTypeRecordsById[$blockTypeId] = MatrixBlockTypeRecord::findOne($blockTypeId);

				if (!$this->_blockTypeRecordsById[$blockTypeId])
				{
					throw new Exception(Craft::t('app', 'No block type exists with the ID “{id}”.', ['id' => $blockTypeId]));
				}
			}

			return $this->_blockTypeRecordsById[$blockTypeId];
		}
		else
		{
			return new MatrixBlockTypeRecord();
		}
	}

	/**
	 * Returns a block record by its ID or creates a new one.
	 *
	 * @param MatrixBlock $block
	 *
	 * @throws Exception
	 * @return MatrixBlockRecord
	 */
	private function _getBlockRecord(MatrixBlock $block)
	{
		$blockId = $block->id;

		if ($blockId)
		{
			if (!isset($this->_blockRecordsById) || !array_key_exists($blockId, $this->_blockRecordsById))
			{
				$this->_blockRecordsById[$blockId] = MatrixBlockRecord::find()
					->where(['id' => $blockId])
					->with('element')
					->one();

				if (!$this->_blockRecordsById[$blockId])
				{
					throw new Exception(Craft::t('app', 'No block exists with the ID “{id}”.', ['id' => $blockId]));
				}
			}

			return $this->_blockRecordsById[$blockId];
		}
		else
		{
			return new MatrixBlockRecord();
		}
	}

	/**
	 * Creates the content table for a Matrix field.
	 *
	 * @param string $name
	 *
	 * @return null
	 */
	private function _createContentTable($name)
	{
		$db = Craft::$app->getDb();
		$db->createCommand()->createTable($name, [
			'elementId' => 'int NOT NULL',
			'locale'    => 'char(12) COLLATE utf8_unicode_ci NOT NULL'
		])->execute();

		$db->createCommand()->createIndex($db->getIndexName($name, 'elementId,locale'), $name, 'elementId,locale', true)->execute();
		$db->createCommand()->addForeignKey($db->getForeignKeyName($name, 'elementId'), $name, 'elementId', '{{%elements}}', 'id', 'CASCADE', null)->execute();
		$db->createCommand()->addForeignKey($db->getForeignKeyName($name, 'locale'), $name, 'locale', '{{%locales}}', 'locale', 'CASCADE', 'CASCADE')->execute();
	}

	/**
	 * Applies the field's translation setting to a set of blocks.
	 *
	 * @param ElementInterface $owner
	 * @param MatrixField      $field
	 * @param array            $blocks
	 *
	 * @return null
	 */
	private function _applyFieldTranslationSetting($owner, $field, $blocks)
	{
		// Does it look like any work is needed here?
		$applyNewTranslationSetting = false;

		foreach ($blocks as $block)
		{
			if ($block->id && (
				($field->translatable && !$block->ownerLocale) ||
				(!$field->translatable && $block->ownerLocale)
			))
			{
				$applyNewTranslationSetting = true;
				break;
			}
		}

		if ($applyNewTranslationSetting)
		{
			// Get all of the blocks for this field/owner that use the other locales, whose ownerLocale attribute is set
			// incorrectly
			$blocksInOtherLocales = [];

			$query = MatrixBlock::find()
				->fieldId($field->id)
				->ownerId($owner->id)
				->status(null)
				->localeEnabled(false)
				->limit(null);

			if ($field->translatable)
			{
				$query->ownerLocale(':empty:');
			}

			foreach (Craft::$app->getI18n()->getSiteLocaleIds() as $localeId)
			{
				if ($localeId == $owner->locale)
				{
					continue;
				}

				$query->locale($localeId);

				if (!$field->translatable)
				{
					$query->ownerLocale($localeId);
				}

				$blocksInOtherLocale = $query->all();

				if ($blocksInOtherLocale)
				{
					$blocksInOtherLocales[$localeId] = $blocksInOtherLocale;
				}
			}

			if ($blocksInOtherLocales)
			{
				if ($field->translatable)
				{
					$newBlockIds = [];

					// Duplicate the other-locale blocks so each locale has their own unique set of blocks
					foreach ($blocksInOtherLocales as $localeId => $blocksInOtherLocale)
					{
						foreach ($blocksInOtherLocale as $blockInOtherLocale)
						{
							$originalBlockId = $blockInOtherLocale->id;

							$blockInOtherLocale->id = null;
							$blockInOtherLocale->getContent()->id = null;
							$blockInOtherLocale->ownerLocale = $localeId;
							$this->saveBlock($blockInOtherLocale, false);

							$newBlockIds[$originalBlockId][$localeId] = $blockInOtherLocale->id;
						}
					}

					// Duplicate the relations, too.  First by getting all of the existing relations for the original
					// blocks
					$relations = (new Query())
						->select(['fieldId', 'sourceId', 'sourceLocale', 'targetId', 'sortOrder'])
						->from('{{%relations}}')
						->where(['in', 'sourceId', array_keys($newBlockIds)])
						->all();

					if ($relations)
					{
						// Now duplicate each one for the other locales' new blocks
						$rows = [];

						foreach ($relations as $relation)
						{
							$originalBlockId = $relation['sourceId'];

							// Just to be safe...
							if (isset($newBlockIds[$originalBlockId]))
							{
								foreach ($newBlockIds[$originalBlockId] as $localeId => $newBlockId)
								{
									$rows[] = [$relation['fieldId'], $newBlockId, $relation['sourceLocale'], $relation['targetId'], $relation['sortOrder']];
								}
							}
						}

						Craft::$app->getDb()->createCommand()->batchInsert(
							'relations',
							['fieldId', 'sourceId', 'sourceLocale', 'targetId', 'sortOrder'],
							$rows
						)->execute();
					}
				}
				else
				{
					// Delete all of these blocks
					$blockIdsToDelete = [];

					foreach ($blocksInOtherLocales as $localeId => $blocksInOtherLocale)
					{
						foreach ($blocksInOtherLocale as $blockInOtherLocale)
						{
							$blockIdsToDelete[] = $blockInOtherLocale->id;
						}
					}

					$this->deleteBlockById($blockIdsToDelete);
				}
			}
		}
	}
}
