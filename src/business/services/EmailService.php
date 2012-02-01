<?php
namespace Blocks;

/**
 *
 */
class EmailService extends \CApplicationComponent
{
	private $_defaultEmailTimeout = 10;

	/**
	 * @param EmailMessage $emailMessage
	 * @throws Exception
	 */
	public function sendEmail(EmailMessage $emailMessage)
	{
		$emailSettings = $this->emailSettings;

		if (!isset($emailSettings['emailerType']))
			throw new Exception('Could not determine how to send the email.  Check your email settings.');

		$email = new \PhpMailer(true);

		switch ($emailSettings['emailerType'])
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
				if (!isset($emailSettings['host']) || !isset($emailSettings['port']) || !isset($emailSettings['userName']) || !isset($emailSettings['password']) ||
				    StringHelper::isNullOrEmpty($emailSettings['host']) || StringHelper::isNullOrEmpty($emailSettings['port']) || StringHelper::isNullOrEmpty($emailSettings['userName']) || StringHelper::isNullOrEmpty($emailSettings['password']))
				{
					throw new Exception('Host, port, username and password must be configured under your email settings.');
				}

				if (!isset($emailSettings['timeout']))
					$emailSettings['timeout'] = $this->_defaultEmailTimeout;

				$pop->authorize($emailSettings['host'], $emailSettings['port'], $emailSettings['timeout'], $emailSettings['userName'], $emailSettings['password'], Blocks::app()->getConfig('devMode') ? 1 : 0);

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
	}

	/**
	 * @param EmailMessage $emailMessage
	 * @param $templateFile
	 */
	public function sendTemplateEmail(EmailMessage $emailMessage, $templateFile)
	{
		$renderedTemplate = Blocks::app()->controller->loadEmailTemplate($templateFile, array());
		$emailMessage->setBody($renderedTemplate);

		$this->sendEmail($emailMessage);
	}

	/**
	 * @param RegisterUserForm $registerUserData
	 */
	public function sendRegistrationEmail(RegisterUserForm $registerUserData)
	{

		$emailSettings = $this->getEmailSettings();
		$email = new EmailMessage(new EmailAddress($emailSettings['fromEmail'], $emailSettings['emailName']), array(new EmailAddress($registerUserData->email, $registerUserData->firstName.' '.$registerUserData->lastName)));
		$email->setIsHtml(true);
		$email->setSubject('Confirm Your Registration');
		Blocks::app()->email->sendTemplateEmail($email, 'register');
	}

	/**
	 * @param $email
	 * @param $emailSettings
	 */
	private function _setSmtpSettings(&$email, $emailSettings)
	{
		$email->isSmtp();

		if (Blocks::app()->getConfig('devMode'))
			$email->smtpDebug = 2;

		if (isset($emailSettings['smtpAuth']) && $emailSettings['smtpAuth'] == 1)
		{
			$email->smtpAuth = true;
			if ((!isset($emailSettings['userName']) && StringHelper::isNullOrEmpty($emailSettings['userName'])) || (!isset($emailSettings['password']) && StringHelper::isNullOrEmpty($emailSettings['password'])))
				throw new Exception('Username and password are required.  Check your email settings.');

			$email->userName = $emailSettings['userName'];
			$email->password = $emailSettings['password'];
		}

		if (isset($emailSettings['smtpKeepAlive']) && $emailSettings['smtpKeepAlive'] == 1)
			$email->smtpKeepAlive = true;

		if (isset($emailSettings['smtpSecureTransport']) && $emailSettings['smtpSecureTransport'] == 1)
			$email->smtpSecure = strtolower($emailSettings['smtpSecureTransportType']);

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
