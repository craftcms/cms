<?php
namespace Blocks;

/**
 * Validates the required User attributes for the installer.
 */
class AccountSettingsModel extends BaseModel
{
	public function defineAttributes()
	{
		return array(
			'username' => array(AttributeType::Varchar, 'maxLength' => 100, 'required' => true),
			'email'    => array(AttributeType::Email, 'required' => true),
			'password' => array(AttributeType::Varchar, 'minLength' => 6, 'required' => true)
		);
	}
}
