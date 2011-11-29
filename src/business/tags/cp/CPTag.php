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

		return new ArrayTag($sectionTags);
	}

	public function noLicenseKey()
	{
		if (($blocksUpdateInfo = Blocks::app()->request->getBlocksUpdateInfo()) !== null)
			return $blocksUpdateInfo['blocksLicenseStatus'] == LicenseKeyStatus::MissingKey ? new BoolTag(true) : new BoolTag(false);

		return new BoolTag(false);
	}

	public function badLicenseKey()
	{
		if (($blocksUpdateInfo = Blocks::app()->request->getBlocksUpdateInfo()) !== null)
			return $blocksUpdateInfo['blocksLicenseStatus'] == LicenseKeyStatus::InvalidKey ? new BoolTag(true) : new BoolTag(false);

		return new BoolTag(false);
	}

	public function criticalUpdateAvailable()
	{
		return new BoolTag(true);
	}
}
