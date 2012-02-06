<?php
namespace Blocks;

/**
 * Handles settings from the control panel.
 */
class SettingsController extends BaseController
{
	/**
	 *
	 */
	public function actionSaveEmail()
	{
		$this->requirePostRequest();

		$emailSettings = new EmailSettingsForm();
		$gMailSmtp = 'smtp.gmail.com';

		$postEmailSettings = Blocks::app()->request->getPost('email');
		$emailSettings->emailerType                 = $postEmailSettings['emailerType'];
		$emailSettings->host                        = $postEmailSettings['host'];
		$emailSettings->port                        = $postEmailSettings['port'];
		$emailSettings->smtpAuth                    = isset($postEmailSettings['smtpAuth']) ? 1 : 0;
		$emailSettings->userName                    = $postEmailSettings['userName'];
		$emailSettings->password                    = $postEmailSettings['password'];
		$emailSettings->smtpKeepAlive               = isset($postEmailSettings['smtpKeepAlive']) ? 1 : 0;
		$emailSettings->smtpSecureTransport         = isset($postEmailSettings['smtpSecureTransport']) ? 1 : 0;
		$emailSettings->smtpSecureTransportType     = $postEmailSettings['smtpSecureTransportType'];
		$emailSettings->timeout                     = $postEmailSettings['timeout'];
		$emailSettings->fromEmail                   = $postEmailSettings['fromEmail'];
		$emailSettings->fromName                    = $postEmailSettings['fromName'];

		// validate user input
		if($emailSettings->validate())
		{
			$settings = array('emailerType' => $emailSettings->emailerType);
			$settings['fromEmail'] = $emailSettings->fromEmail;
			$settings['fromName'] = $emailSettings->fromName;

			switch ($emailSettings->emailerType)
			{
				case EmailerType::Smtp:
				{
					if ($emailSettings->smtpAuth)
					{
						$settings['smtpAuth'] = 1;
						$settings['userName'] = $emailSettings->userName;
						$settings['password'] = $emailSettings->password;
					}

					if ($emailSettings->smtpSecureTransport)
					{
						$settings['smtpSecureTransport'] = 1;
						$settings['smtpSecureTransportType'] = $emailSettings->smtpSecureTransportType;
					}

					$settings['port'] = $emailSettings->port;
					$settings['host'] = $emailSettings->host;
					$settings['timeout'] = $emailSettings->timeout;

					if ($emailSettings->smtpKeepAlive)
					{
						$settings['smtpKeepAlive'] = 1;
					}

					break;
				}

				case EmailerType::Pop:
				{
					$settings['port'] = $emailSettings->port;
					$settings['host'] = $emailSettings->host;
					$settings['userName'] = $emailSettings->userName;
					$settings['password'] = $emailSettings->password;
					$settings['timeout'] = $emailSettings->timeout;

					break;
				}

				case EmailerType::GmailSmtp:
				{
					$settings['host'] = $gMailSmtp;
					$settings['smtpAuth'] = 1;
					$settings['smtpSecureTransport'] = 1;
					$settings['smtpSecureTransportType'] = $emailSettings->smtpSecureTransportType;
					$settings['userName'] = $emailSettings->userName;
					$settings['password'] = $emailSettings->password;
					$settings['port'] = $emailSettings->smtpSecureTransportType == 'Tls' ? '587' : '465';
					$settings['timeout'] = $emailSettings->timeout;
					break;
				}
			}

			if (Blocks::app()->email->saveEmailSettings($settings))
			{
				Blocks::app()->user->setMessage(MessageStatus::Success, 'Settings updated successfully.');

				$url = Blocks::app()->request->getPost('redirect');
				if ($url !== null)
					$this->redirect($url);
			}
		}

		$this->loadRequestedTemplate(array('emailSettings' => $emailSettings));
	}
}

