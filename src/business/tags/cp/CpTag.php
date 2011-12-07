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

	public function noLicenseKey()
	{
		if (($blocksUpdateInfo = Blocks::app()->request->getBlocksUpdateInfo()) !== null)
			return (bool) ($blocksUpdateInfo['blocksLicenseStatus'] == LicenseKeyStatus::MissingKey);

		return false;
	}

	public function badLicenseKey()
	{
		if (($blocksUpdateInfo = Blocks::app()->request->getBlocksUpdateInfo()) !== null)
			return (bool) ($blocksUpdateInfo['blocksLicenseStatus'] == LicenseKeyStatus::InvalidKey);

		return false;
	}

	public function criticalUpdateAvailable()
	{
		return true;
	}

	public function updates()
	{
		return new CpUpdatesTag;
	}

	private function _generateUpdateNotes($updates, $name)
	{
		$notes = '';
		foreach ($updates as $update)
		{
			$notes .= '<h5>'.$name.' '.$update['version'].($name == 'Blocks' ? '.'.$update['build_number'] : '').'</h5>';
			$notes .= '<ul><li>'.$update['release_notes'].'</li></ul>';
		}

		return $notes;
	}
}
