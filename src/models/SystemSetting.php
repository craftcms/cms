<?php
namespace Blocks;

/**
 *
 */
class SystemSetting extends BaseModel
{
	protected $tableName = 'systemsettings';

	protected $attributes = array(
		'name'     => array('type' => AttributeType::Varchar, 'maxLength' => 100, 'required' => true),
		'value'    => AttributeType::Text,
		'category' => array('type' => AttributeType::Char, 'maxLength' => 15, 'required' => true)
	);

	protected $indexes = array(
		array('columns' => array('category','name'), 'unique' => true)
	);

}
