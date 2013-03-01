<?php
namespace Craft;

/**
 * Field layout model class
 */
class FieldLayoutModel extends BaseModel
{
	private $_tabs;
	private $_fields;

	/**
	 * @access protected
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
		if (isset($values['tabs']))
		{
			$tabs = $values['tabs'];
			unset($values['tabs']);
		}

		if (isset($values['fields']))
		{
			$fields = $values['fields'];
			unset($values['fields']);
		}

		$model = parent::populateModel($values);

		if (isset($tabs))
		{
			$model->setTabs($tabs);
		}

		if (isset($fields))
		{
			$model->setFields($fields);
		}

		return $model;
	}
}
