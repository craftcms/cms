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
	public function actionSaveEmailSettings()
	{
		$this->requirePostRequest();

		$emailSettings = new EmailSettingsForm();
		$gMailSmtp = 'smtp.gmail.com';

		$emailSettings->emailerType                 = Blocks::app()->request->getPost('emailerType');
		$emailSettings->host                        = Blocks::app()->request->getPost('host');
		$emailSettings->port                        = Blocks::app()->request->getPost('port');
		$emailSettings->smtpAuth                    = (Blocks::app()->request->getPost('smtpAuth') === 'y');
		$emailSettings->userName                    = Blocks::app()->request->getPost('userName');
		$emailSettings->password                    = Blocks::app()->request->getPost('password');
		$emailSettings->smtpKeepAlive               = (Blocks::app()->request->getPost('smtpKeepAlive') === 'y');
		$emailSettings->smtpSecureTransport         = (Blocks::app()->request->getPost('smtpSecureTransport') === 'y');
		$emailSettings->smtpSecureTransportType     = Blocks::app()->request->getPost('smtpSecureTransportType');
		$emailSettings->timeout                     = Blocks::app()->request->getPost('timeout');
		$emailSettings->fromEmail                   = Blocks::app()->request->getPost('fromEmail');
		$emailSettings->fromName                    = Blocks::app()->request->getPost('fromName');

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

