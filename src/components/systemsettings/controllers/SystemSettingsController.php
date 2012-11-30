<?php
namespace Blocks;

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
		$generalSettingsModel->isSystemOn = (bool) blx()->request->getPost('isSystemOn');
		$generalSettingsModel->siteName = blx()->request->getPost('siteName');
		$generalSettingsModel->siteUrl = blx()->request->getPost('siteUrl');
		$generalSettingsModel->licenseKey = blx()->request->getPost('licenseKey');

		if ($generalSettingsModel->validate())
		{
			$info = InfoRecord::model()->find();
			$info->on = $generalSettingsModel->isSystemOn;
			$info->siteName = $generalSettingsModel->siteName;
			$info->siteUrl = $generalSettingsModel->siteUrl;
			$info->licenseKey = $generalSettingsModel->licenseKey;
			$info->save();

			blx()->user->setNotice(Blocks::t('General settings saved.'));
			$this->redirectToPostedUrl();
		}
		else
		{
			blx()->user->setError(Blocks::t('Couldn’t save general settings.'));

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
			if (blx()->systemSettings->saveSettings('email', $settings))
			{
				blx()->user->setNotice(Blocks::t('Email settings saved.'));
				$this->redirectToPostedUrl();
			}
		}

		blx()->user->setError(Blocks::t('Couldn’t save email settings.'));

		$this->renderRequestedTemplate(array(
			'settings' => $emailSettings
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
				if (blx()->email->sendTestEmail($settings))
				{
					$this->returnJson(array('success' => true));
				}
			}
			catch (\Exception $e)
			{
				Blocks::log($e->getMessage(), \CLogger::LEVEL_ERROR);
			}
		}

		$this->returnErrorJson(Blocks::t('There was an error testing your email settings.'));
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

		$emailSettings->protocol                    = blx()->request->getPost('protocol');
		$emailSettings->host                        = blx()->request->getPost('host');
		$emailSettings->port                        = blx()->request->getPost('port');
		$emailSettings->smtpAuth                    = (bool)blx()->request->getPost('smtpAuth');

		if ($emailSettings->smtpAuth && $emailSettings->protocol !== EmailerType::Gmail)
		{
			$emailSettings->username                = blx()->request->getPost('smtpUsername');
			$emailSettings->password                = blx()->request->getPost('smtpPassword');
		}
		else
		{
			$emailSettings->username                = blx()->request->getPost('username');
			$emailSettings->password                = blx()->request->getPost('password');
		}

		$emailSettings->smtpKeepAlive               = (bool)blx()->request->getPost('smtpKeepAlive');
		$emailSettings->smtpSecureTransportType     = blx()->request->getPost('smtpSecureTransportType');
		$emailSettings->timeout                     = blx()->request->getPost('timeout');
		$emailSettings->emailAddress                = blx()->request->getPost('emailAddress');
		$emailSettings->senderName                  = blx()->request->getPost('senderName');

		// Validate user input
		if (!$emailSettings->validate())
		{
			return false;
		}

		$settings['protocol']     = $emailSettings->protocol;
		$settings['emailAddress'] = $emailSettings->emailAddress;
		$settings['senderName']   = $emailSettings->senderName;

		if (Blocks::hasPackage(BlocksPackage::Rebrand))
		{
			$settings['template'] = blx()->request->getPost('template');
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
