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
	 * @param EmailData    $emailData
	 * @param              $emailKey
	 * @param string       $languageCode
	 * @param array        $variables
	 * @param null         $pluginClass
	 * @throws Exception
	 * @return bool
	 */
	public function sendTemplateEmail(EmailData $emailData, $emailKey, $languageCode = 'en_us', $variables = array(), $pluginClass = null)
	{
		// Get the email by key and plugin from the database.
		$email = $this->getEmailByKey($emailKey, $pluginClass);

		if (!$email)
		{
			$message = 'Could not find an email template with the key: '.$emailKey;

			if ($pluginClass !== null)
				$message .= ' and plugin class: '.$pluginClass;

			$message .= '.';

			throw new Exception(Blocks::t(TranslationCategory::Email, 'Email error: {errorMessage}', array('{errorMessage}' => $message)));
		}

		// Subject is required.
		if (StringHelper::isNullOrEmpty($email->subject))
			throw new Exception(Blocks::t(TranslationCategory::Email, 'The subject is required when attempting to send an email.'));

		// HTML OR Text body is required.
		if (StringHelper::isNullOrEmpty($email->html) && StringHelper::isNullOrEmpty($email->text))
			throw new Exception(Blocks::t(TranslationCategory::Email, 'Either the email Html body or text body is required when sending an email.'));

		// Render the email templates
		$emailContent = blx()->controller->loadEmailTemplate($email, $variables);

		$textExists = StringHelper::isNotNullOrEmpty($emailContent['text']);
		$htmlExists = StringHelper::isNotNullOrEmpty($emailContent['html']);
		$subjectExists = StringHelper::isNotNullOrEmpty($emailContent['subject']);

		// Check to see if the subject rendered.
		if (!$subjectExists)
			throw new Exception(Blocks::t(TranslationCategory::Email, 'Could not render the subject email template for the requested email.'));

		// Check to see if the HTML or Text body rendered.
		if (!$htmlExists && !$textExists)
			throw new Exception(Blocks::t(TranslationCategory::Email, 'Could not render the html email template or the text email template body for the requested email.'));

		// Set the subject.
		$emailData->setSubject($emailContent['subject']);

		// Check if this is an HTML email.
		if ($emailData->getIsHtml())
		{
			// We were able to render an HTML and Text email template.
			if ($htmlExists && $textExists)
			{
				$emailData->setAltBody($emailContent['text']);
				$emailData->setBody($emailContent['html']);
			}

			// We found an HTML template, but not a text one.
			elseif ($htmlExists && !$textExists)
			{
				$emailData->setBody($emailContent['html']);
			}

			// Found a text template, but not an HTML one, so use the text as the primary body.
			elseif (!$htmlExists && $textExists)
			{
				$emailData->setBody($emailContent['text']);
			}
		}
		else
		{
			// This is a text only email, so we ignore anything that was an HTML template.
			if ($textExists)
			{
				$emailData->setBody($emailContent['text']);
			}
			else
				throw new Exception(Blocks::t(TranslationCategory::Email, 'A non-HTML email was specified, but could not render the text template.'));
		}

		// Send it!
		if ($this->sendEmail($emailData))
			return true;

		return false;
	}

	/**
	 * @param      $key
	 * @param null $pluginClass
	 * @return mixed
	 */
	public function getEmailByKey($key, $pluginClass = null)
	{
		$email = blx()->db->createCommand()
			->select('e.*')
			->from('emails e')
			->join('plugins p', 'p.id = e.plugin_id')
			->where('e.key = :key AND p.class = :pluginClass', array(':key' => $key, ':pluginClass' => $pluginClass))
			->queryRow();

		if ($email)
			return Email::model()->populateRecord($email);

		return false;
	}

	/**
	 * @param User $user
	 * @param      $site
	 * @return bool
	 */
	public function sendVerificationEmail(User $user, Site $site)
	{
		$emailSettings = $this->getEmailSettings();
		$email = new EmailData(new EmailAddress($emailSettings['emailAddress'], $emailSettings['senderName']), array(new EmailAddress($user->email, $user->first_name.' '.$user->last_name)));

		if ($user->html_email)
			$email->setIsHtml(true);
		else
			$email->setIsHtml(false);

		if ($this->sendTemplateEmail($email, 'registeruser', array('user' => $user, 'site' => $site)))
			return true;

		return false;
	}

	/**
	 * @param User $user
	 * @param      $site
	 * @return bool
	 */
	public function sendForgotPasswordEmail(User $user, Site $site)
	{
		$emailSettings = $this->getEmailSettings();
		$email = new EmailData(new EmailAddress($emailSettings['emailAddress'], $emailSettings['senderName']), array(new EmailAddress($user->email, $user->first_name.' '.$user->last_name)));

		if ($user->html_email)
			$email->setIsHtml(true);
		else
			$email->setIsHtml(false);

		if ($this->sendTemplateEmail($email, 'forgotpassword', array('user' => $user, 'site' => $site)))
			return true;

		return false;
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
