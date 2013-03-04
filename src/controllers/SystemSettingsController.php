<?php
namespace Craft;

/**
 * Handles settings from the control panel.
 */
class SystemSettingsController extends BaseController
{
	/**
	 * Init
	 */
	public function init()
	{
		// All System Settings actions require an admin
		craft()->userSession->requireAdmin();
	}

	/**
	 * Saves the general settings.
	 */
	public function actionSaveGeneralSettings()
	{
		$this->requirePostRequest();

		$info = Craft::getInfo();

		$info->on       = (bool) craft()->request->getPost('on');
		$info->siteName = craft()->request->getPost('siteName');
		$info->siteUrl  = craft()->request->getPost('siteUrl');

		if (Craft::saveInfo($info))
		{
			craft()->userSession->setNotice(Craft::t('General settings saved.'));
			$this->redirectToPostedUrl();
		}
		else
		{
			craft()->userSession->setError(Craft::t('Couldn’t save general settings.'));

			// Send the info back to the template
			craft()->urlManager->setRouteVariables(array(
				'info' => $info
			));
		}
	}

	/**
	 * Saves the email settings.
	 */
	public function actionSaveEmailSettings()
	{
		$this->requirePostRequest();

		$settings = $this->_getEmailSettingsFromPost();

		if ($settings !== false)
		{
			if (craft()->systemSettings->saveSettings('email', $settings))
			{
				craft()->userSession->setNotice(Craft::t('Email settings saved.'));
				$this->redirectToPostedUrl();
			}
		}

		craft()->userSession->setError(Craft::t('Couldn’t save email settings.'));

		// Send the settings back to the template
		craft()->urlManager->setRouteVariables(array(
			'settings' => $settings
		));
	}

	/**
	 * Tests the email settings.
	 */
	public function actionTestEmailSettings()
	{
		$this->requirePostRequest();
		$this->requireAjaxRequest();

		$settings = $this->_getEmailSettingsFromPost();

		if ($settings !== false)
		{
			try
			{
				if (craft()->email->sendTestEmail($settings))
				{
					$this->returnJson(array('success' => true));
				}
			}
			catch (\Exception $e)
			{
				Craft::log($e->getMessage(), \CLogger::LEVEL_ERROR);
			}
		}

		$this->returnErrorJson(Craft::t('There was an error testing your email settings.'));
	}

	/**
	 * Returns the email settings from the post data.
	 *
	 * @access private
	 * @return array
	 */
	private function _getEmailSettingsFromPost()
	{
		$emailSettings = new EmailSettingsModel();
		$gMailSmtp = 'smtp.gmail.com';

		$emailSettings->protocol                    = craft()->request->getPost('protocol');
		$emailSettings->host                        = craft()->request->getPost('host');
		$emailSettings->port                        = craft()->request->getPost('port');
		$emailSettings->smtpAuth                    = (bool)craft()->request->getPost('smtpAuth');

		if ($emailSettings->smtpAuth && $emailSettings->protocol !== EmailerType::Gmail)
		{
			$emailSettings->username                = craft()->request->getPost('smtpUsername');
			$emailSettings->password                = craft()->request->getPost('smtpPassword');
		}
		else
		{
			$emailSettings->username                = craft()->request->getPost('username');
			$emailSettings->password                = craft()->request->getPost('password');
		}

		$emailSettings->smtpKeepAlive               = (bool)craft()->request->getPost('smtpKeepAlive');
		$emailSettings->smtpSecureTransportType     = craft()->request->getPost('smtpSecureTransportType');
		$emailSettings->timeout                     = craft()->request->getPost('timeout');
		$emailSettings->emailAddress                = craft()->request->getPost('emailAddress');
		$emailSettings->senderName                  = craft()->request->getPost('senderName');

		// Validate user input
		if (!$emailSettings->validate())
		{
			return false;
		}

		$settings['protocol']     = $emailSettings->protocol;
		$settings['emailAddress'] = $emailSettings->emailAddress;
		$settings['senderName']   = $emailSettings->senderName;

		if (Craft::hasPackage(CraftPackage::Rebrand))
		{
			$settings['template'] = craft()->request->getPost('template');
		}

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

			case EmailerType::Gmail:
			{
				$settings['host'] = $gMailSmtp;
				$settings['smtpAuth'] = 1;
				$settings['smtpSecureTransportType'] = 'ssl';
				$settings['username'] = $emailSettings->username;
				$settings['password'] = $emailSettings->password;
				$settings['port'] = $emailSettings->smtpSecureTransportType == 'tls' ? '587' : '465';
				$settings['timeout'] = $emailSettings->timeout;
				break;
			}
		}

		return $settings;
	}
}
