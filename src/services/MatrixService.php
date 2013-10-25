<?php
namespace Craft;

/**
 *
 */
class MatrixService extends BaseApplicationComponent
{
	private $_recordTypesById;
	private $_recordTypeRecordsById;
	private $_recordRecordsById;
	private $_uniqueRecordTypeAndFieldHandles;
	private $_parentMatrixFields;

	/**
	 * Returns the record types for a given Matrix field.
	 *
	 * @param int $fieldId
	 * @param string|null $indexBy
	 * @return array
	 */
	public function getRecordTypesByFieldId($fieldId, $indexBy = null)
	{
		$recordTypeRecords = MatrixRecordTypeRecord::model()->ordered()->findAllByAttributes(array(
			'fieldId' => $fieldId
		));

		$recordTypes = MatrixRecordTypeModel::populateModels($recordTypeRecords);

		if (!$indexBy)
		{
			return $recordTypes;
		}
		else
		{
			$indexedRecordTypes = array();

			foreach ($recordTypes as $recordType)
			{
				$indexedRecordTypes[$recordType->$indexBy] = $recordType;
			}

			return $indexedRecordTypes;
		}
	}

	/**
	 * Returns a record type by its ID.
	 *
	 * @param int $recordTypeId
	 * @return MatrixRecordTypeModel|null
	 */
	public function getRecordTypeById($recordTypeId)
	{
		if (!isset($this->_recordTypesById) || !array_key_exists($recordTypeId, $this->_recordTypesById))
		{
			$recordTypeRecord = MatrixRecordTypeRecord::model()->findById($recordTypeId);

			if ($recordTypeRecord)
			{
				$this->_recordTypesById[$recordTypeId] = MatrixRecordTypeModel::populateModel($recordTypeRecord);
			}
			else
			{
				$this->_recordTypesById[$recordTypeId] = null;
			}
		}

		return $this->_recordTypesById[$recordTypeId];
	}

	/**
	 * Validates a record type.
	 *
	 * @param MatrixRecordTypeModel $recordType
	 * @return bool
	 */
	public function validateRecordType(MatrixRecordTypeModel $recordType)
	{
		$validates = true;

		$recordTypeRecord = $this->_getRecordTypeRecord($recordType);

		$recordTypeRecord->fieldId = $recordType->fieldId;
		$recordTypeRecord->name    = $recordType->name;
		$recordTypeRecord->handle  = $recordType->handle;

		if (!$recordTypeRecord->validate())
		{
			$validates = false;
			$recordType->addErrors($recordTypeRecord->getErrors());
		}

		// Can't validate multiple new rows at once so we'll need to give these a temporary context
		// to avioid false unique handle validation errors, and just validate those manually.
		$originalFieldContext = craft()->content->fieldContext;
		craft()->content->fieldContext = StringHelper::randomString(10);

		foreach ($recordType->getFields() as $field)
		{
			craft()->fields->validateField($field);

			// Make sure the record type handle + field handle combo is unique for the whole field.
			// This prevents us from worying about content column conflicts like "a" + "b_c" == "a_b" + "c".
			if ($recordType->handle && $field->handle)
			{
				$recordTypeAndFieldHandle = $recordType->handle.'_'.$field->handle;

				if (in_array($recordTypeAndFieldHandle, $this->_uniqueRecordTypeAndFieldHandles))
				{
					// This error *might* not be entirely accurate, but it's such an edge case
					// that it's probably better for the error to be worded for the common problem
					// (two duplicate handles within the same record type).
					$error = Craft::t('{attribute} "{value}" has already been taken.', array(
						'attribute' => Craft::t('Handle'),
						'value' => $field->handle
					));

					$field->addError('handle', $error);
				}
				else
				{
					$this->_uniqueRecordTypeAndFieldHandles[] = $recordTypeAndFieldHandle;
				}
			}

			if ($field->hasErrors())
			{
				$recordType->hasFieldErrors = true;
				$validates = false;
			}
		}

		craft()->content->fieldContext = $originalFieldContext;

		return $validates;
	}

	/**
	 * Saves a record type.
	 *
	 * @param MatrixRecordTypeModel $recordType
	 * @param bool $validate
	 * @return bool
	 */
	public function saveRecordType(MatrixRecordTypeModel $recordType, $validate = true)
	{
		if (!$validate || $this->validateRecordType($recordType))
		{
			$transaction = craft()->db->getCurrentTransaction() === null ? craft()->db->beginTransaction() : null;
			try
			{
				// Get the record type record
				$recordTypeRecord = $this->_getRecordTypeRecord($recordType);
				$isNewRecordType = $recordType->isNew();

				if (!$isNewRecordType)
				{
					$oldRecordType = MatrixRecordTypeModel::populateModel($recordTypeRecord);

					$oldFieldsById = array();

					foreach ($oldRecordType->getFields() as $field)
					{
						$oldFieldsById[$field->id] = $field;
					}
				}

				// Set the basic info on it
				$recordTypeRecord->fieldId   = $recordType->fieldId;
				$recordTypeRecord->name      = $recordType->name;
				$recordTypeRecord->handle    = $recordType->handle;
				$recordTypeRecord->sortOrder = $recordType->sortOrder;

				// Save it, minus the field layout for now
				$recordTypeRecord->save(false);

				if ($isNewRecordType)
				{
					// Set the new ID on the model
					$recordType->id = $recordTypeRecord->id;
				}

				// Save the fields and field layout
				$fieldLayoutFields = array();
				$sortOrder = 0;

				$originalFieldContext = craft()->content->fieldContext;
				craft()->content->fieldContext = 'matrixRecordType:'.$recordType->id;

				$originalFieldColumnPrefix = craft()->content->fieldColumnPrefix;
				craft()->content->fieldColumnPrefix = 'field_'.$recordType->handle.'_';

				if (!$isNewRecordType)
				{
					$originalOldFieldColumnPrefix = craft()->fields->oldFieldColumnPrefix;
					craft()->fields->oldFieldColumnPrefix = 'field_'.$oldRecordType->handle.'_';
				}

				foreach ($recordType->getFields() as $field)
				{
					if (!$isNewRecordType && !$field->isNew())
					{
						unset($oldFieldsById[$field->id]);
					}

					if (!craft()->fields->saveField($field))
					{
						throw new Exception(Craft::t('An error occurred while saving this Matrix record type.'));
					}

					$sortOrder++;
					$fieldLayoutFields[] = array(
						'fieldId'   => $field->id,
						'required'  => $field->required,
						'sortOrder' => $sortOrder
					);
				}

				craft()->content->fieldContext = $originalFieldContext;
				craft()->content->fieldColumnPrefix = $originalFieldColumnPrefix;

				if (!$isNewRecordType)
				{
					craft()->fields->oldFieldColumnPrefix = $originalOldFieldColumnPrefix;
				}

				$fieldLayout = new FieldLayoutModel();
				$fieldLayout->type = ElementType::MatrixRecord;
				$fieldLayout->setFields($fieldLayoutFields);
				craft()->fields->saveLayout($fieldLayout, false);

				// Update the record type model & record with our new field layout ID
				$recordType->setFieldLayout($fieldLayout);
				$recordType->fieldLayoutId = $fieldLayout->id;
				$recordTypeRecord->fieldLayoutId = $fieldLayout->id;

				// Update the record type with the field layout ID
				$recordTypeRecord->save(false);

				if (!$isNewRecordType)
				{
					// Drop the old fields
					foreach ($oldFieldsById as $field)
					{
						craft()->fields->deleteField($field);
					}

					// Delete the old field layout
					craft()->fields->deleteLayoutById($oldRecordType->fieldLayoutId);
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
	 * Deletes a record type.
	 *
	 * @param MatrixRecordTypeModel $recordType
	 * @return bool
	 */
	public function deleteRecordType(MatrixRecordTypeModel $recordType)
	{
		$transaction = craft()->db->getCurrentTransaction() === null ? craft()->db->beginTransaction() : null;
		try
		{
			// First delete the records of this type
			$recordIds = craft()->db->createCommand()
				->select('id')
				->from('matrixrecords')
				->where(array('typeId' => $recordType->id))
				->queryColumn();

			craft()->elements->deleteElementById($recordIds);

			// Now delete the record type fields
			$originalFieldColumnPrefix = craft()->content->fieldColumnPrefix;
			craft()->content->fieldColumnPrefix = 'field_'.$recordType->handle.'_';

			foreach ($recordType->getFields() as $field)
			{
				craft()->fields->deleteField($field);
			}

			craft()->content->fieldColumnPrefix = $originalFieldColumnPrefix;

			// Delete the field layout
			craft()->fields->deleteLayoutById($recordType->fieldLayoutId);

			// Finally delete the actual record type
			$affectedRows = craft()->db->createCommand()->delete('matrixrecordtypes', array('id' => $recordType->id));

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
	 * @param MatrixSettingsModel $settings
	 * @return bool
	 */
	public function validateFieldSettings(MatrixSettingsModel $settings)
	{
		$validates = true;

		$this->_uniqueRecordTypeAndFieldHandles = array();

		foreach ($settings->getRecordTypes() as $recordType)
		{
			if (!$this->validateRecordType($recordType))
			{
				// Don't break out of the loop because we still want to get validation errors
				// for the remaining record types.
				$validates = false;
			}
		}

		return $validates;
	}

	/**
	 * Saves a Matrix field's settings.
	 *
	 * @param MatrixSettingsModel $settings
	 * @param bool $validate
	 * @return bool
	 */
	public function saveSettings(MatrixSettingsModel $settings, $validate = true)
	{
		if (!$validate || $this->validateFieldSettings($settings))
		{
			$transaction = craft()->db->getCurrentTransaction() === null ? craft()->db->beginTransaction() : null;
			try
			{
				$matrixField = $settings->getField();

				// Create the content table first since the record type fields will need it
				$oldContentTable = $this->getContentTableName($matrixField, true);
				$newContentTable = $this->getContentTableName($matrixField);

				// Do we need to create/rename the content table?
				if (!craft()->db->tableExists($newContentTable))
				{
					if ($oldContentTable && craft()->db->tableExists($oldContentTable))
					{
						MigrationHelper::renameTable($oldContentTable, $newContentTable);
					}
					else
					{
						$this->_createContentTable($newContentTable);
					}
				}

				// Get the old record types
				$oldRecordTypes = $this->getRecordTypesByFieldId($matrixField->id);
				$oldRecordTypesById = array();

				foreach ($oldRecordTypes as $recordType)
				{
					$oldRecordTypesById[$recordType->id] = $recordType;
				}

				// Save the new ones
				$sortOrder = 0;

				$originalContentTable = craft()->content->contentTable;
				craft()->content->contentTable = $newContentTable;

				foreach ($settings->getRecordTypes() as $recordType)
				{
					if (!$recordType->isNew())
					{
						unset($oldRecordTypesById[$recordType->id]);
					}

					$sortOrder++;
					$recordType->fieldId = $matrixField->id;
					$recordType->sortOrder = $sortOrder;
					$this->saveRecordType($recordType, false);
				}

				// Delete all of the record types that are no longer needed
				foreach ($oldRecordTypesById as $recordType)
				{
					$this->deleteRecordType($recordType);
				}

				craft()->content->contentTable = $originalContentTable;

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
		else
		{
			return false;
		}
	}

	/**
	 * Deletes a Matrix field.
	 *
	 * @param FieldModel $matrixField
	 * @return bool
	 */
	public function deleteMatrixField(FieldModel $matrixField)
	{
		$transaction = craft()->db->getCurrentTransaction() === null ? craft()->db->beginTransaction() : null;
		try
		{
			$originalContentTable = craft()->content->contentTable;
			$contentTable = $this->getContentTableName($matrixField);
			craft()->content->contentTable = $contentTable;

			// Delete the record types
			$recordTypes = $this->getRecordTypesByFieldId($matrixField->id);

			foreach ($recordTypes as $recordType)
			{
				$this->deleteRecordType($recordType);
			}

			// Drop the content table
			craft()->db->createCommand()->dropTable($contentTable);

			craft()->content->contentTable = $originalContentTable;

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
	 * @param FieldModel $matrixField
	 * @param bool $useOldHandle
	 * @return string|false
	 */
	public function getContentTableName(FieldModel $matrixField, $useOldHandle = false)
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

			$name = '_'.strtolower($handle).$name;
		}
		while ($matrixField = $this->getParentMatrixField($matrixField));

		return 'matrixcontent'.$name;
	}

	/**
	 * Validates a record.
	 *
	 * @param MatrixRecordModel $record
	 * @return bool
	 */
	public function validateRecord(MatrixRecordModel $record)
	{
		$record->clearErrors();

		$recordRecord = $this->_getRecordRecord($record);

		$recordRecord->fieldId      = $record->fieldId;
		$recordRecord->ownerId      = $record->ownerId;
		$recordRecord->typeId       = $record->typeId;
		$recordRecord->sortOrder    = $record->sortOrder;

		$recordRecord->validate();
		$record->addErrors($recordRecord->getErrors());

		$originalFieldContext = craft()->content->fieldContext;
		craft()->content->fieldContext = 'matrixRecordType:'.$record->typeId;

		$fieldLayout = $record->getType()->getFieldLayout();
		$content = craft()->content->prepElementContentForSave($record, $fieldLayout);
		$content->validate();
		$record->addErrors($content->getErrors());

		craft()->content->fieldContext = $originalFieldContext;

		return !$record->hasErrors();
	}

	/**
	 * Saves a record.
	 *
	 * @param MatrixRecordModel $record
	 * @param bool              $validate
	 * @throws \Exception
	 * @return bool
	 */
	public function saveRecord(MatrixRecordModel $record, $validate = true)
	{
		if (!$validate || $this->validateRecord($record))
		{
			$transaction = craft()->db->getCurrentTransaction() === null ? craft()->db->beginTransaction() : null;
			try
			{
				$recordRecord = $this->_getRecordRecord($record);
				$isNewRecord = $recordRecord->isNewRecord();

				$recordRecord->fieldId   = $record->fieldId;
				$recordRecord->ownerId   = $record->ownerId;
				$recordRecord->typeId    = $record->typeId;
				$recordRecord->sortOrder = $record->sortOrder;

				if (!$isNewRecord)
				{
					$elementRecord = $recordRecord->element;

					$elementLocaleRecord = ElementLocaleRecord::model()->findByAttributes(array(
						'elementId' => $record->id,
						'locale'    => $record->locale
					));
				}
				else
				{
					$elementRecord = new ElementRecord();
					$elementRecord->type = ElementType::MatrixRecord;
				}

				if (empty($elementLocaleRecord))
				{
					$elementLocaleRecord = new ElementLocaleRecord();
					$elementLocaleRecord->locale = $record->locale;
				}

				// Save the element record first
				$elementRecord->save(false);

				// Now that we have an element ID, save it on the other stuff
				$record->id = $elementRecord->id;
				$recordRecord->id = $record->id;
				$elementLocaleRecord->elementId = $record->id;
				$record->getContent()->elementId = $record->id;

				$recordRecord->save(false);
				$elementLocaleRecord->save(false);

				$originalFieldContext = craft()->content->fieldContext;
				craft()->content->fieldContext = 'matrixRecordType:'.$record->typeId;

				$originalFieldColumnPrefix = craft()->content->fieldColumnPrefix;
				craft()->content->fieldColumnPrefix = 'field_'.$record->getType()->handle.'_';

				craft()->content->saveContent($record->getContent());
				craft()->content->postSaveOperations($record, $record->getContent());

				craft()->content->fieldContext = $originalFieldContext;
				craft()->content->fieldColumnPrefix = $originalFieldColumnPrefix;

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
	 * Saves a Matrix field.
	 *
	 * @param FieldModel $matrixField
	 * @param int        $ownerId
	 * @param array      $records
	 * @throws \Exception
	 * @return bool
	 */
	public function saveField(FieldModel $matrixField, $ownerId, $records)
	{
		$transaction = craft()->db->getCurrentTransaction() === null ? craft()->db->beginTransaction() : null;
		try
		{
			$originalContentTable = craft()->content->contentTable;
			craft()->content->contentTable = $this->getContentTableName($matrixField);

			$recordIds = array();

			foreach ($records as $record)
			{
				// The owner ID might not have been set yet
				$record->ownerId = $ownerId;

				$this->saveRecord($record, false);

				$recordIds[] = $record->id;
			}

			// Get the IDs of records that are row deleted
			$deletedRecordIds = craft()->db->createCommand()
				->select('id')
				->from('matrixrecords')
				->where(array('and',
					'ownerId = :ownerId',
					'fieldId = :fieldId',
					array('not in', 'id', $recordIds)
				), array(
					':ownerId' => $ownerId,
					':fieldId' => $matrixField->id
				))
				->queryColumn();

			craft()->elements->deleteElementById($deletedRecordIds);

			craft()->content->contentTable = $originalContentTable;

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

	/**
	 * Returns the parent Matrix field, if any.
	 *
	 * @param FieldModel       $matrixField
	 * @return FieldModel|null
	 */
	public function getParentMatrixField(FieldModel $matrixField)
	{
		if (!isset($this->_parentMatrixFields) || !array_key_exists($matrixField->id, $this->_parentMatrixFields))
		{
			// Does this Matrix field belong to another one?
			$parentMatrixFieldId = craft()->db->createCommand()
				->select('fields.id')
				->from('fields fields')
				->join('matrixrecordtypes recordtypes', 'recordtypes.fieldId = fields.id')
				->join('fieldlayoutfields fieldlayoutfields', 'fieldlayoutfields.layoutId = recordtypes.fieldLayoutId')
				->where('fieldlayoutfields.fieldId = :matrixFieldId', array(':matrixFieldId' => $matrixField->id))
				->queryScalar();

			if ($parentMatrixFieldId)
			{
				$this->_parentMatrixFields[$matrixField->id] = craft()->fields->getFieldById($parentMatrixFieldId);
			}
			else
			{
				$this->_parentMatrixFields[$matrixField->id] = null;
			}
		}

		return $this->_parentMatrixFields[$matrixField->id];
	}

	/**
	 * Returns a record type record by its ID or creates a new one.
	 *
	 * @access private
	 * @param  MatrixRecordTypeModel $recordType
	 * @throws Exception
	 * @return MatrixRecordTypeRecord
	 */
	private function _getRecordTypeRecord(MatrixRecordTypeModel $recordType)
	{
		if (!$recordType->isNew())
		{
			$recordTypeId = $recordType->id;

			if (!isset($this->_recordTypeRecordsById) || !array_key_exists($recordTypeId, $this->_recordTypeRecordsById))
			{
				$this->_recordTypeRecordsById[$recordTypeId] = MatrixRecordTypeRecord::model()->findById($recordTypeId);

				if (!$this->_recordTypeRecordsById[$recordTypeId])
				{
					throw new Exception(Craft::t('No record type exists with the ID “{id}”', array('id' => $recordTypeId)));
				}
			}

			return $this->_recordTypeRecordsById[$recordTypeId];
		}
		else
		{
			return new MatrixRecordTypeRecord();
		}
	}

	/**
	 * Returns a record record by its ID or creates a new one.
	 *
	 * @access private
	 * @param  MatrixRecordModel $record
	 * @throws Exception
	 * @return MatrixRecordRecord
	 */
	private function _getRecordRecord(MatrixRecordModel $record)
	{
		$recordId = $record->id;

		if ($recordId)
		{
			if (!isset($this->_recordRecordsById) || !array_key_exists($recordId, $this->_recordRecordsById))
			{
				$this->_recordRecordsById[$recordId] = MatrixRecordRecord::model()->with('element')->findById($recordId);

				if (!$this->_recordRecordsById[$recordId])
				{
					throw new Exception(Craft::t('No record exists with the ID “{id}”', array('id' => $recordId)));
				}
			}

			return $this->_recordRecordsById[$recordId];
		}
		else
		{
			return new MatrixRecordRecord();
		}
	}

	/**
	 * Creates the content table for a Matrix field.
	 *
	 * @access private
	 * @param string $name
	 */
	private function _createContentTable($name)
	{
		craft()->db->createCommand()->createTable($name, array(
			'elementId' => array('column' => ColumnType::Int, 'null' => false),
			'locale'    => array('column' => ColumnType::Locale, 'null' => false)
		));

		craft()->db->createCommand()->createIndex($name, 'elementId,locale', true);
		craft()->db->createCommand()->addForeignKey($name, 'elementId', 'elements', 'id', 'CASCADE', null);
		craft()->db->createCommand()->addForeignKey($name, 'locale', 'locales', 'locale', 'CASCADE', 'CASCADE');
	}
}
