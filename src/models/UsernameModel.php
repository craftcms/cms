<?php
namespace Craft;

/**
 * Username model
 */
class UsernameModel extends BaseModel
{
	/**
	 * @return array
	 */
	public function defineAttributes()
	{
		return array(
			'username' => array(AttributeType::String, 'required' => true),
		);
	}
}
