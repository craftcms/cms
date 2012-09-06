<?php
namespace Blocks;

/**
 *
 */
class PasswordModel extends BaseModel
{
	public function defineAttributes()
	{
		return array(
			'password' => array(AttributeType::Varchar, 'minLength' => 6, 'required' => true)
		);
	}
}
