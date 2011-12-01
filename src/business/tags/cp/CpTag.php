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

	public function updates()
	{
		$blocksUpdateInfo = Blocks::app()->site->versionCheck();
		$arr = array();

		// blocks first.
		if ($blocksUpdateInfo['blocksVersionUpdateStatus'] == BlocksVersionUpdateStatus::UpdateAvailable && count($blocksUpdateInfo['blocksLatestCoreReleases']) > 0)
		{
			$notes = $this->_generateUpdateNotes($blocksUpdateInfo['blocksLatestCoreReleases'], 'Blocks');
			$arr[] = new ArrayTag(array(
				'name' => 'Blocks '.$blocksUpdateInfo['blocksClientEdition'],
				'version' => $blocksUpdateInfo['blocksLatestVersionNo'].'.'.$blocksUpdateInfo['blocksLatestBuildNo'],
				'notes' => $notes,
			));

		}

		// plugins second.
		if (isset($blocksUpdateInfo['pluginNamesAndVersions']) && count($blocksUpdateInfo['pluginNamesAndVersions']) > 0)
		{
			foreach ($blocksUpdateInfo['pluginNamesAndVersions'] as $pluginInfo)
			{
				if ($pluginInfo['status'] == PluginVersionUpdateStatus::UpdateAvailable && count($pluginInfo['newerReleases']) > 0)
				{
					$notes = $this->_generateUpdateNotes($pluginInfo['newerReleases'], $pluginInfo['displayName']);
					$arr[] = new ArrayTag(array(
						'name' => $pluginInfo['displayName'],
						'version' => $pluginInfo['latestVersion'],
						'notes' => $notes,
					));
				}
			}
		}

		return new ArrayTag($arr);
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
