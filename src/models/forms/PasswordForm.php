<?php
namespace Blocks;

/**
 *
 */
class PasswordForm extends BaseForm
{
	protected $attributes = array(
		'password' => array('type' => PropertyType::Varchar, 'minLength' => 6, 'required' => true)
	);
}
