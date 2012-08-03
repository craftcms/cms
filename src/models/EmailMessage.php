<?php
namespace Blocks;

/**
 *
 */
class EmailMessage extends BaseModel
{
	protected $tableName = 'emailmessages';

	protected $attributes = array(
		'key'           => array('type' => AttributeType::Char, 'required' => true, 'unique' => true, 'maxLength' => 150),
		'html_template' => array('type' => AttributeType::Varchar, 'maxLength' => 500),
	);

	protected $belongsTo = array(
		'plugin' => array('model' => 'Plugin')
	);

	protected $hasMany = array(
		'content' => array('model' => 'EmailMessageContent', 'foreignKey' => 'message'),
	);
}
