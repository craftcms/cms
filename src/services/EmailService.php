<?php
namespace Blocks;

/**
 *
 */
class EmailService extends \CApplicationComponent
{
	private $_defaultEmailTimeout = 10;

	/**
	 * Sends an email.
	 *
	 * @param User $user
	 * @param string $subject
	 * @param string $body
	 * @param string $htmlBody
	 * @param array $variables
	 * @return bool
	 * @throws Exception
	 */
	public function sendEmail(User $user, $subject, $body, $htmlBody = null, $variables = array())
	{
		// Get the saved email settings.
		$emailSettings = $this->getEmailSettings();

		if (!isset($emailSettings['protocol']))
			throw new Exception(Blocks::t('Could not determine how to send the email.  Check your email settings.'));

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
					throw new Exception(Blocks::t('Host, port, username and password must be configured under your email settings.'));
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

		// Set the From/To fields
		$email->from = $emailSettings['emailAddress'];
		$email->fromName = $emailSettings['senderName'];
		$email->addAddress($user->email, $user->getFullName());

		$variables['user'] = $user;

		$email->subject = TemplateHelper::renderString($subject.' subject', $subject, $variables);
		$renderedBody = TemplateHelper::renderString($subject.' body', $body, $variables);

		if ($user->email_format == 'html' && $htmlBody)
		{
			$renderedHtmlBody = TemplateHelper::renderString($subject.' HTML body', $htmlBody, $variables);
			$email->msgHtml($renderedHtmlBody);
			$email->altBody = $renderedBody;
		}
		else
		{
			$email->body = $renderedBody;
		}

		if (!$email->send())
			throw new Exception(Blocks::t('Email error: {errorMessage}', array('errorMessage' => $email->errorInfo)));

		return true;
	}

	/**
	 * Sends an email by its key.
	 *
	 * @param User $user
	 * @param string $key
	 * @param int $pluginId
	 * @param array $variables
	 * @return bool
	 * @throws Exception
	 */
	public function sendEmailByKey(User $user, $key, $pluginId = null, $variables = array())
	{
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
				throw new Exception(Blocks::t('Username and password are required.  Check your email settings.'));

			$email->userName = $emailSettings['username'];
			$email->password = $emailSettings['password'];
		}

		if (isset($emailSettings['smtpKeepAlive']) && $emailSettings['smtpKeepAlive'] == 1)
			$email->smtpKeepAlive = true;

		$email->smtpSecure = $emailSettings['smtpSecureTransportType'] != 'none' ? $emailSettings['smtpSecureTransportType'] : null;

		if (!isset($emailSettings['host']))
			throw new Exception(Blocks::t('You must specify a host name in your email settings.'));

		if (!isset($emailSettings['port']))
			throw new Exception(Blocks::t('You must specify a port in your email settings.'));

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
