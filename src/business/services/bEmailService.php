<?php

/**
 *
 */
class bEmailService extends CApplicationComponent
{
	private $_defaultEmailTimeout = 10;

	/**
	 * @param bEmailMessage $emailMessage
	 * @throws bException
	 */
	public function sendEmail(bEmailMessage $emailMessage)
	{
		$emailSettings = $this->emailSettings;

		if (!isset($emailSettings['emailerType']))
			throw new bException('Could not determine how to send the email.  Check your email settings.');

		$email = new PhpMailer(true);

		switch ($emailSettings['emailerType'])
		{
			case bEmailerType::GmailSmtp:
			case bEmailerType::Smtp:
			{
				$this->_setSmtpSettings($email, $emailSettings);
				break;
			}

			case bEmailerType::Pop:
			{
				$pop = new Pop3();
				if (!isset($emailSettings['host']) || !isset($emailSettings['port']) || !isset($emailSettings['userName']) || !isset($emailSettings['password']) ||
				    bStringHelper::isNullOrEmpty($emailSettings['host']) || bStringHelper::isNullOrEmpty($emailSettings['port']) || bStringHelper::isNullOrEmpty($emailSettings['userName']) || bStringHelper::isNullOrEmpty($emailSettings['password']))
				{
					throw new bException('Host, port, username and password must be configured under your email settings.');
				}

				if (!isset($emailSettings['timeout']))
					$emailSettings['timeout'] = $this->_defaultEmailTimeout;

				$pop->authorize($emailSettings['host'], $emailSettings['port'], $emailSettings['timeout'], $emailSettings['userName'], $emailSettings['password'], Blocks::app()->getConfig('devMode') ? 1 : 0);

				$this->_setSmtpSettings($email, $emailSettings);
				break;
			}

			case bEmailerType::Sendmail:
			{
				$email->isSendmail();
				break;
			}

			case bEmailerType::PhpMail:
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
			throw new bException($email->errorInfo);
	}

	/**
	 * @param bEmailMessage $emailMessage
	 * @param $templateFile
	 */
	public function sendTemplateEmail(bEmailMessage $emailMessage, $templateFile)
	{
		$renderedTemplate = Blocks::app()->controller->loadEmailTemplate($templateFile, array());
		$emailMessage->setBody($renderedTemplate);

		$this->sendEmail($emailMessage);
	}

	/**
	 * @param bRegisterUserForm $registerUserData
	 */
	public function sendRegistrationEmail(bRegisterUserForm $registerUserData)
	{

		$emailSettings = $this->getEmailSettings();
		$email = new bEmailMessage(new bEmailAddress($emailSettings['fromEmail'], $emailSettings['emailName']), array(new bEmailAddress($registerUserData->email, $registerUserData->firstName.' '.$registerUserData->lastName)));
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
			if ((!isset($emailSettings['userName']) && bStringHelper::isNullOrEmpty($emailSettings['userName'])) || (!isset($emailSettings['password']) && bStringHelper::isNullOrEmpty($emailSettings['password'])))
				throw new bException('Username and password are required.  Check your email settings.');

			$email->userName = $emailSettings['userName'];
			$email->password = $emailSettings['password'];
		}

		if (isset($emailSettings['smtpKeepAlive']) && $emailSettings['smtpKeepAlive'] == 1)
			$email->smtpKeepAlive = true;

		if (isset($emailSettings['smtpSecureTransport']) && $emailSettings['smtpSecureTransport'] == 1)
			$email->smtpSecure = strtolower($emailSettings['smtpSecureTransportType']);

		if (!isset($emailSettings['host']))
			throw new bException('You must specify a host name in your email settings.');

		if (!isset($emailSettings['port']))
			throw new bException('You must specify a port in your email settings.');

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
		$emailSettings = bArrayHelper::expandSettingsArray($emailSettings);
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
