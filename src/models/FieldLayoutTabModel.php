<?php
namespace Craft;

/**
 * Field layout tab model class
 */
class FieldLayoutTabModel extends BaseModel
{
	private $_fields;

	/**
	 * @access protected
	 * @return array
	 */
	protected function defineAttributes()
	{
		return array(
			'id'        => AttributeType::Number,
			'name'      => AttributeType::Name,
			'sortOrder' => AttributeType::SortOrder,
		);
	}

	/**
	 * Returns the tab's fields.
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
	 * Sets the tab's fields.
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
		if (isset($values['fields']))
		{
			$fields = $values['fields'];
			unset($values['fields']);
		}

		$model = parent::populateModel($values);

		if (isset($fields))
		{
			$model->setFields($fields);
		}

		return $model;
	}
}
