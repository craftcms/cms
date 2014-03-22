<?php
namespace Craft;

/**
 *
 */
class EtService extends BaseApplicationComponent
{
	const Ping              = '@@@elliottEndpointUrl@@@actions/elliott/app/ping';
	const CheckForUpdates   = '@@@elliottEndpointUrl@@@actions/elliott/app/checkForUpdates';
	const TransferLicense   = '@@@elliottEndpointUrl@@@actions/elliott/app/transferLicenseToCurrentDomain';
	const PurchaseEdition   = '@@@elliottEndpointUrl@@@actions/elliott/app/purchaseEdition';
	const GetUpdateFileInfo = '@@@elliottEndpointUrl@@@actions/elliott/app/getUpdateFileInfo';

	/**
	 * @return EtModel|null
	 */
	public function ping()
	{
		$et = new Et(static::Ping);
		$etResponse = $et->phoneHome();
		return $etResponse;
	}

	/**
	 * Checks if any new updates are available.
	 *
	 * @param $updateInfo
	 * @return EtModel|null
	 */
	public function checkForUpdates($updateInfo)
	{
		$et = new Et(static::CheckForUpdates);
		$et->setData($updateInfo);
		$etResponse = $et->phoneHome();

		if ($etResponse)
		{
			$etResponse->data = new UpdateModel($etResponse->data);
			return $etResponse;
		}
	}

	/**
	 * @return \Craft\EtModel|null
	 */
	public function getUpdateFileInfo()
	{
		$et = new Et(static::GetUpdateFileInfo);
		$etResponse = $et->phoneHome();

		if ($etResponse)
		{
			return $etResponse->data;
		}
	}

	/**
	 * @param $downloadPath
	 * @param $md5
	 * @return bool
	 */
	public function downloadUpdate($downloadPath, $md5)
	{
		if (IOHelper::folderExists($downloadPath))
		{
			$downloadPath .= $md5.'.zip';
		}

		$updateModel = craft()->updates->getUpdates();
		$buildVersion = $updateModel->app->latestVersion.'.'.$updateModel->app->latestBuild;

		$path = 'http://download.buildwithcraft.com/craft/'.$updateModel->app->latestVersion.'/'.$buildVersion.'/Patch/'.$updateModel->app->localBuild.'/'.$md5.'.zip';

		$et = new Et($path, 240);
		$et->setDestinationFileName($downloadPath);

		if (($fileName = $et->phoneHome()) !== null)
		{
			return $fileName;
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
		$et = new Et(static::TransferLicense);
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
	 * Returns the license key status, or false if it's unknown.
	 *
	 * @return string|false
	 */
	public function getLicenseKeyStatus()
	{
		return craft()->cache->get('licenseKeyStatus');
	}

	/**
	 * Returns the domain that the installed license key is licensed for, null if it's not set yet, or false if it's unknown.
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
	 * @param $attributes
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

				// Make sure it's valid. (At a minumum, localBuild and localVersion should be set.)
				if ($etModel->validate())
				{
					return $etModel;
				}
			}
		}
	}
}
