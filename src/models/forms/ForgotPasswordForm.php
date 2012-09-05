<?php
namespace Blocks;

/**
 * Forgot Password form
 */
class ForgotPasswordForm extends BaseForm
{
	protected function getProperties()
	{
		return array(
			'username' => array(PropertyType::Varchar, 'required' => true),
		);
	}

}
