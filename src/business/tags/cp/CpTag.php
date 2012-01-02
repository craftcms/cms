<?php

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

	public function dashboard()
	{
		return new CpDashboardTag();
	}

	public function resource($path)
	{
		return new CpResourceTag($path);
	}

	public function sections()
	{
		$sectionTags = array();

		foreach (self::$defaultSections as $handle => $name)
		{
			$sectionTags[] = new CpSectionTag($handle, $name);
		}

		return $sectionTags;
	}

	public function badLicenseKey()
	{
		$licenseKeyStatus = Blocks::app()->site->licenseKeyStatus;
		if ($licenseKeyStatus->licenseKeyStatus == LicenseKeyStatus::InvalidKey)
			return true;

		return false;
	}

	public function criticalUpdateAvailable()
	{
		if (!Blocks::app()->update->isUpdateInfoCached())
			return false;

		$updateInfo = Blocks::app()->update->updateInfo;
		return $updateInfo->criticalUpdateAvailable;
	}

	public function updateInfoCached()
	{
		return Blocks::app()->update->isUpdateInfoCached();
	}

	public function updates($forceRefresh = false)
	{
		return new CpUpdatesTag($forceRefresh);
	}
}
