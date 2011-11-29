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
		return new BoolTag(false);
	}

	public function badLicenseKey()
	{
		return new BoolTag(true);
	}

	public function criticalUpdateAvailable()
	{
		return new BoolTag(true);
	}
}
