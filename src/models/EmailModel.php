<?php
namespace Craft;

/**
 * Email message model class.
 *
 * @property string $fromEmail         The sender’s email address. Defaults to the System Email Address defined in
 *                                     Settings → Email.
 * @property string $fromName          The sender’s name. Defaults to the Sender Name defined in Settings → Email.
 * @property string $toEmail           The recipient’s email address. (Required)
 * @property string $toFirstName       The recipient’s first name.
 * @property string $toLastName        The recipient’s last name.
 * @property string $subject           The email’s subject. (Required)
 * @property string $body              The email’s plain text body. (Required)
 * @property string $htmlBody          The email’s HTML body.
 * @property string $replyTo           The Reply-To email address.
 * @property string $sender            The value that should be passed to the “MAIL FROM” SMTP header, if that differs
 *                                     from $fromEmail.
 * @property array  $cc                The recipients that should be CC’d on the email. Each element of this array
 *                                     should be a nested array containing the keys 'name' and 'email'.
 * @property array  $bcc               The recipients that should be BCC’d on the email. Each element of this array
 *                                     should be a nested array containing the keys 'name' and 'email'.
 * @property array  $stringAttachments Any strings of text which should be attached to the email as files. Each element
 *                                     of this array should be a nested array containing the keys 'string' (the contents
 *                                     of the file), 'fileName' (the name of the file), 'encoding' (the file encoding),
 *                                     and 'type' (the file’s MIME type).
 * @property array  $attachments       Any files which should be attached to the email. Each element of this array
 *                                     should be a nested array containing the keys 'path' (the path to the file),
 *                                     'name' (the name of the file), 'encoding' (the file encoding), and 'type' (the
 *                                     file’s MIME type).
 * @property array  $customHeaders     Any custom headers that should be included in the email. The keys of the array
 *                                     should identify the header names, and their values, well, take a guess.
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
	 * Adds a string or binary attachment (non-filesystem) to the list. This method can be used to attach ascii or
	 * binary data, such as a BLOB record from a database.
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
	 * Adds an attachment from a path on the filesystem. Returns false if the file could not be found or accessed.
	 *
	 * @param string $path     Path to the attachment.
	 * @param string $name     Overrides the attachment name.
	 * @param string $encoding File encoding (see $Encoding).
	 * @param string $type     File extension (MIME) type.
	 *
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
	 * @inheritDoc BaseModel::defineAttributes()
	 *
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
