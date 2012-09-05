<?php
namespace Blocks;

/**
 * Username model
 */
class UsernameModel extends BaseModel
{
	protected function getProperties()
	{
		return array(
			'username' => array(PropertyType::Varchar, 'required' => true),
		);
	}

}
