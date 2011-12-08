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
		if (($blocksUpdateInfo = Blocks::app()->request->getBlocksUpdateInfo()) !== false)
			return (bool) ($blocksUpdateInfo['blocksLicenseStatus'] == LicenseKeyStatus::InvalidKey);

		return false;
	}

	public function criticalUpdateAvailable()
	{
		if (($blocksUpdateInfo = Blocks::app()->request->getBlocksUpdateInfo()) !== null)
			return (bool) ($blocksUpdateInfo['blocksCriticalUpdateAvailable']);
	}

	public function updatesCached()
	{
		return (Blocks::app()->request->getBlocksUpdateInfo() !== false);
	}

	public function updates()
	{
		return new CpUpdatesTag;
	}
}
