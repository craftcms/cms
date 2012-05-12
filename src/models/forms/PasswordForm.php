<?php
namespace Blocks;

/**
 *
 */
class PasswordForm extends FormModel
{
	protected $attributes = array(
		'password' => array('type' => AttributeType::Varchar, 'minLength' => 6, 'required' => true)
	);
}
