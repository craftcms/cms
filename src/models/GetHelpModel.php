<?php
namespace Craft;

/**
 *
 */
class GetHelpModel extends BaseModel
{
	/**
	 * @access protected
	 * @return array
	 */
	protected function defineAttributes()
	{
		return array(
			'fromEmail'        => array(AttributeType::Email, 'required' => true),
			'message'          => array(AttributeType::String, 'required' => true),
			'attachDebugFiles' => array(AttributeType::Bool)
		);
	}
}
