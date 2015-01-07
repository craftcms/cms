<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2013 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\models;

use craft\app\Craft;
use craft\app\enums\AttributeType;
use craft\app\models\FieldLayout as FieldLayoutModel;
use craft\app\models\Field       as FieldModel;

/**
 * FieldLayoutField model class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class FieldLayoutField extends BaseModel
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
				$this->_layout = Craft::$app->fields->getLayoutById($this->layoutId);
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
			return Craft::$app->fields->getFieldById($this->fieldId);
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
		return [
			'id'        => AttributeType::Number,
			'layoutId'  => AttributeType::Number,
			'tabId'     => AttributeType::Number,
			'fieldId'   => AttributeType::Name,
			'required'  => AttributeType::Bool,
			'sortOrder' => AttributeType::SortOrder,
		];
	}
}
