<?php
namespace Craft;

/**
 * Matrix block model class
 */
class MatrixBlockModel extends BaseElementModel
{
	protected $elementType = ElementType::MatrixBlock;
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
	 * Returns the block type.
	 *
	 * @return MatrixBlockTypeModel|null
	 */
	public function getType()
	{
		if ($this->typeId)
		{
			return craft()->matrix->getBlockTypeById($this->typeId);
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
	 * Returns the name of the table this element's content is stored in.
	 *
	 * @return string
	 */
	public function getContentTable()
	{
		$matrixField = craft()->fields->getFieldById($this->fieldId);
		return craft()->matrix->getContentTableName($matrixField);
	}

	/**
	 * Returns the field column prefix this element's content uses.
	 *
	 * @return string
	 */
	public function getFieldColumnPrefix()
	{
		return 'field_'.$this->getType()->handle.'_';
	}

	/**
	 * Returns the field context this element's content uses.
	 *
	 * @access protected
	 * @return string
	 */
	public function getFieldContext()
	{
		return 'matrixBlockType:'.$this->typeId;
	}
}
