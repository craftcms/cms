<?php
namespace Blocks;

/**
 *
 */
class Email extends BaseModel
{
	protected $tableName = 'emails';

	protected $attributes = array(
		'key'               => array('type' => AttributeType::Char, 'required' => true, 'unique' => true, 'maxLength' => 150),
		'template_path'     => array('type' => AttributeType::Varchar, 'maxLength' => 500),
	);

	protected $belongsTo = array(
		'plugin'   => array('model' => 'Plugin')
	);

	protected $hasMany = array(
		'emailContent'   => array('model' => 'EmailContent', 'foreignKey' => 'email'),
	);
}
