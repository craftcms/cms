<?php
namespace Blocks;

/**
 *
 */
class EmailContent extends BaseModel
{
	protected $tableName = 'email_content';

	protected $attributes = array(
		'language'       => AttributeType::Language,
		'subject'        => array('type' => AttributeType::Varchar, 'required' => true, 'unique' => true, 'maxLength' => 1000),
		'content'        => array('type' => AttributeType::Text, 'required' => true),
		'html_content'   => AttributeType::Text,
	);

	protected $belongsTo = array(
		'email'     => array('model' => 'Email')
	);
}
