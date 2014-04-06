<?php
namespace Craft;

/**
 *
 */
class PasswordModel extends BaseModel
{
	/**
	 * @access protected
	 * @return array
	 */
	protected function defineAttributes()
	{
		return array(
			'password' => array(AttributeType::String, 'minLength' => 6, 'maxLength' => 160, 'required' => true)
		);
	}
}
