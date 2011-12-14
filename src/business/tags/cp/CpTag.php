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
		if (($blocksUpdateData = Blocks::app()->update->blocksUpdateData()) !== false)
			return (bool)($blocksUpdateData->licenseStatus == LicenseKeyStatus::InvalidKey);

		return false;
	}

	public function criticalUpdateAvailable()
	{
		if (($blocksUpdateData = Blocks::app()->update->blocksUpdateInfo()) !== null)
			return (bool)($blocksUpdateData->criticalUpdateAvailable);
	}

	public function updatesCached()
	{
		return (Blocks::app()->update->blocksUpdateInfo() !== false);
	}

	public function updates($fetch = false)
	{
		return new CpUpdatesTag($fetch);
	}
}
