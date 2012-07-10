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
	 * Saves the general settings.
	 */
	public function actionSaveGeneralSettings()
	{
		$this->requirePostRequest();

		$settings['url'] = blx()->request->getPost('url');
		$settings['licenseKey'] = blx()->request->getPost('licenseKey');

		if (blx()->settings->saveSettings('systemsettings', $settings, 'general', true))
		{
			blx()->user->setMessage(MessageType::Notice, 'Settings saved.');
			$this->redirectToPostedUrl();
		}
		else
		{
			blx()->user->setMessage(MessageType::Error, 'Couldn’t save settings.');
			$this->loadRequestedTemplate(array('settings' => $settings));
		}
	}

	/**
	 * Saves the email settings.
	 */
	public function actionSaveEmailSettings()
	{
		$this->requirePostRequest();

		$emailSettings = new EmailSettingsForm();
		$gMailSmtp = 'smtp.gmail.com';

		$emailSettings->protocol                    = blx()->request->getPost('protocol');
		$emailSettings->host                        = blx()->request->getPost('host');
		$emailSettings->port                        = blx()->request->getPost('port');
		$emailSettings->smtpAuth                    = (blx()->request->getPost('smtpAuth') === 'y');

		if ($emailSettings->smtpAuth)
		{
			$emailSettings->username                = blx()->request->getPost('smtp_username');
			$emailSettings->password                = blx()->request->getPost('smtp_password');
		}
		else
		{
			$emailSettings->username                = blx()->request->getPost('username');
			$emailSettings->password                = blx()->request->getPost('password');
		}

		$emailSettings->smtpKeepAlive               = (blx()->request->getPost('smtpKeepAlive') === 'y');
		$emailSettings->smtpSecureTransportType     = blx()->request->getPost('smtpSecureTransportType');
		$emailSettings->timeout                     = blx()->request->getPost('timeout');
		$emailSettings->emailAddress                = blx()->request->getPost('emailAddress');
		$emailSettings->senderName                  = blx()->request->getPost('senderName');

		// Validate user input
		if ($emailSettings->validate())
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

			if (blx()->email->saveEmailSettings($settings))
			{
				blx()->user->setMessage(MessageType::Notice, 'Settings saved.');
				$this->redirectToPostedUrl();
			}
			else
			{
				blx()->user->setMessage(MessageType::Error, 'Couldn’t save settings.');
			}
		}
		else
		{
			blx()->user->setMessage(MessageType::Error, 'Couldn’t save settings.');
		}

		$this->loadRequestedTemplate(array('settings' => $emailSettings));
	}

	/**
	 * Saves the language settings.
	 */
	public function actionSaveLanguageSettings()
	{
		$this->requirePostRequest();

		$languages = blx()->request->getPost('languages');
		sort($languages);

		if (blx()->settings->saveSettings('systemsettings', $languages, 'languages', true))
		{
			blx()->user->setMessage(MessageType::Notice, 'Language settings saved.');
			$this->redirectToPostedUrl();
		}
		else
		{
			blx()->user->setMessage(MessageType::Error, 'Couldn’t save language settings.');
			$this->loadRequestedTemplate(array('selectedLanguages' => $languages));
		}
	}

	/**
	 * Saves the advanced settings.
	 */
	public function actionSaveAdvancedSettings()
	{
		$this->requirePostRequest();

		$settings = array();

		$checkboxes = array('showDebugInfo', 'useUncompressedJs', 'disablePlugins');
		foreach ($checkboxes as $key)
		{
			if (blx()->request->getPost($key))
				$settings[$key] = true;
		}

		if (blx()->settings->saveSettings('systemsettings', $settings, 'advanced', true))
		{
			blx()->user->setMessage(MessageType::Notice, 'Settings saved.');
			$this->redirectToPostedUrl();
		}
		else
		{
			blx()->user->setMessage(MessageType::Error, 'Couldn’t save settings.');
			$this->loadRequestedTemplate(array('settings' => $settings));
		}
	}
}
