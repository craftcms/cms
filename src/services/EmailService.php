<?php
namespace Blocks;

/**
 *
 */
class EmailService extends BaseService
{
	private $_defaultEmailTimeout = 10;

	/**
	 * @param EmailMessage $emailMessage
	 * @return bool
	 * @throws Exception
	 */
	public function sendEmail(EmailMessage $emailMessage)
	{
		$emailSettings = $this->emailSettings;

		if (!isset($emailSettings['protocol']))
			throw new Exception('Could not determine how to send the email.  Check your email settings.');

		$email = new \PhpMailer(true);

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

				$pop->authorize($emailSettings['host'], $emailSettings['port'], $emailSettings['timeout'], $emailSettings['username'], $emailSettings['password'], Blocks::app()->config->getItem('devMode') ? 1 : 0);

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

		$email->body = $emailMessage->getBody();
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
		$email->body = $emailMessage->getBody();

		if ($email->altBody !== null)
			$email->altBody = $emailMessage->getAltBody();

		if ($emailMessage->getIsHtml())
		{
			$email->msgHtml($emailMessage->getBody());
		}

		if (!$email->send())
			throw new Exception($email->errorInfo);

		return true;
	}

	/**
	 * @param EmailMessage $emailMessage
	 * @param              $templateFile
	 * @param array        $data
	 *
	 * @return bool
	 */
	public function sendTemplateEmail(EmailMessage $emailMessage, $templateFile, $data = array())
	{
		$renderedTemplate = Blocks::app()->controller->loadEmailTemplate($templateFile, $data);
		$emailMessage->setBody($renderedTemplate);

		if ($this->sendEmail($emailMessage))
			return true;

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
		$email->setIsHtml(true);
		$email->setSubject('Confirm Your Registration');

		if ($this->sendTemplateEmail($email, 'register', array('user' => $user, 'site' => $site)))
			return true;

		return false;
	}

	/**
	 * @param $email
	 * @param $emailSettings
	 */
	private function _setSmtpSettings(&$email, $emailSettings)
	{
		$email->isSmtp();

		if (Blocks::app()->config->getItem('devMode'))
			$email->smtpDebug = 2;

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
		$emailSettings = Blocks::app()->settings->getSystemSettings('email');
		$emailSettings = ArrayHelper::expandSettingsArray($emailSettings);
		return $emailSettings;
	}

	/**
	 * @param $settings
	 * @return bool
	 */
	public function saveEmailSettings($settings)
	{
		if (Blocks::app()->settings->saveSettings('systemsettings', $settings, null, 'email', true))
			return true;

		return false;
	}
}
