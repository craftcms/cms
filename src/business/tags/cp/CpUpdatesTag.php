<?php

class CpUpdatesTag extends Tag
{
	private $_updates;

	public function init()
	{
		$blocksUpdateData = Blocks::app()->update->blocksUpdateInfo();
		$this->_updates = array();

		// blocks first.
		if ($blocksUpdateData->versionUpdateStatus == BlocksVersionUpdateStatus::UpdateAvailable && count($blocksUpdateData->newerReleases) > 0)
		{
			$notes = $this->_generateUpdateNotes($blocksUpdateData->newerReleases, 'Blocks');
			$this->_updates[] = array(
				'name' => 'Blocks '.$blocksUpdateData->localEdition,
				'handle' => 'Blocks',
				'version' => $blocksUpdateData->latestVersion.'.'.$blocksUpdateData->latestBuild,
				'critical' => $blocksUpdateData->criticalUpdateAvailable,
				'notes' => $notes,
			);

		}

		// plugins second.
		if ($blocksUpdateData->plugins !== null && count($blocksUpdateData->plugins) > 0)
		{
			foreach ($blocksUpdateData->plugins as $plugin)
			{
				if ($plugin->status == PluginVersionUpdateStatus::UpdateAvailable && count($plugin->newerReleases) > 0)
				{
					$notes = $this->_generateUpdateNotes($plugin->newerReleases, $plugin->displayName);
					$this->_updates[] = array(
						'name' => $plugin->displayName,
						'handle' => $plugin->handle,
						'version' => $plugin->latestVersion,
						'critical' => $plugin->criticalUpdateAvailable,
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
			$notes .= '<h5>'.$name.' '.$update->version.($name == 'Blocks' ? '.'.$update->build : '').'</h5>';
			$notes .= '<ul><li>'.$update->releaseNotes.'</li></ul>';
		}

		return $notes;
	}
}
