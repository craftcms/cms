<?php
namespace Blocks;

/**
 * Forgot Password model
 */
class ForgotPasswordModel extends BaseModel
{
	protected function getProperties()
	{
		return array(
			'username' => array(PropertyType::Varchar, 'required' => true),
		);
	}

}
