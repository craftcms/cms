<?php
namespace Blocks;

/**
 *
 */
class EmailService extends BaseApplicationComponent
{
	private $_defaultEmailTimeout = 10;

	/**
	 * Sends an email.
	 *
	 * @param UserModel $user
	 * @param string $subject
	 * @param string $body
	 * @param string $htmlBody
	 * @param array $variables
	 * @return bool
	 * @throws Exception
	 */
	public function sendEmail(UserModel $user, $subject, $body, $htmlBody = null, $variables = array())
	{
		// Get the saved email settings.
		$emailSettings = $this->getSettings();

		if (!isset($emailSettings['protocol']))
		{
			throw new Exception(Blocks::t('Could not determine how to send the email.  Check your email settings.'));
		}

		$email = new \PhpMailer(true);

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
					throw new Exception(Blocks::t('Host, port, username and password must be configured under your email settings.'));
				}

				if (!isset($emailSettings['timeout']))
				{
					$emailSettings['timeout'] = $this->_defaultEmailTimeout;
				}

				$pop->authorize($emailSettings['host'], $emailSettings['port'], $emailSettings['timeout'], $emailSettings['username'], $emailSettings['password'], blx()->config->devMode ? 1 : 0);

				$this->_setSmtpSettings($email, $emailSettings);
				break;
			}

			case EmailerType::Sendmail:
			{
				$email->isSendmail();
				break;
			}

			case EmailerType::Php:
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

		$variables['user'] = new UserVariable($user);

		$email->subject = blx()->templates->renderString($subject.' - subject', $subject, $variables);
		$renderedBody = blx()->templates->renderString($subject.' - body', $body, $variables);

		if ($user->emailFormat == 'html' && $htmlBody)
		{
			$renderedHtmlBody = blx()->templates->renderString($subject.' - HTML body', $htmlBody, $variables);
			$email->msgHtml($renderedHtmlBody);
			$email->altBody = $renderedBody;
		}
		else
		{
			$email->body = $renderedBody;
		}

		if (!$email->send())
		{
			throw new Exception(Blocks::t('Email error: {error}', array('error' => $email->errorInfo)));
		}

		return true;
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
		if (Blocks::hasPackage(BlocksPackage::Rebrand))
		{
			$message = blx()->emailMessages->getMessage($key, $user->language);

			$subject  = $message->subject;
			$body     = $message->body;
			$htmlBody = $message->htmlBody;
		}
		else
		{
			$subject  = Blocks::t($key.'_subject');
			$body     = Blocks::t($key.'_body');
			$htmlBody = Blocks::t($key.'_html_body');
		}

		$tempTemplatesPath = '';

		if (Blocks::hasPackage(BlocksPackage::Rebrand))
		{
			// Is there a custom HTML template set?
			$settings = $this->getSettings();

			if (!empty($settings['template']))
			{
				$tempTemplatesPath = blx()->path->getSiteTemplatesPath();
				$template = $settings['template'];
			}
		}

		if (empty($template))
		{
			$tempTemplatesPath = blx()->path->getCpTemplatesPath();
			$template = '_special/email';
		}

		if (!$htmlBody || $htmlBody == $key.'_html_body')
		{
			// Auto-generate the HTML content
			if (!class_exists('\Markdown_Parser', false))
			{
				require_once blx()->path->getFrameworkPath().'vendors/markdown/markdown.php';
			}

			$md = new \Markdown_Parser();
			$htmlBody = $md->transform($body);
		}

		$htmlBody = "{% extends '{$template}' %}\n" .
			"{% block body %}\n" .
			$htmlBody .
			"{% endblock %}\n";

		// Temporarily swap the templates path
		$originalTemplatesPath = blx()->path->getTemplatesPath();
		blx()->path->setTemplatesPath($tempTemplatesPath);

		// Send the email
		$return = $this->sendEmail($user, $subject, $body, $htmlBody, $variables);

		// Return to the original templates path
		blx()->path->setTemplatesPath($originalTemplatesPath);

		return $return;
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
			{
				throw new Exception(Blocks::t('Username and password are required.  Check your email settings.'));
			}

			$email->userName = $emailSettings['username'];
			$email->password = $emailSettings['password'];
		}

		if (isset($emailSettings['smtpKeepAlive']) && $emailSettings['smtpKeepAlive'] == 1)
		{
			$email->smtpKeepAlive = true;
		}

		$email->smtpSecure = $emailSettings['smtpSecureTransportType'] != 'none' ? $emailSettings['smtpSecureTransportType'] : null;

		if (!isset($emailSettings['host']))
		{
			throw new Exception(Blocks::t('You must specify a host name in your email settings.'));
		}

		if (!isset($emailSettings['port']))
		{
			throw new Exception(Blocks::t('You must specify a port in your email settings.'));
		}

		if (!isset($emailSettings['timeout']))
		{
			$emailSettings['timeout'] = $this->_defaultEmailTimeout;
		}

		$email->host = $emailSettings['host'];
		$email->port = $emailSettings['port'];
		$email->timeout = $emailSettings['timeout'];
	}

	/**
	 * Gets the system email settings.
	 *
	 * @return array
	 */
	public function getSettings()
	{
		return blx()->systemSettings->getSettings('email');
	}

	/**
	 * Saves the system email settings.
	 *
	 * @param array $settings
	 * @return bool
	 */
	public function saveSettings($settings)
	{
		return blx()->systemSettings->saveSettings('email', $settings);
	}
}
