<?php
namespace Craft;

/**
 * Class GetHelpModel
 *
 * @package craft.app.models
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
			'fromEmail'        => array(AttributeType::Email, 'required' => true, 'label' => 'Your Email'),
			'message'          => array(AttributeType::String, 'required' => true),
			'attachLogs'       => AttributeType::Bool,
			'attachDbBackup'   => AttributeType::Bool,
			'attachTemplates'  => AttributeType::Bool,
			'attachment'       => AttributeType::Mixed,
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
