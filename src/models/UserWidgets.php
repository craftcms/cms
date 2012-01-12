<?php

/**
 *
 */
class UserWidgets extends BaseModel
{
	/**
	 * Returns an instance of the specified model
	 *
	 * @access public
	 *
	 * @static
	 *
	 * @param string $class
	 *
	 * @return object The model instance
	 */
	public static function model($class = __CLASS__)
	{
		return parent::model($class);
	}

	protected $hasMany = array(
		'settings' => array('model' => 'UserWidgetSettings', 'foreignKey' => 'widget')
	);

	protected $belongsTo = array(
		'user' => array('model' => 'Users', 'required' => true)
	);

	protected $attributes = array(
		'class'      => array('type' => AttributeType::String, 'maxSize' => 150, 'required' => true),
		'sort_order' => array('type' => AttributeType::Integer, 'required' => true)
	);
}
