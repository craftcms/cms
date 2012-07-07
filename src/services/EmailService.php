<?php
namespace Blocks;

/**
 *
 */
class EmailService extends \CApplicationComponent
{
	private $_defaultEmailTimeout = 10;

	public function registerEmailTemplate()
	{

	}

	/**
	 * @param EmailMessage $emailMessage
	 * @return bool
	 * @throws Exception
	 */
	public function sendEmail(EmailMessage $emailMessage)
	{
		// Get the saved email settings.
		$emailSettings = $this->getEmailSettings();

		if (!isset($emailSettings['protocol']))
			throw new Exception('Could not determine how to send the email.  Check your email settings.');

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
					throw new Exception('Host, port, username and password must be configured under your email settings.');
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

		$email->from = $emailMessage->getFrom()->getEmailAddress();
		$email->fromName = $emailMessage->getFrom()->getName();
		$email->addReplyTo($emailMessage->getReplyTo()->getEmailAddress(), $emailMessage->getReplyTo()->getName());

		foreach ($emailMessage->getTo() as $toAddress)
		{
			$email->addAddress($toAddress->getEmailAddress(), $toAddress->getName());
		}

		foreach ($emailMessage->getCc() as $toCcAddress)
		{
			$email->addCc($toCcAddress->getEmailAddress(), $toCcAddress->getName());
		}

		foreach ($emailMessage->getBcc() as $toBccAddress)
		{
			$email->addBcc($toBccAddress->getEmailAddress(), $toBccAddress->getName());
		}

		$email->subject = $emailMessage->getSubject();

		// See if it's an HTML email.
		if ($emailMessage->getIsHtml())
		{
			// They already supplied an alt body (text), use it.
			if ($emailMessage->getAltBody())
			{
				$email->altBody = $emailMessage->getAltBody();
			}

			// msgHtml will attempt to set a alt body from the html string if alt body was not supplied earlier.
			$email->msgHtml($emailMessage->getBody());
		}
		else
		{
			// This is a text email.
			$email->body = $emailMessage->getBody();
		}

		if (!$email->send())
			throw new Exception($email->errorInfo);

		return true;
	}

	/**
	 * @param EmailMessage $emailMessage
	 * @param              $emailKey
	 * @param string       $languageCode
	 * @param array        $variables
	 * @param null         $pluginClass
	 * @throws Exception
	 * @return bool
	 */
	public function sendTemplateEmail(EmailMessage $emailMessage, $emailKey, $languageCode = 'en_us', $variables = array(), $pluginClass = null)
	{
		// Get the email by key and plugin from the database.
		$email = $this->getEmailByKey($emailKey, $pluginClass);

		if (!$email)
		{
			$message = 'Could not find an email template with the key: '.$emailKey;

			if ($pluginClass !== null)
				$message .= ' and plugin class: '.$pluginClass;

			$message .= '.';

			throw new Exception($message);
		}

		// Subject is required.
		if (StringHelper::isNullOrEmpty($email->subject))
			throw new Exception('The subject is required when attempting to send an email.');

		// HTML OR Text body is required.
		if (StringHelper::isNullOrEmpty($email->html) && StringHelper::isNullOrEmpty($email->text))
			throw new Exception('Either the email Html body or text body is required when sending an email.');

		// Render the email templates
		$emailContent = blx()->controller->loadEmailTemplate($email, $variables);

		$textExists = StringHelper::isNotNullOrEmpty($emailContent['text']);
		$htmlExists = StringHelper::isNotNullOrEmpty($emailContent['html']);
		$subjectExists = StringHelper::isNotNullOrEmpty($emailContent['subject']);

		// Check to see if the subject rendered.
		if (!$subjectExists)
			throw new Exception('Could not render the subject email template for the requested email.');

		// Check to see if the HTML or Text body rendered.
		if (!$htmlExists && !$textExists)
			throw new Exception('Could not render the html email template or the text email template body for the requested email.');

		// Set the subject.
		$emailMessage->setSubject($emailContent['subject']);

		// Check if this is an HTML email.
		if ($emailMessage->getIsHtml())
		{
			// We were able to render an HTML and Text email template.
			if ($htmlExists && $textExists)
			{
				$emailMessage->setAltBody($emailContent['text']);
				$emailMessage->setBody($emailContent['html']);
			}

			// We found an HTML template, but not a text one.
			elseif ($htmlExists && !$textExists)
			{
				$emailMessage->setBody($emailContent['html']);
			}

			// Found a text template, but not an HTML one, so use the text as the primary body.
			elseif (!$htmlExists && $textExists)
			{
				$emailMessage->setBody($emailContent['text']);
			}
		}
		else
		{
			// This is a text only email, so we ignore anything that was an HTML template.
			if ($textExists)
			{
				$emailMessage->setBody($emailContent['text']);
			}
			else
				throw new Exception('A non-HTML email was specified, but could not render the text template.');
		}

		// Send it!
		if ($this->sendEmail($emailMessage))
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
	public function sendRegistrationEmail(User $user, Site $site)
	{
		$emailSettings = $this->getEmailSettings();
		$email = new EmailMessage(new EmailAddress($emailSettings['emailAddress'], $emailSettings['senderName']), array(new EmailAddress($user->email, $user->first_name.' '.$user->last_name)));

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
		$email = new EmailMessage(new EmailAddress($emailSettings['emailAddress'], $emailSettings['senderName']), array(new EmailAddress($user->email, $user->first_name.' '.$user->last_name)));

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
				throw new Exception('Username and password are required.  Check your email settings.');

			$email->userName = $emailSettings['username'];
			$email->password = $emailSettings['password'];
		}

		if (isset($emailSettings['smtpKeepAlive']) && $emailSettings['smtpKeepAlive'] == 1)
			$email->smtpKeepAlive = true;

		$email->smtpSecure = $emailSettings['smtpSecureTransportType'] != 'none' ? $emailSettings['smtpSecureTransportType'] : null;

		if (!isset($emailSettings['host']))
			throw new Exception('You must specify a host name in your email settings.');

		if (!isset($emailSettings['port']))
			throw new Exception('You must specify a port in your email settings.');

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
		$emailSettings = ArrayHelper::expandSettingsArray($emailSettings);
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
