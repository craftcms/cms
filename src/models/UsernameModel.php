<?php
namespace Blocks;

/**
 * Username model
 */
class UsernameModel extends BaseModel
{
	protected function defineAttributes()
	{
		return array(
			'username' => array(AttributeType::Varchar, 'required' => true),
		);
	}

}
