<?php
namespace Craft;

/**
 * Handles package actions.
 */
class PackagesController extends BaseController
{
	private $_packageList = array(
		CraftPackage::Users,
		CraftPackage::PublishPro,
		CraftPackage::Language,
		CraftPackage::Cloud,
		CraftPackage::Rebrand,
	);

	/**
	 * Init
	 */
	public function init()
	{
		// All package actions must be performed by an admin.
		craft()->userSession->requireAdmin();
	}

	/**
	 * Fetches the licensed packages from Elliott.
	 */
	public function actionFetchPackageInfo()
	{
		$this->requireAjaxRequest();

		$this->returnJson(array(
			'success' => true,
			'packages' => array(
				'Users'      => array('licensed' => false, 'price' => 14900, 'salePrice' => 7450),
				'PublishPro' => array('licensed' => true),
				'Language'   => array('licensed' => false, 'price' => 29900, 'salePrice' => 14950),
				'Cloud'      => array('licensed' => true),
				'Rebrand'    => array('licensed' =>false, 'price' => 4900, 'salePrice' => 2450),
			),
		));
	}

	/**
	 * Installs a package.
	 */
	public function actionInstallPackage()
	{
		$this->requirePostRequest();
		$this->requireAjaxRequest();

		$package = craft()->request->getRequiredPost('package');

		// Make sure it's a real package name
		$this->_validatePackageName($package);

		// Make sure it's not already installed
		$installedPackages = Craft::getPackages();

		if (in_array($package, $installedPackages))
		{
			throw new Exception(Craft::t('The {package} package is already installed.', array('package' => $package)));
		}

		// Install it
		$installedPackages[] = $package;
		craft()->db->createCommand()->update('info', array(
			'packages' => implode(',', $installedPackages))
		);

		$this->returnJson(array(
			'success' => true
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

		// Make sure it's a real package name
		$this->_validatePackageName($package);

		// Make sure it's actually installed
		$installedPackages = Craft::getPackages();

		if (!in_array($package, $installedPackages))
		{
			throw new Exception(Craft::t('The {package} package wasn’t installed.', array('package' => $package)));
		}

		// Uninstall it
		$index = array_search($package, $installedPackages);
		array_splice($installedPackages, $index, 1);
		craft()->db->createCommand()->update('info', array(
			'packages' => implode(',', $installedPackages))
		);

		$this->returnJson(array(
			'success' => true
		));
	}

	/**
	 * Validates a package name.
	 *
	 * @access private
	 * @throws Exception
	 */
	private function _validatePackageName($package)
	{
		if (!in_array($package, $this->_packageList))
		{
			throw new Exception(Craft::t('Craft doesn’t have a package named “{package}”', array('package' => $package)));
		}
	}
}
