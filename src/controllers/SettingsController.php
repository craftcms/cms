<?php
namespace Blocks;

/**
 * Handles settings from the control panel.
 */
class SettingsController extends BaseController
{
	/**
	 * All settings actions require the user to be logged in
	 */
	public function run()
	{
		$this->requireLogin();
	}

	/**
	 *
	 */
	public function actionSaveEmailSettings()
	{
		$this->requirePostRequest();

		$emailSettings = new EmailSettingsForm();
		$gMailSmtp = 'smtp.gmail.com';

		$emailSettings->protocol                    = Blocks::app()->request->getPost('protocol');
		$emailSettings->host                        = Blocks::app()->request->getPost('host');
		$emailSettings->port                        = Blocks::app()->request->getPost('port');
		$emailSettings->smtpAuth                    = (Blocks::app()->request->getPost('smtpAuth') === 'y');
		$emailSettings->username                    = Blocks::app()->request->getPost('username');
		$emailSettings->password                    = Blocks::app()->request->getPost('password');
		$emailSettings->smtpKeepAlive               = (Blocks::app()->request->getPost('smtpKeepAlive') === 'y');
		$emailSettings->smtpSecureTransportType     = Blocks::app()->request->getPost('smtpSecureTransportType');
		$emailSettings->timeout                     = Blocks::app()->request->getPost('timeout');
		$emailSettings->emailAddress                = Blocks::app()->request->getPost('emailAddress');
		$emailSettings->senderName                  = Blocks::app()->request->getPost('senderName');

		// validate user input
		if($emailSettings->validate())
		{
			$settings = array('protocol' => $emailSettings->protocol);
			$settings['emailAddress'] = $emailSettings->emailAddress;
			$settings['senderName'] = $emailSettings->senderName;

			switch ($emailSettings->protocol)
			{
				case EmailerType::Smtp:
				{
					if ($emailSettings->smtpAuth)
					{
						$settings['smtpAuth'] = 1;
						$settings['username'] = $emailSettings->username;
						$settings['password'] = $emailSettings->password;
					}

					$settings['smtpSecureTransportType'] = $emailSettings->smtpSecureTransportType;

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
					$settings['username'] = $emailSettings->username;
					$settings['password'] = $emailSettings->password;
					$settings['timeout'] = $emailSettings->timeout;

					break;
				}

				case EmailerType::GmailSmtp:
				{
					$settings['host'] = $gMailSmtp;
					$settings['smtpAuth'] = 1;
					$settings['smtpSecureTransportType'] = 'tls';
					$settings['username'] = $emailSettings->username;
					$settings['password'] = $emailSettings->password;
					$settings['port'] = $emailSettings->smtpSecureTransportType == 'tls' ? '587' : '465';
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

