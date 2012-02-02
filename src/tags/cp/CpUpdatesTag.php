<?php
namespace Blocks;

/**
 *
 */
class CpUpdatesTag extends Tag
{
	private $_updates;

	/**
	 * @param $forceRefresh
	 * @return mixed
	 */
	public function init($forceRefresh)
	{
		$this->_updates = array();

		if (!$forceRefresh && !Blocks::app()->update->isUpdateInfoCached())
			return;

		$blocksUpdateInfo = Blocks::app()->update->getUpdateInfo($forceRefresh);

		// blocks first.
		if ($blocksUpdateInfo->versionUpdateStatus == VersionUpdateStatus::UpdateAvailable && count($blocksUpdateInfo->newerReleases) > 0)
		{
			$notes = $this->_generateUpdateNotes($blocksUpdateInfo->newerReleases, 'Blocks');
			$this->_updates[] = array(
				'name' => 'Blocks '.Blocks::getEdition(),
				'handle' => 'Blocks',
				'version' => $blocksUpdateInfo->latestVersion.'.'.$blocksUpdateInfo->latestBuild,
				'critical' => $blocksUpdateInfo->criticalUpdateAvailable,
				'notes' => $notes,
			);

		}

		// plugins second.
		if ($blocksUpdateInfo->plugins !== null && count($blocksUpdateInfo->plugins) > 0)
		{
			foreach ($blocksUpdateInfo->plugins as $plugin)
			{
				if ($plugin->status == PluginVersionUpdateStatus::UpdateAvailable && count($plugin->newerReleases) > 0)
				{
					$notes = $this->_generateUpdateNotes($plugin->newerReleases, $plugin->displayName);
					$this->_updates[] = array(
						'name' => $plugin->displayName,
						'handle' => $plugin->class,
						'version' => $plugin->latestVersion,
						'critical' => $plugin->criticalUpdateAvailable,
						'notes' => $notes,
					);
				}
			}
		}
	}

	/**
	 * @return mixed
	 */
	public function __toArray()
	{
		return $this->_updates;
	}

	/**
	 * @return string
	 */
	public function __toString()
	{
		return $this->_updates ? 'y' : '';
	}

	/**
	 * @return int
	 */
	public function length()
	{
		return count($this->_updates);
	}

	/**
	 * @param $updates
	 * @param $name
	 * @return string
	 */
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
