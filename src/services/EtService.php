<?php
namespace Craft;

/**
 * Class EtService
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://craftcms.com/license Craft License Agreement
 * @see       http://craftcms.com
 * @package   craft.app.services
 * @since     1.0
 */
class EtService extends BaseApplicationComponent
{
	// Constants
	// =========================================================================

	const ENDPOINT_PING = 'app/ping';
	const ENDPOINT_CHECK_FOR_UPDATES = 'app/checkForUpdates';
	const ENDPOINT_TRANSFER_LICENSE = 'app/transferLicenseToCurrentDomain';
	const ENDPOINT_GET_UPGRADE_INFO = 'app/getUpgradeInfo';
	const ENDPOINT_GET_COUPON_PRICE = 'app/getCouponPrice';
	const ENDPOINT_PURCHASE_UPGRADE = 'app/purchaseUpgrade';
	const ENDPOINT_GET_UPDATE_FILE_INFO = 'app/getUpdateFileInfo';
	const ENDPOINT_REGISTER_PLUGIN = 'plugins/registerPlugin';
	const ENDPOINT_UNREGISTER_PLUGIN = 'plugins/unregisterPlugin';
	const ENDPOINT_TRANSFER_PLUGIN = 'plugins/transferPlugin';

	// Public Methods
	// =========================================================================

	/**
	 * @return EtModel|null
	 */
	public function ping()
	{
		$et = $this->_createEtTransport(static::ENDPOINT_PING);
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
		$et = $this->_createEtTransport(static::ENDPOINT_CHECK_FOR_UPDATES);
		$et->setData($updateInfo);
		$etResponse = $et->phoneHome();

		if ($etResponse)
		{
			$updateModel = new UpdateModel($etResponse->data);

			// Convert the Craft release dates into localized times.
			if (count($updateModel->app->releases) > 0)
			{
				foreach ($updateModel->app->releases as $key => $release)
				{
					// Have to use setAttribute here.
					$updateModel->app->releases[$key]->setAttribute('localizedDate', $release->date->localeDate());
				}
			}

			// Convert any plugin release dates into localized times.
			if (count($updateModel->plugins) > 0)
			{
				foreach ($updateModel->plugins as $pluginKey => $plugin)
				{
					if (count($plugin->releases) > 0)
					{
						foreach ($plugin->releases as $pluginReleaseKey => $pluginRelease)
						{
							// Have to use setAttribute here.
							$updateModel->plugins[$pluginKey]->releases[$pluginReleaseKey]->setAttribute('localizedDate', $pluginRelease->date->localeDate());
						}
					}
				}
			}

			$etResponse->data = $updateModel;

			return $etResponse;
		}
	}

	/**
	 * @param $handle
	 *
	 * @return EtModel|null
	 * @throws EtException
	 * @throws \Exception
	 */
	public function getUpdateFileInfo($handle)
	{
		$et = $this->_createEtTransport(static::ENDPOINT_GET_UPDATE_FILE_INFO);

		if ($handle !== 'craft')
		{
			$et->setHandle($handle);
			$plugin = craft()->plugins->getPlugin($handle);

			if ($plugin)
			{
				$pluginUpdateModel = new PluginUpdateModel();
				$pluginUpdateModel->class = $plugin->getClassHandle();
				$pluginUpdateModel->localVersion = $plugin->getVersion();

				$et->setData($pluginUpdateModel);
			}
		}

		$etResponse = $et->phoneHome();

		if ($etResponse)
		{
			return $etResponse->data;
		}
	}

	/**
	 * @param string $downloadPath
	 * @param string $md5
	 * @param string $handle
	 *
	 * @return bool
	 */
	public function downloadUpdate($downloadPath, $md5, $handle)
	{
		if (IOHelper::folderExists($downloadPath))
		{
			$downloadPath .= $md5.'.zip';
		}

		$updateModel = craft()->updates->getUpdates();

		if ($handle == 'craft')
		{
			$localVersion = $updateModel->app->localVersion;
			$targetVersion = $updateModel->app->latestVersion;
			$uriPrefix = 'craft';
		}
		else
		{
			// Find the plugin whose class matches the handle
			$localVersion = null;
			$targetVersion = null;
			$uriPrefix = 'plugins/'.$handle;

			foreach ($updateModel->plugins as $plugin)
			{
				if (strtolower($plugin->class) == $handle)
				{
					$localVersion = $plugin->localVersion;
					$targetVersion = $plugin->latestVersion;
					break;
				}
			}

			if ($localVersion === null)
			{
				Craft::log('Couldn’t find the plugin "'.$handle.'" in the update model.', LogLevel::Warning);

				return false;
			}
		}

		$baseUrl = craft()->config->get('downloadBaseUrl') ?: 'https://download.craftcdn.com';
		$xy = AppHelper::getMajorMinorVersion($targetVersion);
		$url = "{$baseUrl}/{$uriPrefix}/{$xy}/{$targetVersion}/Patch/{$localVersion}/{$md5}.zip";

		$client = new \Guzzle\Http\Client();
		$request = $client->get($url, null, array(
			'timeout' => 240,
			'connect_timeout' => 30,
		));

		// Potentially long-running request, so close session to prevent session blocking on subsequent requests.
		craft()->session->close();

		$response = $request->send();

		if (!$response->isSuccessful())
		{
			Craft::log('Error in downloading '.$url.' Response: '.$response->getBody(), LogLevel::Warning);

			return false;
		}

		$body = $response->getBody();

		// Make sure we're at the beginning of the stream.
		$body->rewind();

		// Write it out to the file
		IOHelper::writeToFile($downloadPath, $body->getStream(), true);

		// Close the stream.
		$body->close();

		return IOHelper::getFileName($downloadPath);
	}

	/**
	 * Transfers the installed license to the current domain.
	 *
	 * @return true|string Returns true if the request was successful, otherwise returns the error.
	 */
	public function transferLicenseToCurrentDomain()
	{
		$et = $this->_createEtTransport(static::ENDPOINT_TRANSFER_LICENSE);
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
				$error = Craft::t('Craft is unable to transfer your license to this domain at this time.');
			}

			return $error;
		}
	}

	/**
	 * Fetches info about the available Craft editions from Elliott.
	 *
	 * @return EtModel|null
	 */
	public function fetchUpgradeInfo()
	{
		$et = $this->_createEtTransport(static::ENDPOINT_GET_UPGRADE_INFO);
		$etResponse = $et->phoneHome();

		if ($etResponse)
		{
			$etResponse->data = new UpgradeInfoModel($etResponse->data);
		}

		return $etResponse;
	}

	/**
	 * Fetches the price of an upgrade with a coupon applied to it.
	 *
	 * @return EtModel|null
	 */
	public function fetchCouponPrice($edition, $couponCode)
	{
		$et = $this->_createEtTransport(static::ENDPOINT_GET_COUPON_PRICE);
		$et->setData(array('edition' => $edition, 'couponCode' => $couponCode));
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
			$et = $this->_createEtTransport(static::ENDPOINT_PURCHASE_UPGRADE);
			$et->setData($model);
			$etResponse = $et->phoneHome();

			if (!empty($etResponse->data['success']))
			{
				// Success! Let's get this sucker installed.
				craft()->setEdition($model->edition);

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
						case 'edition_doesnt_exist': $error = Craft::t('The selected edition doesn’t exist anymore.'); break;
						case 'invalid_license_key':  $error = Craft::t('Your license key is invalid.'); break;
						case 'license_has_edition':  $error = Craft::t('Your Craft license already has this edition.'); break;
						case 'price_mismatch':       $error = Craft::t('The cost of this edition just changed.'); break;
						case 'unknown_error':        $error = Craft::t('An unknown error occurred.'); break;
						case 'invalid_coupon_code':  $error = Craft::t('Invalid coupon code.'); break;

						// Stripe errors
						case 'incorrect_number':     $error = Craft::t('The card number is incorrect.'); break;
						case 'invalid_number':       $error = Craft::t('The card number is invalid.'); break;
						case 'invalid_expiry_month': $error = Craft::t('The expiration month is invalid.'); break;
						case 'invalid_expiry_year':  $error = Craft::t('The expiration year is invalid.'); break;
						case 'invalid_cvc':          $error = Craft::t('The security code is invalid.'); break;
						case 'incorrect_cvc':        $error = Craft::t('The security code is incorrect.'); break;
						case 'expired_card':         $error = Craft::t('Your card has expired.'); break;
						case 'card_declined':        $error = Craft::t('Your card was declined.'); break;
						case 'processing_error':     $error = Craft::t('An error occurred while processing your card.'); break;

						default:                     $error = $etResponse->errors[0];
					}
				}
				else
				{
					// Something terrible must have happened!
					$error = Craft::t('Craft is unable to purchase an edition upgrade at this time.');
				}

				$model->addError('response', $error);
			}
		}

		return false;
	}

	/**
	 * Registers a given plugin with the current Craft license.
	 *
	 * @string $pluginHandle The plugin handle that should be registered
	 *
	 * @return EtModel
	 */
	public function registerPlugin($pluginHandle)
	{
		$et = $this->_createEtTransport(static::ENDPOINT_REGISTER_PLUGIN);
		$et->setData(array(
			'pluginHandle' => $pluginHandle
		));
		$etResponse = $et->phoneHome();

		return $etResponse;
	}

	/**
	 * Transfers a given plugin to the current Craft license.
	 *
	 * @string $pluginHandle The plugin handle that should be transferred
	 *
	 * @return EtModel
	 */
	public function transferPlugin($pluginHandle)
	{
		$et = $this->_createEtTransport(static::ENDPOINT_TRANSFER_PLUGIN);
		$et->setData(array(
			'pluginHandle' => $pluginHandle
		));
		$etResponse = $et->phoneHome();

		return $etResponse;
	}

	/**
	 * Unregisters a given plugin from the current Craft license.
	 *
	 * @string $pluginHandle The plugin handle that should be unregistered
	 *
	 * @return EtModel
	 */
	public function unregisterPlugin($pluginHandle)
	{
		$et = $this->_createEtTransport(static::ENDPOINT_UNREGISTER_PLUGIN);
		$et->setData(array(
			'pluginHandle' => $pluginHandle
		));
		$etResponse = $et->phoneHome();

		if (!empty($etResponse->data['success']))
		{
			// Remove our record of the license key
			craft()->plugins->setPluginLicenseKey($pluginHandle, null);
		}

		return $etResponse;
	}

	/**
	 * Returns the license key status, or false if it's unknown.
	 *
	 * @return string|false
	 */
	public function getLicenseKeyStatus()
	{
		return craft()->cache->get('licenseKeyStatus');
	}

	/**
	 * Returns the domain that the installed license key is licensed for, null if it's not set yet, or false if it's
	 * unknown.
	 *
	 * @return string|null|false
	 */
	public function getLicensedDomain()
	{
		return craft()->cache->get('licensedDomain');
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

				// Make sure it's valid.
				if ($etModel->validate())
				{
					return $etModel;
				}
			}
		}
	}

	// Private Methods
	// =========================================================================

	/**
	 * Creates a new ET Transport object for the given endpoint.
	 *
	 * @param string $endpoint
	 *
	 * @return Et
	 */
	private function _createEtTransport($endpoint)
	{
		$baseUrl = craft()->config->get('elliottBaseUrl') ?: 'https://elliott.craftcms.com';
		$query = craft()->config->get('elliottQuery');
		$url = $baseUrl.'/actions/elliott/'.$endpoint;

		if ($query)
		{
			$url .= '?'.$query;
		}

		return new Et($url);
	}
}
