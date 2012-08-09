<?php
namespace Blocks;

/**
 *
 */
class EmailService extends \CApplicationComponent
{
	private $_defaultEmailTimeout = 10;

	/**
	 * Returns all of the system email messages.
	 *
	 * @return array
	 */
	public function getAllMessages()
	{
		$messages = EmailMessage::model()->findAll();
		return $messages;
	}

	/**
	 * Returns a system email message by its ID.
	 *
	 * @param int $messageId
	 * @return EmailMessage
	 */
	public function getMessageById($messageId)
	{
		$message = EmailMessage::model()->findById($messageId);
		return $message;
	}

	/**
	 * Returns a system email message by its key.
	 *
	 * @param string $key
	 * @param int $pluginId
	 * @return EmailMessage
	 */
	public function getMessageByKey($key, $pluginId = null)
	{
		$message = EmailMessage::model()->findByAttributes(array(
			'plugin_id' => $pluginId,
			'key' => $key
		));
		return $message;
	}

	/**
	 * Registers a new system email message.
	 *
	 * @param string $key
	 * @param int $pluginId
	 * @return EmailMessage
	 */
	public function registerMessage($key, $pluginId = null)
	{
		$message = new EmailMessage();
		$message->key = $key;
		$message->plugin_id = $pluginId;
		$message->save();
		return $message;
	}

	/**
	 * Returns the localized content for a system email message.
	 *
	 * @param int $messageId
	 * @param string $language
	 * @return string
	 */
	public function getMessageContent($messageId, $language = null)
	{
		if (!$language)
			$language = blx()->language;

		$content = EmailMessageContent::model()->findByAttributes(array(
			'id' => $messageId,
			'language' => $language
		));

		return $content;
	}

	/**
	 * Saves the localized content for a system email message.
	 *
	 * @param int $messageId
	 * @param string $subject
	 * @param string $body
	 * @param string $htmlBody
	 * @param string $language
	 */
	public function saveMessageContent($messageId, $subject, $body, $htmlBody = null, $language = null)
	{
		if (!$language)
			$language = blx()->language;

		// Has this message already been translated into this language?
		$content = $this->getMessageContent($messageId, $language);

		if (!$content)
		{
			$content = new EmailMessageContent();
			$content->language = $language;
		}

		$content->subject = $subject;
		$content->body = $body;
		$content->html_body = $htmlBody;
		$content->save();

		return $content;
	}

	/**
	 * @param EmailData $emailData
	 * @return bool
	 * @throws Exception
	 */
	public function sendEmail(EmailData $emailData)
	{
		// Get the saved email settings.
		$emailSettings = $this->getEmailSettings();

		if (!isset($emailSettings['protocol']))
			throw new Exception(Blocks::t(TranslationCategory::Email, 'Could not determine how to send the email.  Check your email settings.'));

		$email = new \PhpMailer(true);

		// Check which protocol we need to use.
		switch ($emailSettings['protocol'])
		{
			case EmailerType::GmailSmtp:
			case EmailerType::Smtp:
			{
				$this->_setSmtpSettings($email, $emailSettings);
				break;
			}

			case EmailerType::Pop:
			{
				$pop = new \Pop3();
				if (!isset($emailSettings['host']) || !isset($emailSettings['port']) || !isset($emailSettings['username']) || !isset($emailSettings['password']) ||
				    StringHelper::isNullOrEmpty($emailSettings['host']) || StringHelper::isNullOrEmpty($emailSettings['port']) || StringHelper::isNullOrEmpty($emailSettings['username']) || StringHelper::isNullOrEmpty($emailSettings['password']))
				{
					throw new Exception(Blocks::t(TranslationCategory::Email, 'Host, port, username and password must be configured under your email settings.'));
				}

				if (!isset($emailSettings['timeout']))
					$emailSettings['timeout'] = $this->_defaultEmailTimeout;

				$pop->authorize($emailSettings['host'], $emailSettings['port'], $emailSettings['timeout'], $emailSettings['username'], $emailSettings['password'], blx()->config->devMode ? 1 : 0);

				$this->_setSmtpSettings($email, $emailSettings);
				break;
			}

			case EmailerType::Sendmail:
			{
				$email->isSendmail();
				break;
			}

			case EmailerType::PhpMail:
			{
				$email->isMail();
				break;
			}

			default:
			{
				$email->isMail();
			}
		}

		$email->from = $emailData->getFrom()->getEmailAddress();
		$email->fromName = $emailData->getFrom()->getName();
		$email->addReplyTo($emailData->getReplyTo()->getEmailAddress(), $emailData->getReplyTo()->getName());

		foreach ($emailData->getTo() as $toAddress)
		{
			$email->addAddress($toAddress->getEmailAddress(), $toAddress->getName());
		}

		foreach ($emailData->getCc() as $toCcAddress)
		{
			$email->addCc($toCcAddress->getEmailAddress(), $toCcAddress->getName());
		}

		foreach ($emailData->getBcc() as $toBccAddress)
		{
			$email->addBcc($toBccAddress->getEmailAddress(), $toBccAddress->getName());
		}

		$email->subject = $emailData->getSubject();

		// See if it's an HTML email.
		if ($emailData->getIsHtml())
		{
			// They already supplied an alt body (text), use it.
			if ($emailData->getAltBody())
			{
				$email->altBody = $emailData->getAltBody();
			}

			// msgHtml will attempt to set a alt body from the html string if alt body was not supplied earlier.
			$email->msgHtml($emailData->getBody());
		}
		else
		{
			// This is a text email.
			$email->body = $emailData->getBody();
		}

		if (!$email->send())
			throw new Exception(Blocks::t(TranslationCategory::Email, 'Email error: {errorMessage}', array('{errorMessage}' => $email->errorInfo)));

		return true;
	}

	/**
	 * Sends an email by its message key.
	 *
	 * @param User   $user
	 * @param string $key
	 * @param int    $pluginId
	 * @param array  $variables
	 * @throws Exception
	 * @return boolMessage
	 */
	public function sendUserEmailByKey(User $user, $key, $pluginId = null, $variables = array())
	{
		$emailSettings = $this->getEmailSettings();
		$emailData = new EmailData(new EmailAddress($emailSettings['emailAddress'], $emailSettings['senderName']), array(new EmailAddress($user->email, $user->first_name.' '.$user->last_name)));
		$emailData->setIsHtml($user->html_email);

		// Get the email by key and plugin from the database.
		$message = $this->getMessageByKey($key, $pluginId);

		if (!$message)
		{
			$error = 'Could not find an email template with the key: '.$key;

			if ($pluginId !== null)
				$error .= ' and plugin ID: '.$pluginId;

			$error .= '.';

			throw new Exception(Blocks::t(TranslationCategory::Email, 'Email error: {errorMessage}', array('{errorMessage}' => $error)));
		}

		$content = $this->getMessageContent($message->id, $language);

		// Render the email templates
		//$variables['user'] => $user;
		//$emailContent = blx()->controller->loadEmailTemplate($email, $variables);

		// Set the subject.
		$emailData->setSubject($content->subject);

		if ($emailData->getIsHtml() && $content->html_body)
		{
			$emailData->setBody($content->html_body);
			$emailData->setAltBody($content->body);
		}
		else
		{
			$emailData->setBody($content->body);
		}

		// Send it!
		return $this->sendEmail($emailData);
	}

	/**
	 * @param $email
	 * @param $emailSettings
	 * @throws Exception
	 */
	private function _setSmtpSettings(&$email, $emailSettings)
	{
		$email->isSmtp();

		if (isset($emailSettings['smtpAuth']) && $emailSettings['smtpAuth'] == 1)
		{
			$email->smtpAuth = true;
			if ((!isset($emailSettings['username']) && StringHelper::isNullOrEmpty($emailSettings['username'])) || (!isset($emailSettings['password']) && StringHelper::isNullOrEmpty($emailSettings['password'])))
				throw new Exception(Blocks::t(TranslationCategory::Email, 'Username and password are required.  Check your email settings.'));

			$email->userName = $emailSettings['username'];
			$email->password = $emailSettings['password'];
		}

		if (isset($emailSettings['smtpKeepAlive']) && $emailSettings['smtpKeepAlive'] == 1)
			$email->smtpKeepAlive = true;

		$email->smtpSecure = $emailSettings['smtpSecureTransportType'] != 'none' ? $emailSettings['smtpSecureTransportType'] : null;

		if (!isset($emailSettings['host']))
			throw new Exception(Blocks::t(TranslationCategory::Email, 'You must specify a host name in your email settings.'));

		if (!isset($emailSettings['port']))
			throw new Exception(Blocks::t(TranslationCategory::Email, 'You must specify a port in your email settings.'));

		if (!isset($emailSettings['timeout']))
			$emailSettings['timeout'] = $this->_defaultEmailTimeout;

		$email->host = $emailSettings['host'];
		$email->port = $emailSettings['port'];
		$email->timeout = $emailSettings['timeout'];
	}

	/**
	 * @return mixed
	 */
	public function getEmailSettings()
	{
		$emailSettings = blx()->settings->getSystemSettings('email');
		return $emailSettings;
	}

	/**
	 * @param $settings
	 * @return bool
	 */
	public function saveEmailSettings($settings)
	{
		if (blx()->settings->saveSettings('systemsettings', $settings, 'email', true))
			return true;

		return false;
	}
}
