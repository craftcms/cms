<?php
namespace craft\app\models;

use craft\app\enums\ElementType;

/**
 * MatrixBlockType model class.
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://buildwithcraft.com/license Craft License Agreement
 * @see       http://buildwithcraft.com
 * @package   craft.app.models
 * @since     1.3
 */
class MatrixBlockType extends BaseModel
{
	// Traits
	// =========================================================================

	use \craft\app\base\FieldLayoutTrait;

	// Properties
	// =========================================================================

	/**
	 * @var The element type that block types' field layouts should be associated with.
	 */
	private $_fieldLayoutElementType = ElementType::MatrixBlock;

	/**
	 * @var bool
	 */
	public $hasFieldErrors = false;

	/**
	 * @var
	 */
	private $_fields;

	// Public Methods
	// =========================================================================

	/**
	 * Use the block type handle as the string representation.
	 *
	 * @return string
	 */
	public function __toString()
	{
		return $this->handle;
	}

	/**
	 * Returns whether this is a new component.
	 *
	 * @return bool
	 */
	public function isNew()
	{
		return (!$this->id || strncmp($this->id, 'new', 3) === 0);
	}

	/**
	 * Returns the fields associated with this block type.
	 *
	 * @return array
	 */
	public function getFields()
	{
		if (!isset($this->_fields))
		{
			$this->_fields = array();

			$fieldLayoutFields = $this->getFieldLayout()->getFields();

			foreach ($fieldLayoutFields as $fieldLayoutField)
			{
				$field = $fieldLayoutField->getField();
				$field->required = $fieldLayoutField->required;
				$this->_fields[] = $field;
			}
		}

		return $this->_fields;
	}

	/**
	 * Sets the fields associated with this block type.
	 *
	 * @param array $fields
	 *
	 * @return null
	 */
	public function setFields($fields)
	{
		$this->_fields = $fields;
	}

	// Protected Methods
	// =========================================================================

	/**
	 * @inheritDoc BaseModel::defineAttributes()
	 *
	 * @return array
	 */
	protected function defineAttributes()
	{
		return array(
			'id'            => AttributeType::Number,
			'fieldId'       => AttributeType::Number,
			'fieldLayoutId' => AttributeType::String,
			'name'          => AttributeType::String,
			'handle'        => AttributeType::String,
			'sortOrder'     => AttributeType::Number,
		);
	}
}
