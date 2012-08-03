<?php
namespace Blocks;

/**
 *
 */
class EmailMessageContent extends BaseModel
{
	protected $tableName = 'emailmessagecontent';

	protected $attributes = array(
		'language'  => AttributeType::Language,
		'subject'   => array(AttributeType::Varchar, 'required' => true, 'maxLength' => 1000),
		'body'      => array(AttributeType::Text, 'required' => true),
		'html_body' => AttributeType::Text,
	);

	protected $belongsTo = array(
		'message' => array('model' => 'EmailMessage')
	);

	protected $indexes = array(
		array('columns' => array('message_id', 'language'), 'unique' => true)
	);
}
