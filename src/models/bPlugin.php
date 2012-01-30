<?php

/**
 *
 */
class bPlugin extends bBaseModel
{
	protected $tableName = 'plugins';

	protected $attributes = array(
		'class'   => bAttributeType::ClassName,
		'version' => bAttributeType::Version,
		'enabled' => array('type' => bAttributeType::Boolean, 'default' => true)
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
