<?php
namespace Blocks;

/**
 *
 */
class EmailTemplate extends BaseModel
{
	protected $tableName = 'email_templates';

	protected $attributes = array(
		'key'           => array('type' => AttributeType::Char, 'required' => true, 'unique' => true, 'maxLength' => 150),
		'subject'       => array('type' => AttributeType::Varchar, 'required' => true, 'maxLength' => 1000),
		'html'          => array('type' => AttributeType::Text, 'required' => true),
		'text'          => array('type' => AttributeType::Text),
	);

	protected $belongsTo = array(
		'plugin'   => array('model' => 'Plugin')
	);
}
