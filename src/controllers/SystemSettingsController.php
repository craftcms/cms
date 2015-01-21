<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2013 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\controllers;

use Craft;
use craft\app\dates\DateTime;
use craft\app\enums\ComponentType;
use craft\app\enums\EmailerType;
use craft\app\errors\HttpException;
use craft\app\helpers\UrlHelper;
use craft\app\models\EmailSettings as EmailSettingsModel;
use craft\app\models\GlobalSet as GlobalSetModel;
use craft\app\variables\Tool as ToolVariable;
use craft\app\web\Controller;

/**
 * The SystemSettingsController class is a controller that handles various control panel settings related tasks such as
 * displaying, saving and testing Craft settings in the control panel.
 *
 * Note that all actions in this controller require administrator access in order to execute.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class SystemSettingsController extends Controller
{
	// Public Methods
	// =========================================================================

	/**
	 * @inheritDoc Controller::init()
	 *
	 * @throws HttpException
	 * @return null
	 */
	public function init()
	{
		// All system setting actions require an admin
		$this->requireAdmin();
	}

	/**
	 * Shows the settings index.
	 *
	 * @return null
	 */
	public function actionSettingsIndex()
	{
		// Get all the tools
		$tools = Craft::$app->components->getComponentsByType(ComponentType::Tool);
		ksort($tools);

		// If there are no Asset sources, don't display the update Asset indexes tool.
		if (count(Craft::$app->assetSources->getAllSources()) == 0)
		{
			unset($tools['AssetIndex']);
		}

		$variables['tools'] = ToolVariable::populateVariables($tools);

		$this->renderTemplate('settings/_index', $variables);
	}

	/**
	 * Shows the general settings form.
	 *
	 * @param array $variables
	 *
	 * @return null
	 */
	public function actionGeneralSettings(array $variables = [])
	{
		if (empty($variables['info']))
		{
			$variables['info'] = Craft::$app->getInfo();
		}

		// Assemble the timezone options array (Technique adapted from http://stackoverflow.com/a/7022536/1688568)
		$variables['timezoneOptions'] = [];

		$utc = new DateTime();
		$offsets = [];
		$timezoneIds = [];
		$includedAbbrs = [];

		foreach (\DateTimeZone::listIdentifiers() as $timezoneId)
		{
			$timezone = new \DateTimeZone($timezoneId);
			$transition =  $timezone->getTransitions($utc->getTimestamp(), $utc->getTimestamp());
			$abbr = $transition[0]['abbr'];

			$offset = round($timezone->getOffset($utc) / 60);

			if ($offset)
			{
				$hour = floor($offset / 60);
				$minutes = floor(abs($offset) % 60);

				$format = sprintf('%+d', $hour);

				if ($minutes)
				{
					$format .= ':'.sprintf('%02u', $minutes);
				}
			}
			else
			{
				$format = '';
			}

			$offsets[] = $offset;
			$timezoneIds[] = $timezoneId;
			$includedAbbrs[] = $abbr;
			$variables['timezoneOptions'][$timezoneId] = 'UTC'.$format.($abbr != 'UTC' ? " ({$abbr})" : '').($timezoneId != 'UTC' ? ' - '.$timezoneId : '');
		}

		array_multisort($offsets, $timezoneIds, $variables['timezoneOptions']);

		$this->renderTemplate('settings/general/_index', $variables);
	}

	/**
	 * Saves the general settings.
	 *
	 * @return null
	 */
	public function actionSaveGeneralSettings()
	{
		$this->requirePostRequest();

		$info = Craft::$app->getInfo();

		$info->on          = (bool) Craft::$app->getRequest()->getBodyParam('on');
		$info->siteName    = Craft::$app->getRequest()->getBodyParam('siteName');
		$info->siteUrl     = Craft::$app->getRequest()->getBodyParam('siteUrl');
		$info->timezone    = Craft::$app->getRequest()->getBodyParam('timezone');

		if (Craft::$app->saveInfo($info))
		{
			Craft::$app->getSession()->setNotice(Craft::t('app', 'General settings saved.'));
			$this->redirectToPostedUrl();
		}
		else
		{
			Craft::$app->getSession()->setError(Craft::t('app', 'Couldn’t save general settings.'));

			// Send the info back to the template
			Craft::$app->getUrlManager()->setRouteVariables([
				'info' => $info
			]);
		}
	}

	/**
	 * Saves the email settings.
	 *
	 * @return null
	 */
	public function actionSaveEmailSettings()
	{
		$this->requirePostRequest();

		$settings = $this->_getEmailSettingsFromPost();

		// If $settings is an instance of EmailSettingsModel, there were validation errors.
		if (!$settings instanceof EmailSettingsModel)
		{
			if (Craft::$app->systemSettings->saveSettings('email', $settings))
			{
				Craft::$app->getSession()->setNotice(Craft::t('app', 'Email settings saved.'));
				$this->redirectToPostedUrl();
			}
		}

		Craft::$app->getSession()->setError(Craft::t('app', 'Couldn’t save email settings.'));

		// Send the settings back to the template
		Craft::$app->getUrlManager()->setRouteVariables([
			'settings' => $settings
		]);
	}

	/**
	 * Tests the email settings.
	 *
	 * @return null
	 */
	public function actionTestEmailSettings()
	{
		$this->requirePostRequest();
		$this->requireAjaxRequest();

		$settings = $this->_getEmailSettingsFromPost();

		// If $settings is an instance of EmailSettingsModel, there were validation errors.
		if (!$settings instanceof EmailSettingsModel)
		{
			try
			{
				if (Craft::$app->email->sendTestEmail($settings))
				{
					$this->returnJson(['success' => true]);
				}
			}
			catch (\Exception $e)
			{
				Craft::error($e->getMessage(), __METHOD__);
			}
		}

		$this->returnErrorJson(Craft::t('app', 'There was an error testing your email settings.'));
	}

	/**
	 * Global Set edit form.
	 *
	 * @param array $variables
	 *
	 * @throws HttpException
	 * @return null
	 */
	public function actionEditGlobalSet(array $variables = [])
	{
		// Breadcrumbs
		$variables['crumbs'] = [
			['label' => Craft::t('app', 'Settings'), 'url' => UrlHelper::getUrl('settings')],
			['label' => Craft::t('app', 'Globals'),  'url' => UrlHelper::getUrl('settings/globals')]
		];

		// Tabs
		$variables['tabs'] = [
			'settings'    => ['label' => Craft::t('app', 'Settings'),     'url' => '#set-settings'],
			'fieldlayout' => ['label' => Craft::t('app', 'Field Layout'), 'url' => '#set-fieldlayout']
		];

		if (empty($variables['globalSet']))
		{
			if (!empty($variables['globalSetId']))
			{
				$variables['globalSet'] = Craft::$app->globals->getSetById($variables['globalSetId']);

				if (!$variables['globalSet'])
				{
					throw new HttpException(404);
				}
			}
			else
			{
				$variables['globalSet'] = new GlobalSetModel();
			}
		}

		if ($variables['globalSet']->id)
		{
			$variables['title'] = $variables['globalSet']->name;
		}
		else
		{
			$variables['title'] = Craft::t('app', 'Create a new global set');
		}

		// Render the template!
		$this->renderTemplate('settings/globals/_edit', $variables);
	}

	// Private Methods
	// =========================================================================

	/**
	 * Returns the email settings from the post data.
	 *
	 * @return array
	 */
	private function _getEmailSettingsFromPost()
	{
		$emailSettings = new EmailSettingsModel();
		$gMailSmtp = 'smtp.gmail.com';

		$emailSettings->protocol                    = Craft::$app->getRequest()->getBodyParam('protocol');
		$emailSettings->host                        = Craft::$app->getRequest()->getBodyParam('host');
		$emailSettings->port                        = Craft::$app->getRequest()->getBodyParam('port');
		$emailSettings->smtpAuth                    = (bool)Craft::$app->getRequest()->getBodyParam('smtpAuth');

		if ($emailSettings->smtpAuth && $emailSettings->protocol !== EmailerType::Gmail)
		{
			$emailSettings->username                = Craft::$app->getRequest()->getBodyParam('smtpUsername');
			$emailSettings->password                = Craft::$app->getRequest()->getBodyParam('smtpPassword');
		}
		else
		{
			$emailSettings->username                = Craft::$app->getRequest()->getBodyParam('username');
			$emailSettings->password                = Craft::$app->getRequest()->getBodyParam('password');
		}

		$emailSettings->smtpKeepAlive               = (bool)Craft::$app->getRequest()->getBodyParam('smtpKeepAlive');
		$emailSettings->smtpSecureTransportType     = Craft::$app->getRequest()->getBodyParam('smtpSecureTransportType');
		$emailSettings->timeout                     = Craft::$app->getRequest()->getBodyParam('timeout');
		$emailSettings->emailAddress                = Craft::$app->getRequest()->getBodyParam('emailAddress');
		$emailSettings->senderName                  = Craft::$app->getRequest()->getBodyParam('senderName');

		if (Craft::$app->getEdition() >= Craft::Client)
		{
			$settings['template'] = Craft::$app->getRequest()->getBodyParam('template');
			$emailSettings->template = $settings['template'];
		}

		// Validate user input
		if (!$emailSettings->validate())
		{
			return $emailSettings;
		}

		$settings['protocol']     = $emailSettings->protocol;
		$settings['emailAddress'] = $emailSettings->emailAddress;
		$settings['senderName']   = $emailSettings->senderName;

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
