<?php
namespace Craft;

/**
 * Email message model class
 */
class EmailModel extends BaseModel
{
	/**
	 * @access protected
	 * @return array
	 */
	protected function defineAttributes()
	{
		$settings = craft()->email->getSettings();

		$fromEmail = !empty($settings['emailAddress']) ? $settings['emailAddress'] : '';
		$fromName =  !empty($settings['senderName']) ? $settings['senderName'] : '';

		return array(
			'fromEmail'   => array(AttributeType::Email, 'required' => true, 'default' => $fromEmail),
			'fromName'    => array(AttributeType::String, 'default' => $fromName),
			'toEmail'     => array(AttributeType::Email, 'required' => true),
			'toFirstName' => array(AttributeType::String),
			'toLastName'  => array(AttributeType::String),
			'subject'     => array(AttributeType::String, 'required' => true),
			'body'        => array(AttributeType::String, 'required' => true),
			'htmlBody'    => array(AttributeType::String),
			'cc'          => array(AttributeType::Mixed),
			'bcc'         => array(AttributeType::Mixed),
			'emailFormat' => array(AttributeType::String, 'default' => 'text'),
		);
	}
}
