<?php
namespace Craft;

/**
 *
 */
class EmailService extends BaseApplicationComponent
{
	private $_settings;
	private $_defaultEmailTimeout = 10;

	/**
	 * Sends an email.
	 *
	 * @param EmailModel $emailModel
	 * @param array      $variables
	 * @return bool
	 */
	public function sendEmail(EmailModel $emailModel, $variables = array())
	{
		$user = craft()->users->getUserByUsernameOrEmail($emailModel->toEmail);

		if (!$user)
		{
			$user = new UserModel();
			$user->email = $emailModel->toEmail;
			$user->firstName = $emailModel->toFirstName;
			$user->lastName = $emailModel->toLastName;
		}

		return $this->_sendEmail($user, $emailModel, $variables);
	}

	/**
	 * Sends an email by its key.
	 *
	 * @param UserModel $user
	 * @param string $key
	 * @param array $variables
	 * @return bool
	 * @throws Exception
	 */
	public function sendEmailByKey(UserModel $user, $key, $variables = array())
	{
		$emailModel = new EmailModel();

		if (Craft::hasPackage(CraftPackage::Rebrand))
		{
			$message = craft()->emailMessages->getMessage($key, $user->preferredLocale);

			$emailModel->subject  = $message->subject;
			$emailModel->body     = $message->body;
		}
		else
		{
			$emailModel->subject  = Craft::t($key.'_subject', null, null, 'en_us');
			$emailModel->body     = Craft::t($key.'_body', null, null, 'en_us');
		}

		$tempTemplatesPath = '';

		if (Craft::hasPackage(CraftPackage::Rebrand))
		{
			// Is there a custom HTML template set?
			$settings = $this->getSettings();

			if (!empty($settings['template']))
			{
				$tempTemplatesPath = craft()->path->getSiteTemplatesPath();
				$template = $settings['template'];
			}
		}

		if (empty($template))
		{
			$tempTemplatesPath = craft()->path->getCpTemplatesPath();
			$template = '_special/email';
		}

		if (!$emailModel->htmlBody)
		{
			// Auto-generate the HTML content
			$emailModel->htmlBody = StringHelper::parseMarkdown($emailModel->body);
		}

		$emailModel->htmlBody = "{% extends '{$template}' %}\n".
			"{% set body %}\n".
			$emailModel->htmlBody.
			"{% endset %}\n";

		// Temporarily swap the templates path
		$originalTemplatesPath = craft()->path->getTemplatesPath();
		craft()->path->setTemplatesPath($tempTemplatesPath);

		// Send the email
		$return = $this->_sendEmail($user, $emailModel, $variables);

		// Return to the original templates path
		craft()->path->setTemplatesPath($originalTemplatesPath);

		return $return;
	}

	/**
	 * Gets the system email settings.
	 *
	 * @return array
	 */
	public function getSettings()
	{
		if (!isset($this->_settings))
		{
			$this->_settings = craft()->systemSettings->getSettings('email');
		}

		return $this->_settings;
	}

	/**
	 * @param $settings
	 * @return bool
	 */
	public function sendTestEmail($settings)
	{
		$this->_settings = $settings;

		$user = craft()->userSession->getUser();
		$newSettings = array();

		foreach ($settings as $key => $value)
		{
			if ($key == 'password' && $value)
			{
				$value = 'xxxxx';
			}

			$newSettings[$key] = $value;
		}

		return $this->sendEmailByKey($user, 'test_email', array('settings' => $newSettings));
	}

	/**
	 * @param UserModel  $user
	 * @param EmailModel $emailModel
	 * @param array      $variables
	 * @throws Exception
	 * @return bool
	 */
	private function _sendEmail(UserModel $user, EmailModel $emailModel, $variables = array())
	{
		// Get the saved email settings.
		$emailSettings = $this->getSettings();

		if (!isset($emailSettings['protocol']))
		{
			throw new Exception(Craft::t('Could not determine how to send the email.  Check your email settings.'));
		}

		$email = new \PhpMailer(true);

		// Default the charset to UTF-8
		$email->CharSet = 'UTF-8';

		// Set the "from" information.
		$email->SetFrom($emailModel->fromEmail, $emailModel->fromName);

		// Check which protocol we need to use.
		switch ($emailSettings['protocol'])
		{
			case EmailerType::Gmail:
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
					throw new Exception(Craft::t('Host, port, username and password must be configured under your email settings.'));
				}

				if (!isset($emailSettings['timeout']))
				{
					$emailSettings['timeout'] = $this->_defaultEmailTimeout;
				}

				$pop->authorize($emailSettings['host'], $emailSettings['port'], $emailSettings['timeout'], $emailSettings['username'], $emailSettings['password'], craft()->config->get('devMode') ? 1 : 0);

				$this->_setSmtpSettings($email, $emailSettings);
				break;
			}

			case EmailerType::Sendmail:
			{
				$email->IsSendmail();
				break;
			}

			case EmailerType::Php:
			{
				$email->IsMail();
				break;
			}

			default:
			{
				$email->IsMail();
			}
		}

		// If they have the test email config var set to something, use it instead of the supplied email.
		if (($testToEmail = craft()->config->get('testToEmailAddress')) != '')
		{
			$email->AddAddress($testToEmail, 'Test Email');
		}
		else
		{
			$email->AddAddress($user->email, $user->getFullName());
		}

		// Add any BCC's
		if (!empty($emailModel->bcc))
		{
			foreach ($emailModel->bcc as $bcc)
			{
				if (!empty($bcc['email']))
				{
					$bccEmail = $bcc['email'];

					$bccName = !empty($bcc['name']) ? $bcc['name'] : '';
					$email->AddBcc($bccEmail, $bccName);
				}
			}
		}

		// Add any CC's
		if (!empty($emailModel->cc))
		{
			foreach ($emailModel->cc as $cc)
			{
				if (!empty($cc['email']))
				{
					$ccEmail = $cc['email'];

					$ccName = !empty($cc['name']) ? $cc['name'] : '';
					$email->AddCc($ccEmail, $ccName);
				}
			}
		}

		// Add a reply to (if any)
		if (!empty($emailModel->replyTo))
		{
			$email->AddReplyTo($email->replyTo);
		}

		// Add any string attachments
		if (!empty($emailModel->stringAttachments))
		{
			foreach ($emailModel->stringAttachments as $stringAttachment)
			{
				$email->AddStringAttachment($stringAttachment['string'], $stringAttachment['fileName'], $stringAttachment['encoding'], $stringAttachment['type']);
			}
		}

		// Add any normal disc attachments
		if (!empty($emailModel->attachments))
		{
			foreach ($emailModel->attachments as $attachment)
			{
				$email->AddAttachment($attachment['path'], $attachment['name'], $attachment['encoding'], $attachment['type']);
			}
		}

		$variables['user'] = $user;

		$email->Subject = craft()->templates->renderString($emailModel->subject, $variables);

		// If they populated an htmlBody, use it.
		if ($emailModel->htmlBody)
		{
			$renderedHtmlBody = craft()->templates->renderString($emailModel->htmlBody, $variables);
			$email->MsgHTML($renderedHtmlBody);
			$email->AltBody = craft()->templates->renderString($emailModel->body, $variables);
		}
		else
		{
			// They didn't provide an htmlBody, so markdown the body.
			$renderedHtmlBody = craft()->templates->renderString(StringHelper::parseMarkdown($emailModel->body), $variables);
			$email->MsgHTML($renderedHtmlBody);
			$email->AltBody = craft()->templates->renderString($emailModel->body, $variables);
		}

		if (!$email->Send())
		{
			throw new Exception(Craft::t('Email error: {error}', array('error' => $email->ErrorInfo)));
		}

		return true;
	}

	/**
	 * @param $email
	 * @param $emailSettings
	 * @throws Exception
	 */
	private function _setSmtpSettings(&$email, $emailSettings)
	{
		$email->IsSmtp();

		if (isset($emailSettings['smtpAuth']) && $emailSettings['smtpAuth'] == 1)
		{
			$email->SMTPAuth = true;

			if ((!isset($emailSettings['username']) && StringHelper::isNullOrEmpty($emailSettings['username'])) || (!isset($emailSettings['password']) && StringHelper::isNullOrEmpty($emailSettings['password'])))
			{
				throw new Exception(Craft::t('Username and password are required.  Check your email settings.'));
			}

			$email->Username = $emailSettings['username'];
			$email->Password = $emailSettings['password'];
		}

		if (isset($emailSettings['smtpKeepAlive']) && $emailSettings['smtpKeepAlive'] == 1)
		{
			$email->SMTPKeepAlive = true;
		}

		$email->SMTPSecure = $emailSettings['smtpSecureTransportType'] != 'none' ? $emailSettings['smtpSecureTransportType'] : null;

		if (!isset($emailSettings['host']))
		{
			throw new Exception(Craft::t('You must specify a host name in your email settings.'));
		}

		if (!isset($emailSettings['port']))
		{
			throw new Exception(Craft::t('You must specify a port in your email settings.'));
		}

		if (!isset($emailSettings['timeout']))
		{
			$emailSettings['timeout'] = $this->_defaultEmailTimeout;
		}

		$email->Host = $emailSettings['host'];
		$email->Port = $emailSettings['port'];
		$email->Timeout = $emailSettings['timeout'];
	}
}
