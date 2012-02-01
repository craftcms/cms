<?php
namespace Blocks;

/**
 *
 */
class UserWidget extends BaseModel
{
	protected $tableName = 'userwidgets';

	protected $attributes = array(
		'class'      => AttributeType::ClassName,
		'sort_order' => AttributeType::SortOrder
	);

	protected $belongsTo = array(
		'user' => array('model' => 'User', 'required' => true),
		'plugin' => array('model' => 'Plugin')
	);

	protected $hasMany = array(
		'settings' => array('model' => 'UserWidgetSetting', 'foreignKey' => 'widget')
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
