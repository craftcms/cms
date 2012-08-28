<?php
namespace Blocks;

/**
 * Validates the required User attributes for the installer.
 */
class InstallUserForm extends BaseForm
{
	protected $attributes = array(
		'username' => array('type' => PropertyType::Varchar, 'maxLength' => 100, 'required' => true),
		'email'    => array('type' => PropertyType::Email, 'required' => true),
		'password' => array('type' => PropertyType::Varchar, 'minLength' => 6, 'required' => true)
	);
}
