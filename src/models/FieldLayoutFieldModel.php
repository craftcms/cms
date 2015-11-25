<?php
namespace Craft;

/**
 * Field layout field model class.
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://craftcms.com/license Craft License Agreement
 * @see       http://craftcms.com
 * @package   craft.app.models
 * @since     1.0
 */
class FieldLayoutFieldModel extends BaseModel
{
	// Properties
	// =========================================================================

	/**
	 * @var
	 */
	private $_layout;

	// Public Methods
	// =========================================================================

	/**
	 * Returns the field’s layout.
	 *
	 * @return FieldLayoutModel|null The field’s layout.
	 */
	public function getLayout()
	{
		if (!isset($this->_layout))
		{
			if ($this->layoutId)
			{
				$this->_layout = craft()->fields->getLayoutById($this->layoutId);
			}
			else
			{
				$this->_layout = false;
			}
		}

		if ($this->_layout)
		{
			return $this->_layout;
		}
	}

	/**
	 * Sets the field’s layout.
	 *
	 * @param FieldLayoutModel $layout The field’s layout.
	 *
	 * @return null
	 */
	public function setLayout(FieldLayoutModel $layout)
	{
		$this->_layout = $layout;
	}

	/**
	 * Returns the associated field.
	 *
	 * @return FieldModel|null The associated field.
	 */
	public function getField()
	{
		if ($this->fieldId)
		{
			return craft()->fields->getFieldById($this->fieldId);
		}
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
			'id'        => AttributeType::Number,
			'layoutId'  => AttributeType::Number,
			'tabId'     => AttributeType::Number,
			'fieldId'   => AttributeType::Name,
			'required'  => AttributeType::Bool,
			'sortOrder' => AttributeType::SortOrder,
		);
	}
}
