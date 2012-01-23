<?php

/**
 *
 */
class bEmailService extends CApplicationComponent
{
	/**
	 * @param        $from
	 * @param        $replyTo
	 * @param        $to
	 * @param        $subject
	 * @param        $body
	 * @param string $altBody
	 * @param bool   $isHTML
	 *
	 * @internal param $fromEmail
	 * @internal param $fromName
	 * @internal param $replyToEmail
	 * @internal param $replyToName
	 */
	public function sendEmail($from, $replyTo, $to, $subject, $body, $altBody = '', $isHTML = true)
	{
		// methods to support
		// 1) mail() // tested with localhost
		// 2) sendmail() // can't test on windows, but installed on some/most? unix servers
		// 3) pop before smtp
		// 4) gmail smtp // tested make sure openssl is enabled in php.ini and you use ssl port 465 or tls port 587
		// 5) smtp no auth // tested on localhost
		// 6) smtp auth

		// SETTINGS
		// get host
		// get emailertype
		//
		//

		$emailSettings = $this->emailSettings;
		$email = new PhpMailer(true);

		switch ($emailSettings['email']->emailerType)
		{
			case bEmailerType::Smtp:
			{
				$this->_setSmtpSettings($email, $emailSettings);
				break;
			}

			case bEmailerType::GmailSmtp:
			{
				$this->_setSmtpSettings($email, $emailSettings);

				// or tls port 587
				$email->port = '465';
				$email->smtpSecure = 'ssl';
				$email->host = 'smtp.gmail.com';
				break;
			}

			case bEmailerType::Pop:
			{
				$pop = new Pop3();
				$pop->authorize($emailSettings['email']->hostName, 110, 30, 'username', 'password', 1);

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

		$body = 'Html Hello.';

		//$email->Host = 'secure.emailsrvr.com';
		//$email->Port = 25;
		//$email->Username = 'brad@pixelandtonic.com';
		//$email->Password = 'WqYJ3IsKbc1erC';

		$email->from = 'brad@pixelandtonic.com';
		$email->fromName = 'Blocks Admin';
		$email->subject = 'This is a very important subject.';
		$email->altBody = 'Plain Text Hello.';

		$email->msgHtml($body);

		$email->addReplyTo('brad@pixelandtonic.com', 'Blocks Admin');
		$email->addAddress('takobell@gmail.com', 'Brad Bell');

		$email->isHtml(true);

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

		if (Blocks::app()->config('devMode'))
			$email->smtpDebug = 2;

		if ($emailSettings['email']->smtpAuth)
		{
			$email->smtpAuth = true;
			$email->userName = $emailSettings['email']->userName;
			$email->password = $emailSettings['email']->password;
		}

		if ($emailSettings['email']->keepAlive)
			$email->smtpKeepAlive = true;

		if ($emailSettings['email']->smtpSecure !== '')
			$email->smtpSecure = $emailSettings['email']->smtpSecure;

		$email->host = implode(';', $emailSettings['email']->hostName);
		$email->port = $emailSettings['email']->port;
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
