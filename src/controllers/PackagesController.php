<?php
namespace Craft;

/**
 * Handles package actions.
 */
class PackagesController extends BaseController
{
	/**
	 * Init
	 */
	public function init()
	{
		// All package actions must be performed by an admin.
		craft()->userSession->requireAdmin();
	}

	/**
	 * Fetches the installed package info from Elliott.
	 */
	public function actionFetchPackageInfo()
	{
		$this->requireAjaxRequest();

		$etResponse = craft()->et->fetchPackageInfo();

		if ($etResponse)
		{
			// Make sure we've got a valid license key
			if ($etResponse->licenseKeyStatus == LicenseKeyStatus::Valid)
			{
				$packages = $etResponse->data;

				// Include which packages are actually licensed
				foreach ($etResponse->licensedPackages as $packageName)
				{
					$packages[$packageName]['licensed'] = true;
				}

				$this->returnJson(array(
					'success'  => true,
					'packages' => $packages
				));
			}
			else
			{
				$this->returnErrorJson(Craft::t('Your license key is invalid.'));
			}
		}
		else
		{
			$this->returnErrorJson(Craft::t('Craft is unable to fetch package info at this time.'));
		}
	}

	/**
	 * Passes along a given CC token to Elliott to purchase a package.
	 */
	public function actionPurchasePackage()
	{
		$this->requirePostRequest();
		$this->requireAjaxRequest();

		$model = new PackagePurchaseOrderModel(array(
			'ccTokenId'     => craft()->request->getRequiredPost('ccTokenId'),
			'package'       => craft()->request->getRequiredPost('package'),
			'expectedPrice' => craft()->request->getRequiredPost('expectedPrice'),
		));

		if (craft()->et->purchasePackage($model))
		{
			$this->returnJson(array(
				'success' => true,
				'package' => $model->package
			));
		}
		else
		{
			$this->returnJson(array(
				'errors' => $model->getErrors()
			));
		}
	}

	/**
	 * Installs a package.
	 */
	public function actionInstallPackage()
	{
		$this->requirePostRequest();
		$this->requireAjaxRequest();

		$package = craft()->request->getRequiredPost('package');
		$success = Craft::installPackage($package);

		$this->returnJson(array(
			'success' => $success
		));
	}

	/**
	 * Uninstalls a package.
	 */
	public function actionUninstallPackage()
	{
		$this->requirePostRequest();
		$this->requireAjaxRequest();

		$package = craft()->request->getRequiredPost('package');
		$success = Craft::uninstallPackage($package);

		$this->returnJson(array(
			'success' => $success
		));
	}
}
