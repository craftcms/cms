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
			'attachDebugFiles' => array(AttributeType::Bool),
			'attachment'       => array(AttributeType::Mixed),
		);
	}

	public function rules()
	{
		// maxSize is 3MB
		return array_merge(parent::rules(), array(
			array('attachment', 'file', 'maxSize' => 3145728, 'allowEmpty' => true),
		));
	}
}
