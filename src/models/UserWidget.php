<?php
namespace Blocks;

/**
 *
 */
class UserWidget extends BaseModel
{
	protected $tableName = 'userwidgets';
	protected $hasSettings = true;

	protected $attributes = array(
		'class'      => AttributeType::ClassName,
		'sort_order' => AttributeType::SortOrder
	);

	protected $belongsTo = array(
		'user' => array('model' => 'User', 'required' => true),
		'plugin' => array('model' => 'Plugin')
	);

	protected $indexes = array(
		array('columns' => array('user_id','class'), 'unique' => true)
	);
}
