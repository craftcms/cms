<?php
namespace Blocks;

/**
 * Field layout model class
 */
class FieldLayoutModel extends BaseModel
{
	private $_tabs;
	private $_fields;

	/**
	 * @return array
	 */
	public function defineAttributes()
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
			return array();
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
			return array();
		}

		return $this->_fields;
	}

	/**
	 * Sets the layout's tabs.
	 *
	 * @param array $tabs
	 */
	public function setTabs($tabs)
	{
		$this->_tabs = FieldLayoutTabModel::populateModels($tabs);
	}

	/**
	 * Sets the layout's fields.
	 *
	 * @param array $fields
	 */
	public function setFields($fields)
	{
		$this->_fields = FieldLayoutFieldModel::populateModels($fields, 'fieldId');
	}

	/**
	 * Populates a new model instance with a given set of attributes.
	 *
	 * @static
	 * @param mixed $values
	 * @return BaseModel
	 */
	public static function populateModel($values)
	{
		$class = get_called_class();
		$model = new $class();

		if (isset($values['tabs']))
		{
			$model->setTabs($values['tabs']);
			unset($values['tabs']);
		}

		if (isset($values['fields']))
		{
			$model->setFields($values['fields']);
			unset($values['fields']);
		}

		$model->setAttributes($values);
		return $model;
	}
}
