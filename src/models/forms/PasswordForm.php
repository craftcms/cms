<?php
namespace Blocks;

/**
 *
 */
class PasswordForm extends BaseForm
{
	protected function getProperties()
	{
		return array(
			'password' => array(PropertyType::Varchar, 'minLength' => 6, 'required' => true)
		);
	}
}
