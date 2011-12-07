<?php

class CpUpdatesTag extends Tag
{
	private $_updates;

	public function init()
	{
		$blocksUpdateInfo = Blocks::app()->request->blocksUpdateInfo;
		$this->_updates = array();

		// blocks first.
		if ($blocksUpdateInfo['blocksVersionUpdateStatus'] == BlocksVersionUpdateStatus::UpdateAvailable && count($blocksUpdateInfo['blocksLatestCoreReleases']) > 0)
		{
			$notes = $this->_generateUpdateNotes($blocksUpdateInfo['blocksLatestCoreReleases'], 'Blocks');
			$this->_updates[] = array(
				'name' => 'Blocks '.$blocksUpdateInfo['blocksClientEdition'],
				'handle' => 'Blocks',
				'version' => $blocksUpdateInfo['blocksLatestVersionNo'].'.'.$blocksUpdateInfo['blocksLatestBuildNo'],
				'critical' => $blocksUpdateInfo['blocksCriticalUpdateAvailable'],
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
					$this->_updates[] = array(
						'name' => $pluginInfo['displayName'],
						'handle' => $pluginInfo['handle'],
						'version' => $pluginInfo['latestVersion'],
						'critical' => $pluginInfo['criticalUpdateAvailable'],
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

	public function __toString()
	{
		return count($this->_updates);
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
