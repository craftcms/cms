<?php
namespace Blocks;

/**
 *
 */
class AuthCode extends BaseModel
{
	protected $tableName = 'authcodes';

	protected $attributes = array(
		'code'              => array('type' => AttributeType::Char, 'maxLength' => 36),
		'type'              => array('type' => AttributeType::Enum, 'values' => array('Registration', 'ResetPassword', 'ForgotPassword'), 'required' => true),
		'date_issued'       => array('type' => AttributeType::Int, 'required' => true),
		'date_activated'    => array('type' => AttributeType::Int),
		'expiration_date'   => array('type' => AttributeType::Int, 'required' => true),
	);

	protected $belongsTo = array(
		'user' => array('model' => 'User', 'required' => true),
	);
}
