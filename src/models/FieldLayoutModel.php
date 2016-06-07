<?php
namespace Craft;

/**
 * Field layout model class.
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://craftcms.com/license Craft License Agreement
 * @see       http://craftcms.com
 * @package   craft.app.models
 * @since     1.0
 */
class FieldLayoutModel extends BaseModel
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
				$this->_tabs = craft()->fields->getLayoutTabsById($this->id);
			}
			else
			{
				$this->_tabs = array();
			}
		}

		return $this->_tabs;
	}

	/**
	 * Returns the layout’s fields.
	 *
	 * @return FieldLayoutFieldModel[] The layout’s fields.
	 */
	public function getFields()
	{
		if (!isset($this->_fields))
		{
			if ($this->id)
			{
				$this->_fields = craft()->fields->getOrderedLayoutFieldsById($this->id);
			}
			else
			{
				$this->_fields = array();
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
		$ids = array();

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
		$this->_tabs = array();

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
		$this->_fields = array();

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
	 * @inheritDoc BaseModel::defineAttributes()
	 *
	 * @return array
	 */
	protected function defineAttributes()
	{
		return array(
			'id'   => AttributeType::Number,
			'type' => AttributeType::ClassName,
		);
	}
}
