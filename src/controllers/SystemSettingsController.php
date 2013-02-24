<?php
namespace Craft;

/**
 * Handles settings from the control panel.
 */
class SystemSettingsController extends BaseController
{

	/**
	 * Saves the general settings.
	 */
	public function actionSaveGeneralSettings()
	{
		$this->requirePostRequest();

		$generalSettingsModel = new GeneralSettingsModel();
		$generalSettingsModel->on         = (bool) craft()->request->getPost('isSystemOn');
		$generalSettingsModel->siteName   = craft()->request->getPost('siteName');
		$generalSettingsModel->siteUrl    = craft()->request->getPost('siteUrl');
		$generalSettingsModel->licenseKey = craft()->request->getPost('licenseKey');

		if ($generalSettingsModel->validate())
		{
			$info = InfoRecord::model()->find();
			$info->on = $generalSettingsModel->on;
			$info->siteName = $generalSettingsModel->siteName;
			$info->siteUrl = $generalSettingsModel->siteUrl;
			$info->licenseKey = $generalSettingsModel->licenseKey;
			$info->save();

			craft()->userSession->setNotice(Craft::t('General settings saved.'));
			$this->redirectToPostedUrl();
		}
		else
		{
			craft()->userSession->setError(Craft::t('Couldn’t save general settings.'));

			$this->renderRequestedTemplate(array(
				'post' => $generalSettingsModel
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

		$this->renderRequestedTemplate(array(
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
