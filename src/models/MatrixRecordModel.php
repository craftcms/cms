<?php
namespace Craft;

/**
 * Matrix record model class
 */
class MatrixRecordModel extends BaseElementModel
{
	protected $elementType = ElementType::MatrixRecord;
	private $_owner;

	/**
	 * @access protected
	 * @return array
	 */
	protected function defineAttributes()
	{
		return array_merge(parent::defineAttributes(), array(
			'fieldId'   => AttributeType::Number,
			'ownerId'   => AttributeType::Number,
			'typeId'    => AttributeType::Number,
			'sortOrder' => AttributeType::Number
		));
	}

	/**
	 * Returns the record type.
	 *
	 * @return MatrixRecordTypeModel|null
	 */
	public function getType()
	{
		if ($this->typeId)
		{
			return craft()->matrix->getRecordTypeById($this->typeId);
		}
	}

	/**
	 * Returns the owner.
	 *
	 * @return BaseElementModel|null
	 */
	public function getOwner()
	{
		if (!isset($this->_owner) && $this->ownerId)
		{
			$this->_owner = craft()->elements->getElementById($this->ownerId);

			if (!$this->_owner)
			{
				$this->_owner = false;
			}
		}

		if ($this->_owner)
		{
			return $this->_owner;
		}
	}

	/**
	 * Returns the field with a given handle.
	 *
	 * @access protected
	 * @param string $handle
	 * @return FieldModel|null
	 */
	protected function getFieldByHandle($handle)
	{
		$originalFieldContext = craft()->content->fieldContext;
		craft()->content->fieldContext = 'matrixRecordType:'.$this->typeId;

		$field = craft()->fields->getFieldByHandle($handle);

		craft()->content->fieldContext = $originalFieldContext;

		return $field;
	}

	/**
	 * Returns a ContentModel to be used as this element's content.
	 *
	 * @access protected
	 * @return ContentModel
	 */
	protected function getContentModel()
	{
		$recordType = $this->getType();

		if ($recordType)
		{
			$originalContentTable = craft()->content->contentTable;
			$matrixField = craft()->fields->getFieldById($this->fieldId);
			craft()->content->contentTable = craft()->matrix->getContentTableName($matrixField);

			$originalFieldContentPrefix = craft()->content->fieldColumnPrefix;
			craft()->content->fieldColumnPrefix = 'field_'.$recordType->handle.'_';

			$originalFieldContext = craft()->content->fieldContext;
			craft()->content->fieldContext = 'matrixRecordType:'.$recordType->id;

			$content = parent::getContentModel();

			craft()->content->contentTable = $originalContentTable;
			craft()->content->fieldColumnPrefix = $originalFieldContentPrefix;
			craft()->content->fieldContext = $originalFieldContext;
		}
		else
		{
			$content = parent::getContentModel();
		}

		return $content;
	}
}
