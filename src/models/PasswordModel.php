<?php
namespace Blocks;

/**
 *
 */
class PasswordModel extends BaseModel
{
	protected function defineAttributes()
	{
		return array(
			'password' => array(AttributeType::Varchar, 'minLength' => 6, 'required' => true)
		);
	}
}
