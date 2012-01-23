<?php

/**
 *
 */
class bEmailService extends CApplicationComponent
{
	private $_defaultEmailTimeout = 10;

	/**
	 * @param \bEmailAddress $from
	 * @param \bEmailAddress $replyTo
	 * @param array          $to
	 * @param array          $cc
	 * @param array          $bcc
	 * @param                $subject
	 * @param                $body
	 * @param string         $altBody
	 * @param bool           $isHTML
	 *
	 * @internal param $fromEmail
	 * @internal param $fromName
	 * @internal param $replyToEmail
	 * @internal param $replyToName
	 */
	public function sendEmail(bEmailAddress $from, bEmailAddress $replyTo, array $to, array $cc, array $bcc, $subject, $body, $altBody = null, $isHTML = true)
	{
		if (bStringHelper::isNullOrEmpty($from->getEmailAddress()))
			throw new bException('You must supply an email address that the email is sent from.');

		if (empty($to))
			throw new bException('You must supply at least one email address to send the email to.');

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
				if (!isset($emailSettings['hostName']) || !isset($emailSettings['port']) || !isset($emailSettings['userName']) || !isset($emailSettings['password']) ||
				    bStringHelper::isNullOrEmpty($emailSettings['hostName']) || bStringHelper::isNullOrEmpty($emailSettings['port']) || bStringHelper::isNullOrEmpty($emailSettings['userName']) || bStringHelper::isNullOrEmpty($emailSettings['password']))
				{
					throw new bException('Hostname, port, username and password must be configured under your email settings.');
				}

				if (!isset($emailSettings['timeout']))
					$emailSettings['timeout'] = $this->_defaultEmailTimeout;

				$pop->authorize($emailSettings['hostName'], $emailSettings['port'], $emailSettings['timeout'], $emailSettings['userName'], $emailSettings['password'], Blocks::app()->getConfig('devMode') ? 1 : 0);

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

		$email->body = $body;
		$email->from = $from->getEmailAddress();
		$email->fromName = $from->getName();
		$email->addReplyTo($replyTo->getEmailAddress(), $replyTo->getName());

		foreach ($to as $toAddress)
		{
			$email->addAddress($toAddress->getEmailAddress(), $toAddress->getName());
		}

		foreach ($cc as $toCcAddress)
		{
			$email->addCc($toCcAddress->getEmailAddress(), $toCcAddress->getName());
		}

		foreach ($bcc as $toBccAddress)
		{
			$email->addBcc($toBccAddress->getEmailAddress(), $toBccAddress->getName());
		}

		$email->subject = $subject;
		$email->body = $body;

		if ($email->altBody !== null)
			$email->altBody = $altBody;

		if ($isHTML)
		{
			$email->isHtml(true);
			$email->msgHtml($body);

		}

		if (!$email->send())
		{
			$error = $email->errorInfo;
		}
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
