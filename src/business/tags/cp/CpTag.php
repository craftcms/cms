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
		$blocksUpdateInfo = Blocks::app()->request->blocksUpdateInfo;
		$updates = array();

		// blocks first.
		if ($blocksUpdateInfo['blocksVersionUpdateStatus'] == BlocksVersionUpdateStatus::UpdateAvailable && count($blocksUpdateInfo['blocksLatestCoreReleases']) > 0)
		{
			$notes = $this->_generateUpdateNotes($blocksUpdateInfo['blocksLatestCoreReleases'], 'Blocks');
			$updates[] = array(
				'name' => 'Blocks '.$blocksUpdateInfo['blocksClientEdition'],
				'handle' => 'Blocks',
				'version' => $blocksUpdateInfo['blocksLatestVersionNo'].'.'.$blocksUpdateInfo['blocksLatestBuildNo'],
				'notes' => $notes,
			);

		}

		// plugins second.
		if (isset($blocksUpdateInfo['pluginNamesAndVersions']) && count($blocksUpdateInfo['pluginNamesAndVersions']) > 0)
		{
			foreach ($blocksUpdateInfo['pluginNamesAndVersions'] as $pluginInfo)
			{
				if ($pluginInfo['status'] == PluginVersionUpdateStatus::UpdateAvailable && count($pluginInfo['newerReleases']) > 0)
				{
					$notes = $this->_generateUpdateNotes($pluginInfo['newerReleases'], $pluginInfo['displayName']);
					$updates[] = array(
						'name' => $pluginInfo['displayName'],
						'handle' => $pluginInfo['handle'],
						'version' => $pluginInfo['latestVersion'],
						'notes' => $notes,
					);
				}
			}
		}

		return $updates;
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
