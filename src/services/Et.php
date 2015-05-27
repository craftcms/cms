<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2015 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\services;

use Craft;
use craft\app\helpers\IOHelper;
use craft\app\helpers\JsonHelper;
use craft\app\models\AppNewRelease;
use craft\app\models\AppUpdate;
use craft\app\models\Et as EtModel;
use craft\app\models\PluginNewRelease;
use craft\app\models\PluginUpdate;
use craft\app\models\Update as UpdateModel;
use craft\app\models\UpgradePurchase as UpgradePurchaseModel;
use yii\base\Component;

/**
 * Class Et service.
 *
 * An instance of the Et service is globally accessible in Craft via [[Application::et `Craft::$app->getEt()`]].
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class Et extends Component
{
	// Constants
	// =========================================================================

	const Ping              = 'https://elliott.buildwithcraft.com/actions/elliott/app/ping';
	const CheckForUpdates   = 'https://elliott.buildwithcraft.com/actions/elliott/app/checkForUpdates';
	const TransferLicense   = 'https://elliott.buildwithcraft.com/actions/elliott/app/transferLicenseToCurrentDomain';
	const GetEditionInfo    = 'https://elliott.buildwithcraft.com/actions/elliott/app/getEditionInfo';
	const PurchaseUpgrade   = 'https://elliott.buildwithcraft.com/actions/elliott/app/purchaseUpgrade';
	const GetUpdateFileInfo = 'https://elliott.buildwithcraft.com/actions/elliott/app/getUpdateFileInfo';

	// Public Methods
	// =========================================================================

	/**
	 * @return EtModel|null
	 */
	public function ping()
	{
		$et = new \craft\app\et\Et(static::Ping);
		$etResponse = $et->phoneHome();
		return $etResponse;
	}

	/**
	 * Checks if any new updates are available.
	 *
	 * @param $updateInfo
	 *
	 * @return EtModel|null
	 */
	public function checkForUpdates($updateInfo)
	{
		$et = new \craft\app\et\Et(static::CheckForUpdates);
		$et->setData($updateInfo);
		$etResponse = $et->phoneHome();

		if ($etResponse)
		{
			// Populate the base UpdateModel
			$updateModel = new UpdateModel();
			$updateModel->setAttributes($etResponse->data, false);

			// Populate any Craft specific attributes.
			$appUpdateModel = new AppUpdate();
			$appUpdateModel->setAttributes($updateModel->app, false);
			$updateModel->app = $appUpdateModel;

			// Populate any new Craft release information.
			foreach ($appUpdateModel->releases as $key => $appReleaseInfo)
			{
				$appReleaseModel = new AppNewRelease();
				$appReleaseModel->setAttributes($appReleaseInfo, false);

				$appUpdateModel->releases[$key] = $appReleaseModel;
			}

			// For every plugin, populate their base information.
			foreach ($updateModel->plugins as $pluginHandle => $pluginUpdateInfo)
			{
				$pluginUpdateModel = new PluginUpdate();
				$pluginUpdateModel->setAttributes($pluginUpdateInfo, false);

				// Now populate a plugin’s release information.
				foreach ($pluginUpdateModel->releases as $key => $pluginReleaseInfo)
				{
					$pluginReleaseModel = new PluginNewRelease();
					$pluginReleaseModel->setAttributes($pluginReleaseInfo, false);

					$pluginUpdateModel->releases[$key] = $pluginReleaseModel;
				}

				$updateModel->plugins[$pluginHandle] = $pluginUpdateModel;
			}

			// Put it all back on Et.
			$etResponse->data = $updateModel;

			return $etResponse;
		}
	}

	/**
	 * @return EtModel|null
	 */
	public function getUpdateFileInfo()
	{
		$et = new \craft\app\et\Et(static::GetUpdateFileInfo);
		$etResponse = $et->phoneHome();

		if ($etResponse)
		{
			return $etResponse->data;
		}
	}

	/**
	 * @param string $downloadPath
	 * @param string $md5
	 *
	 * @return bool
	 */
	public function downloadUpdate($downloadPath, $md5)
	{
		if (IOHelper::folderExists($downloadPath))
		{
			$downloadPath .= '/'.$md5.'.zip';
		}

		$updateModel = Craft::$app->getUpdates()->getUpdates();
		$buildVersion = $updateModel->app->latestVersion.'.'.$updateModel->app->latestBuild;

		$path = 'http://download.buildwithcraft.com/craft/'.$updateModel->app->latestVersion.'/'.$buildVersion.'/Patch/'.$updateModel->app->localBuild.'/'.$md5.'.zip';

		$et = new \craft\app\et\Et($path, 240);
		$et->setDestinationFilename($downloadPath);

		if (($filename = $et->phoneHome()) !== null)
		{
			return $filename;
		}

		return false;
	}

	/**
	 * Transfers the installed license to the current domain.
	 *
	 * @return true|string Returns true if the request was successful, otherwise returns the error.
	 */
	public function transferLicenseToCurrentDomain()
	{
		$et = new \craft\app\et\Et(static::TransferLicense);
		$etResponse = $et->phoneHome();

		if (!empty($etResponse->data['success']))
		{
			return true;
		}
		else
		{
			// Did they at least say why?
			if (!empty($etResponse->errors))
			{
				switch ($etResponse->errors[0])
				{
					// Validation errors
					case 'not_public_domain':
					{
						// So...
						return true;
					}

					default:
					{
						$error = $etResponse->data['error'];
					}
				}
			}
			else
			{
				$error = Craft::t('app', 'Craft is unable to transfer your license to this domain at this time.');
			}

			return $error;
		}
	}

	/**
	 * Fetches info about the available Craft editions from Elliott.
	 *
	 * @return EtModel|null
	 */
	public function fetchEditionInfo()
	{
		$et = new \craft\app\et\Et(static::GetEditionInfo);
		$etResponse = $et->phoneHome();
		return $etResponse;
	}

	/**
	 * Attempts to purchase an edition upgrade.
	 *
	 * @param UpgradePurchaseModel $model
	 *
	 * @return bool
	 */
	public function purchaseUpgrade(UpgradePurchaseModel $model)
	{
		if ($model->validate())
		{
			$et = new \craft\app\et\Et(static::PurchaseUpgrade);
			$et->setData($model);
			$etResponse = $et->phoneHome();

			if (!empty($etResponse->data['success']))
			{
				// Success! Let's get this sucker installed.
				Craft::$app->setEdition($model->edition);

				return true;
			}
			else
			{
				// Did they at least say why?
				if (!empty($etResponse->errors))
				{
					switch ($etResponse->errors[0])
					{
						// Validation errors
						case 'edition_doesnt_exist': $error = Craft::t('app', 'The selected edition doesn’t exist anymore.'); break;
						case 'invalid_license_key':  $error = Craft::t('app', 'Your license key is invalid.'); break;
						case 'license_has_edition':  $error = Craft::t('app', 'Your Craft license already has this edition.'); break;
						case 'price_mismatch':       $error = Craft::t('app', 'The cost of this edition just changed.'); break;
						case 'unknown_error':        $error = Craft::t('app', 'An unknown error occurred.'); break;

						// Stripe errors
						case 'incorrect_number':     $error = Craft::t('app', 'The card number is incorrect.'); break;
						case 'invalid_number':       $error = Craft::t('app', 'The card number is invalid.'); break;
						case 'invalid_expiry_month': $error = Craft::t('app', 'The expiration month is invalid.'); break;
						case 'invalid_expiry_year':  $error = Craft::t('app', 'The expiration year is invalid.'); break;
						case 'invalid_cvc':          $error = Craft::t('app', 'The security code is invalid.'); break;
						case 'incorrect_cvc':        $error = Craft::t('app', 'The security code is incorrect.'); break;
						case 'expired_card':         $error = Craft::t('app', 'Your card has expired.'); break;
						case 'card_declined':        $error = Craft::t('app', 'Your card was declined.'); break;
						case 'processing_error':     $error = Craft::t('app', 'An error occurred while processing your card.'); break;

						default:                     $error = $etResponse->errors[0];
					}
				}
				else
				{
					// Something terrible must have happened!
					$error = Craft::t('app', 'Craft is unable to purchase an edition upgrade at this time.');
				}

				$model->addError('response', $error);
			}
		}

		return false;
	}

	/**
	 * Returns the license key status, or false if it's unknown.
	 *
	 * @return string|false
	 */
	public function getLicenseKeyStatus()
	{
		return Craft::$app->getCache()->get('licenseKeyStatus');
	}

	/**
	 * Returns the domain that the installed license key is licensed for, null if it's not set yet, or false if it's
	 * unknown.
	 *
	 * @return string|null|false
	 */
	public function getLicensedDomain()
	{
		return Craft::$app->getCache()->get('licensedDomain');
	}

	/**
	 * Creates a new EtModel with provided JSON, and returns it if it's valid.
	 *
	 * @param array $attributes
	 *
	 * @return EtModel|null
	 */
	public function decodeEtModel($attributes)
	{
		if ($attributes)
		{
			$attributes = JsonHelper::decode($attributes);

			if (is_array($attributes))
			{
				$etModel = new EtModel($attributes);

				// Make sure it's valid. (At a minimum, localBuild and localVersion
				// should be set.)
				if ($etModel->validate())
				{
					return $etModel;
				}
			}
		}
	}
}
