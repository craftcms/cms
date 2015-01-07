<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2013 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\models;

use craft\app\Craft;
use craft\app\enums\AttributeType;
use craft\app\models\FieldLayout      as FieldLayoutModel;
use craft\app\models\FieldLayoutField as FieldLayoutFieldModel;

/**
 * FieldLayoutTab model class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class FieldLayoutTab extends BaseModel
{
	// Properties
	// =========================================================================

	/**
	 * @var
	 */
	private $_layout;

	/**
	 * @var
	 */
	private $_fields;

	// Public Methods
	// =========================================================================

	/**
	 * Returns the tab’s layout.
	 *
	 * @return FieldLayoutModel|null The tab’s layout.
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
	 * Sets the tab’s layout.
	 *
	 * @param FieldLayoutModel $layout The tab’s layout.
	 *
	 * @return null
	 */
	public function setLayout(FieldLayoutModel $layout)
	{
		$this->_layout = $layout;
	}

	/**
	 * Returns the tab’s fields.
	 *
	 * @return array The tab’s fields.
	 */
	public function getFields()
	{
		if (!isset($this->_fields))
		{
			$this->_fields = array();

			$layout = $this->getLayout();

			if ($layout)
			{
				$fields = $layout->getFields();

				foreach ($fields as $field)
				{
					if ($field->tabId == $this->id)
					{
						$this->_fields[] = $field;
					}
				}
			}
		}

		return $this->_fields;
	}

	/**
	 * Sets the tab’s fields.
	 *
	 * @param array $fields The tab’s fields.
	 *
	 * @return null
	 */
	public function setFields($fields)
	{
		$this->_fields = array();

		foreach ($fields as $field)
		{
			if (is_array($field))
			{
				$field = new FieldLayoutFieldModel($field);
			}

			$this->_fields[] = $field;
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
			'name'      => AttributeType::Name,
			'sortOrder' => AttributeType::SortOrder,
		];
	}
}
