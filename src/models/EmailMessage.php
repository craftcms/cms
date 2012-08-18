<?php
namespace Blocks;

/**
 *
 */
class EmailMessage extends BaseModel
{
	protected $tableName = 'emailmessages';

	protected $attributes = array(
		'key'      => array('type' => AttributeType::Char, 'required' => true, 'unique' => true, 'maxLength' => 150),
		'template' => array('type' => AttributeType::Varchar, 'maxLength' => 500),
	);

	protected $hasMany = array(
		'content' => array('model' => 'EmailMessageContent', 'foreignKey' => 'message'),
	);
}
