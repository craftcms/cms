<?php

/**
 *
 */
class bUserWidget extends bBaseModel
{
	protected $tableName = 'userwidgets';

	protected $attributes = array(
		'class'      => bAttributeType::ClassName,
		'sort_order' => bAttributeType::SortOrder
	);

	protected $belongsTo = array(
		'user' => array('model' => 'bUser', 'required' => true)
	);

	protected $hasMany = array(
		'settings' => array('model' => 'bUserWidgetSetting', 'foreignKey' => 'widget')
	);

	protected $indexes = array(
		array('columns' => array('user_id','class'), 'unique' => true)
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
