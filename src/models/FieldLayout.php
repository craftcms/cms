<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2013 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\models;

use Craft;
use craft\app\base\Model;
use craft\app\enums\AttributeType;
use craft\app\models\Field as FieldModel;
use craft\app\models\FieldLayoutField as FieldLayoutFieldModel;
use craft\app\models\FieldLayoutTab as FieldLayoutTabModel;

/**
 * FieldLayout model class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class FieldLayout extends Model
{
	// Properties
	// =========================================================================

	/**
	 * @var
	 */
	private $_tabs;

	/**
	 * @var
	 */
	private $_fields;

	// Public Methods
	// =========================================================================

	/**
	 * Returns the layout’s tabs.
	 *
	 * @return FieldLayoutTabModel[] The layout’s tabs.
	 */
	public function getTabs()
	{
		if (!isset($this->_tabs))
		{
			if ($this->id)
			{
				$this->_tabs = Craft::$app->fields->getLayoutTabsById($this->id);
			}
			else
			{
				$this->_tabs = [];
			}
		}

		return $this->_tabs;
	}

	/**
	 * Returns the layout’s fields.
	 *
	 * @return FieldModel[] The layout’s fields.
	 */
	public function getFields()
	{
		if (!isset($this->_fields))
		{
			if ($this->id)
			{
				$this->_fields = Craft::$app->fields->getLayoutFieldsById($this->id);
			}
			else
			{
				$this->_fields = [];
			}
		}

		return $this->_fields;
	}

	/**
	 * Returns the layout’s fields’ IDs.
	 *
	 * @return array The layout’s fields’ IDs.
	 */
	public function getFieldIds()
	{
		$ids = [];

		foreach ($this->getFields() as $field)
		{
			$ids[] = $field->fieldId;
		}

		return $ids;
	}

	/**
	 * Sets the layout’s tabs.
	 *
	 * @param array|FieldLayoutTabModel[] $tabs An array of the layout’s tabs, which can either be FieldLayoutTabModel
	 *                                          objects or arrays defining the tab’s attributes.
	 *
	 * @return null
	 */
	public function setTabs($tabs)
	{
		$this->_tabs = [];

		foreach ($tabs as $tab)
		{
			if (is_array($tab))
			{
				$tab = new FieldLayoutTabModel($tab);
			}

			$tab->setLayout($this);
			$this->_tabs[] = $tab;
		}
	}

	/**
	 * Sets the layout']”s fields.
	 *
	 * @param array|FieldLayoutFieldModel[] $fields An array of the layout’s tabs, which can either be
	 *                                              FieldLayoutFieldModel objects or arrays defining the tab’s
	 *                                              attributes.
	 *
	 * @return null
	 */
	public function setFields($fields)
	{
		$this->_fields = [];

		foreach ($fields as $field)
		{
			if (is_array($field))
			{
				$field = new FieldLayoutFieldModel($field);
			}

			$field->setLayout($this);
			$this->_fields[] = $field;
		}
	}

	// Protected Methods
	// =========================================================================

	/**
	 * @inheritDoc Model::defineAttributes()
	 *
	 * @return array
	 */
	protected function defineAttributes()
	{
		return [
			'id'   => AttributeType::Number,
			'type' => AttributeType::ClassName,
		];
	}
}
