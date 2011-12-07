<?php

class CpUpdatesTag extends Tag
{
	private $_updates;

	private function init()
	{
		$blocksUpdateInfo = Blocks::app()->request->blocksUpdateInfo;
		$this->_updates = array();

		// blocks first.
		if ($blocksUpdateInfo['blocksVersionUpdateStatus'] == BlocksVersionUpdateStatus::UpdateAvailable && count($blocksUpdateInfo['blocksLatestCoreReleases']) > 0)
		{
			$notes = $this->_generateUpdateNotes($blocksUpdateInfo['blocksLatestCoreReleases'], 'Blocks');
			$_updates[] = array(
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
					$_updates[] = array(
						'name' => $pluginInfo['displayName'],
						'handle' => $pluginInfo['handle'],
						'version' => $pluginInfo['latestVersion'],
						'notes' => $notes,
					);
				}
			}
		}
	}

	public function __toArray()
	{
		return $this->_updates;
	}
}