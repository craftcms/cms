<?php
namespace Craft;

/**
 * Email message model class.
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://buildwithcraft.com/license Craft License Agreement
 * @see       http://buildwithcraft.com
 * @package   craft.app.models
 * @since     1.0
 */
class EmailModel extends BaseModel
{
	// Public Methods
	// =========================================================================

	/**
	 * Adds a string or binary attachment (non-filesystem) to the list. This
	 * method can be used to attach ascii or binary data, such as a BLOB record
	 * from a database.
	 *
	 * @param string $string   String attachment data.
	 * @param string $fileName Name of the attachment.
	 * @param string $encoding File encoding
	 * @param string $type     File extension MIME type.
	 *
	 * @return null
	 */
	public function addStringAttachment($string, $fileName, $encoding = 'base64', $type = 'application/octet-stream')
	{
		$existingAttachments = $this->stringAttachments;
		$existingAttachments[] = array('string' => $string, 'fileName' => $fileName, 'encoding' => $encoding, 'type' => $type);
		$this->stringAttachments = $existingAttachments;
	}

	/**
	 * Adds an attachment from a path on the filesystem. Returns false if the
	 * file could not be found or accessed.
	 *
	 * @param string $path     Path to the attachment.
	 * @param string $name     Overrides the attachment name.
	 * @param string $encoding File encoding (see $Encoding).
	 * @param string $type     File extension (MIME) type.
	 *
	 * @throws phpmailerException
	 * @return bool
	 */
	public function addAttachment($path, $name = '', $encoding = 'base64', $type = 'application/octet-stream')
	{
		$existingAttachments = $this->attachments;
		$existingAttachments[] = array('path' => $path, 'name' => $name, 'encoding' => $encoding, 'type' => $type);
		$this->attachments = $existingAttachments;
	}

	// Protected Methods
	// =========================================================================

	/**
	 * @return array
	 */
	protected function defineAttributes()
	{
		$settings = craft()->email->getSettings();

		$fromEmail = !empty($settings['emailAddress']) ? $settings['emailAddress'] : '';
		$fromName =  !empty($settings['senderName']) ? $settings['senderName'] : '';

		return array(
			'fromEmail'         => array(AttributeType::Email, 'required' => true, 'default' => $fromEmail),
			'fromName'          => array(AttributeType::String, 'default' => $fromName),
			'toEmail'           => array(AttributeType::Email, 'required' => true),
			'toFirstName'       => array(AttributeType::String),
			'toLastName'        => array(AttributeType::String),
			'subject'           => array(AttributeType::String, 'required' => true),
			'body'              => array(AttributeType::String, 'required' => true),
			'htmlBody'          => array(AttributeType::String),
			'replyTo'           => array(AttributeType::Email),
			'sender'            => array(AttributeType::Email),
			'cc'                => array(AttributeType::Mixed),
			'bcc'               => array(AttributeType::Mixed),
			'stringAttachments' => array(AttributeType::Mixed),
			'attachments'       => array(AttributeType::Mixed),
			'customHeaders'     => array(AttributeType::Mixed),
		);
	}
}
