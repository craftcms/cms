<?php

/**
 *
 */
class UserWidgets extends BaseModel
{
	protected $attributes = array(
		'class'      => array('type' => AttributeType::String, 'maxSize' => 150, 'required' => true),
		'sort_order' => array('type' => AttributeType::Integer, 'required' => true)
	);

	protected $belongsTo = array(
		'user' => array('model' => 'Users', 'required' => true)
	);

	protected $hasMany = array(
		'settings' => array('model' => 'UserWidgetSettings', 'foreignKey' => 'widget')
	);

	/**
	 * Returns an instance of the specified model
	 * @return object The model instance
	 * @static
	 */
	public static function model($class = __CLASS__)
	{
		return parent::model($class);
	}
}
