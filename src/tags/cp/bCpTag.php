<?php

/**
 *
 */
class bCpTag extends bTag
{
	private static $defaultSections = array(
		'dashboard' => 'Dashboard',
		'content' => 'Content',
		'assets' => 'Assets',
		'users' => 'Users',
		'settings' => 'Settings',
		'userguide' => 'User Guide',
	);

	/**
	 * @return bCpDashboardTag
	 */
	public function dashboard()
	{
		return new bCpDashboardTag();
	}

	/**
	 * @param $path
	 * @return bCpResourceTag
	 */
	public function resource($path)
	{
		return new bCpResourceTag($path);
	}

	/**
	 * @return array
	 */
	public function sections()
	{
		$sectionTags = array();

		foreach (self::$defaultSections as $handle => $name)
		{
			$sectionTags[] = new bCpSectionTag($handle, $name);
		}

		return $sectionTags;
	}

	/**
	 * @return bool
	 */
	public function badLicenseKey()
	{
		$licenseKeyStatus = Blocks::app()->site->licenseKeyStatus;
		if ($licenseKeyStatus->licenseKeyStatus == bLicenseKeyStatus::InvalidKey)
			return true;

		return false;
	}

	/**
	 * @return bool
	 */
	public function criticalUpdateAvailable()
	{
		if (!Blocks::app()->update->isUpdateInfoCached())
			return false;

		$updateInfo = Blocks::app()->update->updateInfo;
		return $updateInfo->criticalUpdateAvailable;
	}

	/**
	 * @return mixed
	 */
	public function updateInfoCached()
	{
		return Blocks::app()->update->isUpdateInfoCached();
	}

	/**
	 * @param bool $forceRefresh
	 * @return bCpUpdatesTag
	 */
	public function updates($forceRefresh = false)
	{
		return new bCpUpdatesTag($forceRefresh);
	}
}
