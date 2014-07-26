<?php
namespace Craft;

/**
 * Field layout model class.
 *
 * @package craft.app.models
 */
class FieldLayoutModel extends BaseModel
{
	private $_tabs;
	private $_fields;

	/**
	 * @return array
	 */
	protected function defineAttributes()
	{
		return array(
			'id'   => AttributeType::Number,
			'type' => AttributeType::ClassName,
		);
	}

	/**
	 * Returns the layout's tabs.
	 *
	 * @return array
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
	 * Returns the layout's fields.
	 *
	 * @return array
	 */
	public function getFields()
	{
		if (!isset($this->_fields))
		{
			if ($this->id)
			{
				$this->_fields = craft()->fields->getLayoutFieldsById($this->id);
			}
			else
			{
				$this->_fields = array();
			}
		}

		return $this->_fields;
	}

	/**
	 * Returns the layout's fields' IDs.
	 *
	 * @return array
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
	 * Sets the layout's tabs.
	 *
	 * @param array $tabs
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
	 * Sets the layout's fields.
	 *
	 * @param array $fields
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
}
