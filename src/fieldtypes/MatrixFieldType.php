<?php
namespace Craft;

/**
 *
 */
class MatrixFieldType extends BaseFieldType
{
	private $_postedSettings;

	/**
	 * Returns the type of field this is.
	 *
	 * @return string
	 */
	public function getName()
	{
		return Craft::t('Matrix');
	}

	/**
	 * Returns the content attribute config.
	 *
	 * @return mixed
	 */
	public function defineContentAttribute()
	{
		return false;
	}

	/**
	 * Returns the field's settings HTML.
	 *
	 * @return string|null
	 */
	public function getSettingsHtml()
	{
		// Get the available field types data
		$fieldTypeInfo = $this->_getFieldTypeInfoForConfigurator();

		craft()->templates->includeJsResource('js/MatrixConfigurator.js');
		craft()->templates->includeJs('new Craft.MatrixConfigurator('.JsonHelper::encode($fieldTypeInfo).', "'.craft()->templates->getNamespace().'");');

		craft()->templates->includeTranslations(
			'What this record type will be called in the CP.',
			'How you’ll refer to this record type in the templates.',
			'Are you sure you want to delete this record type?',
			'This field is required',
			'This field is translatable',
			'Field Type',
			'Are you sure you want to delete this field?'
		);

		$fieldTypeOptions = array();

		foreach (craft()->fields->getAllFieldTypes() as $fieldType)
		{
			$fieldTypeOptions[] = array('label' => $fieldType->getName(), 'value' => $fieldType->getClassHandle());
		}

		return craft()->templates->render('_components/fieldtypes/Matrix/settings', array(
			'recordTypes' => $this->getSettings()->getRecordTypes(),
			'fieldTypes'  => $fieldTypeOptions
		));
	}

	/**
	 * Preps the settings before they're saved to the database.
	 *
	 * @param array $settings
	 * @return array
	 */
	public function prepSettings($settings)
	{
		if ($settings instanceof MatrixSettingsModel)
		{
			return $settings;
		}

		$matrixSettings = new MatrixSettingsModel($this->model);
		$recordTypes = array();

		if (!empty($settings['recordTypes']))
		{
			foreach ($settings['recordTypes'] as $recordTypeId => $recordTypeSettings)
			{
				$recordType = new MatrixRecordTypeModel();
				$recordType->id     = $recordTypeId;
				$recordType->name   = $recordTypeSettings['name'];
				$recordType->handle = $recordTypeSettings['handle'];

				$fields = array();

				if (!empty($recordTypeSettings['fields']))
				{
					foreach ($recordTypeSettings['fields'] as $fieldId => $fieldSettings)
					{
						$field = new FieldModel();
						$field->id           = $fieldId;
						$field->name         = $fieldSettings['name'];
						$field->handle       = $fieldSettings['handle'];
						$field->required     = !empty($fieldSettings['required']);
						$field->translatable = !empty($fieldSettings['translatable']);
						$field->type         = $fieldSettings['type'];

						if (isset($fieldSettings['typesettings']))
						{
							$field->settings = $fieldSettings['typesettings'];
						}

						$fields[] = $field;
					}
				}

				$recordType->setFields($fields);
				$recordTypes[] = $recordType;
			}
		}

		$matrixSettings->setRecordTypes($recordTypes);
		return $matrixSettings;
	}

	/**
	 * Performs any actions after a field is saved.
	 */
	public function onAfterSave()
	{
		craft()->matrix->saveSettings($this->getSettings(), false);
	}

	/**
	 * Performs any actions before a field is deleted.
	 */
	public function onBeforeDelete()
	{
		craft()->matrix->deleteMatrixField($this->model);
	}

	/**
	 * Preps the field value for use.
	 *
	 * @param mixed $value
	 * @return ElementCriteriaModel|array
	 */
	public function prepValue($value)
	{
		// $value will be an array of record data or an empty string if there was a validation error
		// or we're loading a draft/version.
		if (is_array($value))
		{
			return $value;
		}
		else if ($value === '')
		{
			return array();
		}
		else
		{
			$criteria = craft()->elements->getCriteria(ElementType::MatrixRecord);

			// Existing element?
			if (!empty($this->element->id))
			{
				$criteria->ownerId = $this->element->id;
				$criteria->fieldId = $this->model->id;
			}
			else
			{
				$criteria->id = false;
			}

			$criteria->locale = $this->element->locale;

			return $criteria;
		}
	}

	/**
	 * Returns the field's input HTML.
	 *
	 * @param string $name
	 * @param mixed  $value
	 * @return string
	 */
	public function getInputHtml($name, $value)
	{
		$id = craft()->templates->formatInputId($name);

		// Get the record types data
		$recordTypeInfo = $this->_getRecordTypeInfoForInput($name);

		craft()->templates->includeJsResource('js/MatrixInput.js');
		craft()->templates->includeJs('new Craft.MatrixInput(' .
			'"'.craft()->templates->namespaceInputId($id).'", ' .
			JsonHelper::encode($recordTypeInfo).', ' .
			'"'.craft()->templates->namespaceInputName($name).'"' .
		');');

		return craft()->templates->render('_components/fieldtypes/Matrix/input', array(
			'id' => $id,
			'name' => $name,
			'recordTypes' => $this->getSettings()->getRecordTypes(),
			'records' => $value
		));
	}

	/**
	 * Returns the input value as it should be saved to the database.
	 *
	 * @param mixed $data
	 * @return mixed
	 */
	public function prepValueFromPost($data)
	{
		// Get the possible record types for this field
		$recordTypes = craft()->matrix->getRecordTypesByFieldId($this->model->id, 'handle');

		if (!is_array($data))
		{
			return array();
		}

		// Get the old records that are still around
		if (!empty($this->element->id))
		{
			$ownerId = $this->element->id;

			$ids = array();

			foreach (array_keys($data) as $recordId)
			{
				if (is_numeric($recordId))
				{
					$ids[] = $recordId;
				}
			}

			if ($ids)
			{
				$criteria = craft()->elements->getCriteria(ElementType::MatrixRecord);
				$criteria->fieldId = $this->model->id;
				$criteria->ownerId = $ownerId;
				$criteria->id = $ids;
				$criteria->limit = null;
				$criteria->locale = $this->element->locale;
				$oldRecords = $criteria->find();

				// Index them by ID
				$oldRecordsById = array();

				foreach ($oldRecords as $oldRecord)
				{
					$oldRecordsById[$oldRecord->id] = $oldRecord;
				}
			}
		}
		else
		{
			$ownerId = null;
		}

		$records = array();
		$sortOrder = 0;

		foreach ($data as $recordId => $recordData)
		{
			$recordType = $recordTypes[$recordData['type']];

			// Is this new?
			if (strncmp($recordId, 'new', 3) === 0)
			{
				$record = new MatrixRecordModel();
				$record->fieldId = $this->model->id;
				$record->typeId  = $recordType->id;
				$record->ownerId = $ownerId;
				$record->locale  = $this->element->locale;
			}
			else
			{
				if (!isset($oldRecordsById[$recordId]))
				{
					throw new Exception(Craft::t('No record exists with the ID “{recordId}” on the element with the ID “{ownerId}” for the field with the ID “{fieldId}”.', array(
						'recordId' => $recordId,
						'ownerId'  => $ownerId,
						'fieldId'  => $this->model->id
					)));
				}

				$record = $oldRecordsById[$recordId];
			}

			if (isset($recordData['fields']))
			{
				$record->getContent()->setAttributes($recordData['fields']);
			}

			$sortOrder++;
			$record->sortOrder = $sortOrder;

			$records[] = $record;
		}

		return $records;
	}

	/**
	 * Validates the value beyond the checks that were assumed based on the content attribute.
	 *
	 * Returns 'true' or any custom validation errors.
	 *
	 * @param array $records
	 * @return true|string|array
	 */
	public function validate($records)
	{
		$validates = true;

		foreach ($records as $record)
		{
			if (!craft()->matrix->validateRecord($record))
			{
				$validates = false;
			}
		}

		if (!$validates)
		{
			return Craft::t('Correct the errors listed above.');
		}
		else
		{
			return true;
		}
	}

	/**
	 * Performs any additional actions after the element has been saved.
	 */
	public function onAfterElementSave()
	{
		$records = $this->element->getContent()->getAttribute($this->model->handle);
		craft()->matrix->saveField($this->model, $this->element->id, $records);
	}

	/**
	 * Returns the settings model.
	 *
	 * @access protected
	 * @return BaseModel
	 */
	protected function getSettingsModel()
	{
		return new MatrixSettingsModel($this->model);
	}

	/**
	 * Returns info about each field type for the configurator.
	 *
	 * @return array
	 */
	private function _getFieldTypeInfoForConfigurator()
	{
		$fieldTypes = array();

		$originalNamespace = craft()->templates->getNamespace();
		$namespace = craft()->templates->namespaceInputName('recordTypes[__RECORD_TYPE__][fields][__FIELD__][typesettings]', $originalNamespace);
		craft()->templates->setNamespace($namespace);

		foreach (craft()->fields->getAllFieldTypes() as $fieldType)
		{
			$fieldTypeClass = $fieldType->getClassHandle();

			// No Matrix-Inception, sorry buddy.
			if ($fieldTypeClass == 'Matrix')
			{
				continue;
			}

			craft()->templates->startJsBuffer();
			$settingsBodyHtml = craft()->templates->namespaceInputs($fieldType->getSettingsHtml());
			$settingsFootHtml = craft()->templates->clearJsBuffer();

			$fieldTypes[] = array(
				'type'             => $fieldTypeClass,
				'name'             => $fieldType->getName(),
				'settingsBodyHtml' => $settingsBodyHtml,
				'settingsFootHtml' => $settingsFootHtml,
			);
		}

		craft()->templates->setNamespace($originalNamespace);

		return $fieldTypes;
	}

	/**
	 * Returns info about each field type for the configurator.
	 *
	 * @access private
	 * @param string $name
	 * @return array
	 */
	private function _getRecordTypeInfoForInput($name)
	{
		$recordTypes = array();

		$originalNamespace = craft()->templates->getNamespace();
		$namespace = craft()->templates->namespaceInputName($name.'[__RECORD__][fields]', $originalNamespace);
		craft()->templates->setNamespace($namespace);

		foreach ($this->getSettings()->getRecordTypes() as $recordType)
		{
			craft()->templates->startJsBuffer();

			$bodyHtml = craft()->templates->namespaceInputs(craft()->templates->render('_includes/fields', array(
				'namespace' => null,
				'fields' => $recordType->getFieldLayout()->getFields()
			)));

			$footHtml = craft()->templates->clearJsBuffer();

			$recordTypes[$recordType->handle] = array(
				'bodyHtml' => $bodyHtml,
				'footHtml' => $footHtml,
			);
		}

		craft()->templates->setNamespace($originalNamespace);

		return $recordTypes;
	}
}
