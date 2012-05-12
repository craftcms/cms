<?php
namespace Blocks;

/**
 * Validates the required User attributes for the installer.
 */
class InstallUserForm extends FormModel
{
	protected $attributes = array(
		'username' => array('type' => AttributeType::Varchar, 'maxLength' => 100, 'required' => true),
		'email'    => array('type' => AttributeType::Email, 'required' => true),
		'password' => array('type' => AttributeType::Varchar, 'minLength' => 6, 'required' => true)
	);
}
