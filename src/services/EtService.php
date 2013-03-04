<?php
namespace Craft;

/**
 *
 */
class EtService extends BaseApplicationComponent
{
	const Ping            = '@@@elliottEndpointUrl@@@actions/elliott/app/ping';
	const CheckForUpdates = '@@@elliottEndpointUrl@@@actions/elliott/app/checkForUpdates';
	const DownloadUpdate  = '@@@elliottEndpointUrl@@@actions/elliott/app/downloadUpdate';
	const TransferLicense = '@@@elliottEndpointUrl@@@actions/elliott/app/transferLicenseToCurrentDomain';
	const GetPackageInfo  = '@@@elliottEndpointUrl@@@actions/elliott/app/getPackageInfo';
	const PurchasePackage = '@@@elliottEndpointUrl@@@actions/elliott/app/purchasePackage';

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
	 * @param $updateInfo
	 * @return EtModel|null
	 */
	public function check($updateInfo)
	{
		$et = new Et(static::CheckForUpdates);
		$et->setData($updateInfo);
		$etResponse = $et->phoneHome();

		if ($etResponse)
		{
			$etResponse->data = UpdateModel::populateModel($etResponse->data);
			return $etResponse;
		}
	}

	/**
	 * @param $downloadPath
	 * @return bool
	 */
	public function downloadUpdate($downloadPath)
	{
		$et = new Et(static::DownloadUpdate, 240);

		if (IOHelper::folderExists($downloadPath))
		{
			$downloadPath .= StringHelper::UUID().'.zip';
		}

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

		if ($etResponse && $etResponse->data['success'])
		{
			return true;
		}
		else
		{
			// Did they at least say why?
			if ($etResponse && !empty($etResponse['errors']))
			{
				switch ($etResponse['errors'][0])
				{
					// Validation errors
					case 'not_public_domain': $error = Craft::t('This domain doesn’t appear to be public.'); break;
					default:                  $error = $etResponse->data['error'];
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
	 * Fetches info about the available packages from Elliott.
	 *
	 * @return EtModel|null
	 */
	public function fetchPackageInfo()
	{
		$et = new Et(static::GetPackageInfo);
		$etResponse = $et->phoneHome();
		return $etResponse;
	}

	/**
	 * Attempts to purchase a package.
	 *
	 * @param PackagePurchaseOrderModel $model
	 * @return bool
	 */
	public function purchasePackage(PackagePurchaseOrderModel $model)
	{
		if ($model->validate())
		{
			$et = new Et(static::PurchasePackage);
			$et->setData($model);
			$etResponse = $et->phoneHome();

			if ($etResponse && $etResponse->data['success'])
			{
				// Success! Let's get this sucker installed.
				if (!Craft::hasPackage($model->package))
				{
					Craft::installPackage($model->package);
				}

				return true;
			}
			else
			{
				// Did they at least say why?
				if ($etResponse && !empty($etResponse['errors']))
				{
					switch ($etResponse['errors'][0])
					{
						// Validation errors
						case 'package_doesnt_exist': $error = Craft::t('The selected package doesn’t exist anymore.'); break;
						case 'invalid_license_key':  $error = Craft::t('Your license key is invalid.'); break;
						case 'license_has_package':  $error = Craft::t('Your Craft license already has this package.'); break;
						case 'price_mismatch':       $error = Craft::t('The cost of this package just changed.'); break;
						case 'unknown_error':        $error = Craft::t('An unknown error occurred.'); break;

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

						default:                     $error = $etResponse->data['error'];
					}
				}
				else
				{
					// Something terrible must have happened!
					$error = Craft::t('Craft is unable to purchase packages at this time.');
				}

				$model->addError('response', $error);
			}
		}

		return false;
	}

	/**
	 * Returns the path to the license key file.
	 */
	public function getLicenseKeyPath()
	{
		return craft()->path->getConfigPath().'license.key';
	}

	/**
	 * Returns the license key status.
	 */
	public function getLicenseKeyStatus()
	{
		return craft()->fileCache->get('licenseKeyStatus');
	}

	/**
	 * Returns the domain that the installed license key is licensed for.
	 */
	public function getLicensedDomain()
	{
		return craft()->fileCache->get('licensedDomain');
	}

	/**
	 * Returns an array of the packages that this license is tied to.
	 *
	 * @return mixed
	 */
	public function getLicensedPackages()
	{
		return craft()->fileCache->get('licensedPackages');
	}

	/**
	 *
	 */
	public function decodeEtValues($attributes)
	{
		$attributes = JsonHelper::decode($attributes);
		return new EtModel($attributes);
	}
}
