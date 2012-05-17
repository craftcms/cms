<?php
namespace Blocks;

/**
 *
 */
class Widget extends BaseModel
{
	protected $tableName = 'widgets';
	protected $settingsTableName = 'widgetsettings';
	protected $foreignKeyName = 'widget_id';
	public $hasSettings = true;

	protected $attributes = array(
		'class'      => AttributeType::ClassName,
		'sort_order' => AttributeType::SortOrder
	);

	protected $belongsTo = array(
		'user'   => array('model' => 'User', 'required' => true),
		'plugin' => array('model' => 'Plugin')
	);
}
