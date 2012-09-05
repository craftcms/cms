<?php
namespace Blocks;

/**
 *
 */
class PasswordModel extends BaseModel
{
	protected function getProperties()
	{
		return array(
			'password' => array(PropertyType::Varchar, 'minLength' => 6, 'required' => true)
		);
	}
}
