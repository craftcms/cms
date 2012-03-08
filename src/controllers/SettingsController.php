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
	public function init()
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

		$emailSettings->protocol                    = b()->request->getPost('protocol');
		$emailSettings->host                        = b()->request->getPost('host');
		$emailSettings->port                        = b()->request->getPost('port');
		$emailSettings->smtpAuth                    = (b()->request->getPost('smtpAuth') === 'y');

		if ($emailSettings->smtpAuth)
		{
			$emailSettings->username                = b()->request->getPost('smtp_username');
			$emailSettings->password                = b()->request->getPost('smtp_password');
		}
		else
		{
			$emailSettings->username                = b()->request->getPost('username');
			$emailSettings->password                = b()->request->getPost('password');
		}

		$emailSettings->smtpKeepAlive               = (b()->request->getPost('smtpKeepAlive') === 'y');
		$emailSettings->smtpSecureTransportType     = b()->request->getPost('smtpSecureTransportType');
		$emailSettings->timeout                     = b()->request->getPost('timeout');
		$emailSettings->emailAddress                = b()->request->getPost('emailAddress');
		$emailSettings->senderName                  = b()->request->getPost('senderName');

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

			if (b()->email->saveEmailSettings($settings))
			{
				b()->user->setMessage(MessageType::Notice, 'Email settings saved.');

				$url = b()->request->getPost('redirect');
				if ($url !== null)
					$this->redirect($url);
			}
			else
			{
				b()->user->setMessage(MessageType::Error, 'Couldn’t save email settings.');
			}
		}
		else
		{
			b()->user->setMessage(MessageType::Error, 'Couldn’t save email settings.');
		}

		$this->loadRequestedTemplate(array('emailSettings' => $emailSettings));
	}
}

