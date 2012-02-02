<?php
namespace Blocks;

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
		'userguide' => 'User Guide',
	);

	/**
	 * @return CpDashboardTag
	 */
	public function dashboard()
	{
		return new CpDashboardTag();
	}

	/**
	 * @param $path
	 * @return CpResourceTag
	 */
	public function resource($path)
	{
		return new CpResourceTag($path);
	}

	/**
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
	 * @return bool
	 */
	public function badLicenseKey()
	{
		$licenseKeyStatus = Blocks::app()->sites->licenseKeyStatus;
		if ($licenseKeyStatus->licenseKeyStatus == LicenseKeyStatus::InvalidKey)
			return true;

		return false;
	}

	/**
	 * @return bool
	 */
	public function criticalUpdateAvailable()
	{
		if (!Blocks::app()->updates->isUpdateInfoCached())
			return false;

		$updateInfo = Blocks::app()->updates->updateInfo;
		return $updateInfo->criticalUpdateAvailable;
	}

	/**
	 * @return mixed
	 */
	public function updateInfoCached()
	{
		return Blocks::app()->updates->isUpdateInfoCached();
	}

	/**
	 * @param bool $forceRefresh
	 * @return CpUpdatesTag
	 */
	public function updates($forceRefresh = false)
	{
		return new CpUpdatesTag($forceRefresh);
	}
}
