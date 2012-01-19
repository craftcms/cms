<?php

/**
 *
 */
class bPlugin extends bBaseModel
{
	protected $tableName = 'plugins';

	protected $attributes = array(
		'name'    => array('type' => bAttributeType::String, 'maxLength' => 50),
		'version' => array('type' => bAttributeType::String, 'maxLength' => 15),
		'enabled' => array('type' => bAttributeType::Boolean, 'default' => true, 'required' => true, 'unsigned' => true)
	);

	protected $hasMany = array(
		'settings' => array('model' => 'bPluginSetting', 'foreignKey' => 'plugin')
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
