<?php

/**
 *
 */
class CpTag extends Tag
{
	private static $defaultSections = array(
		'dashboard' => 'Dashboard',
		'content' => 'Content',
		'assets' => 'Assets',
		'users' => 'Users',
		'settings' => 'Settings',
		'guide' => 'User Guide',
	);

	/**
	 * @access public
	 *
	 * @return CpDashboardTag
	 */
	public function dashboard()
	{
		return new CpDashboardTag();
	}

	/**
	 * @access public
	 *
	 * @param $path
	 *
	 * @return CpResourceTag
	 */
	public function resource($path)
	{
		return new CpResourceTag($path);
	}

	/**
	 * @access public
	 *
	 * @return array
	 */
	public function sections()
	{
		$sectionTags = array();

		foreach (self::$defaultSections as $handle => $name)
		{
			$sectionTags[] = new CpSectionTag($handle, $name);
		}

		return $sectionTags;
	}

	/**
	 * @access public
	 *
	 * @return bool
	 */
	public function badLicenseKey()
	{
		$licenseKeyStatus = Blocks::app()->site->licenseKeyStatus;
		if ($licenseKeyStatus->licenseKeyStatus == LicenseKeyStatus::InvalidKey)
			return true;

		return false;
	}

	/**
	 * @access public
	 *
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
	 * @access public
	 *
	 * @return mixed
	 */
	public function updateInfoCached()
	{
		return Blocks::app()->update->isUpdateInfoCached();
	}

	/**
	 * @access public
	 *
	 * @param bool $forceRefresh
	 *
	 * @return CpUpdatesTag
	 */
	public function updates($forceRefresh = false)
	{
		return new CpUpdatesTag($forceRefresh);
	}
}
