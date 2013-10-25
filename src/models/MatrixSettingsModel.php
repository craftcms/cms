<?php
namespace Craft;

/**
 * Matrix record model class
 */
class MatrixSettingsModel extends BaseModel
{
	private $_matrixField;
	private $_recordTypes;

	/**
	 * Constructor
	 *
	 * @param FieldModel|null $matrixField
	 */
	function __construct(FieldModel $matrixField = null)
	{
		$this->_matrixField = $matrixField;
	}

	/**
	 * Returns the field associated with this.
	 *
	 * @return FieldModel
	 */
	public function getField()
	{
		return $this->_matrixField;
	}

	/**
	 * Returns the record types.
	 *
	 * @return array
	 */
	public function getRecordTypes()
	{
		if (!isset($this->_recordTypes))
		{
			if (!empty($this->_matrixField->id))
			{
				$this->_recordTypes = craft()->matrix->getRecordTypesByFieldId($this->_matrixField->id);
			}
			else
			{
				$this->_recordTypes = array();
			}
		}

		return $this->_recordTypes;
	}

	/**
	 * Sets the record types.
	 *
	 * @param array $recordTypes
	 */
	public function setRecordTypes($recordTypes)
	{
		$this->_recordTypes = $recordTypes;
	}

	/**
	 * Validates the record type settings.
	 *
	 * @param array|null $attributes
	 * @param bool $clearErrors
	 * @return bool
	 */
	public function validate($attributes = null, $clearErrors = true)
	{
		// Enforce $clearErrors without copying code if we don't have to
		$validates = parent::validate($attributes, $clearErrors);

		if (!craft()->matrix->validateFieldSettings($this))
		{
			$validates = false;
		}

		return $validates;
	}
}
