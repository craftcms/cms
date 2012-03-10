<?php
namespace Blocks;

/**
 *
 */
class UpdatesService extends BaseComponent
{
	private $_updateInfo;

	/**
	 * @param $forceRefresh
	 * @return mixed
	 */
	public function updates($forceRefresh = false)
	{
		$updates = array();

		if (!$forceRefresh && !$this->isUpdateInfoCached())
			return null;

		$blocksUpdateInfo = $this->getUpdateInfo($forceRefresh);

		// blocks first.
		if ($blocksUpdateInfo->versionUpdateStatus == VersionUpdateStatus::UpdateAvailable && count($blocksUpdateInfo->newerReleases) > 0)
		{
			$notes = $this->_generateUpdateNotes($blocksUpdateInfo->newerReleases, 'Blocks');
			$updates[] = array(
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
					$updates[] = array(
						'name' => $plugin->displayName,
						'handle' => $plugin->class,
						'version' => $plugin->latestVersion,
						'critical' => $plugin->criticalUpdateAvailable,
						'notes' => $notes,
					);
				}
			}
		}

		return $updates;
	}

	/**
	 * @param $blocksReleases
	 * @return bool
	 */
	public function criticalBlocksUpdateAvailable($blocksReleases)
	{
		foreach ($blocksReleases as $blocksRelease)
		{
			if ($blocksRelease->critical !== null)
				return true;
		}

		return false;
	}

	/**
	 * @param $plugins
	 * @return bool
	 */
	public function criticalPluginUpdateAvailable($plugins)
	{
		foreach ($plugins as $plugin)
		{
			if ($plugin->status == PluginVersionUpdateStatus::UpdateAvailable && count($plugin->newerReleases) > 0)
			{
				foreach ($plugin->newerReleases as $release)
				{
					if ($release->critical)
						return true;
				}
			}
		}

		return false;
	}

	/**
	 * @return bool
	 */
	public function isUpdateInfoCached()
	{
		return (isset($this->_updateInfo) || b()->fileCache->get('updateInfo') !== false);
	}

	/**
	 * @return mixed
	 */
	public function isCriticalUpdateAvailable()
	{
		return $this->updateInfo->criticalUpdateAvailable;
	}

	/**
	 * @param bool $forceRefresh
	 * @return mixed
	 */
	public function getUpdateInfo($forceRefresh = false)
	{
		if (!isset($this->_updateInfo) || $forceRefresh)
		{
			$updateInfo = new UpdateInfo();
			// no update info if we can't find the license keys.

			if (!$forceRefresh)
			{
				// get the update info from the cache if it's there
				$updateInfo = b()->fileCache->get('updateInfo');
			}

			// fetch it if it wasn't cached, or if we're forcing a refresh
			if ($forceRefresh || $updateInfo === false)
			{
				$updateInfo = $this->check();

				if ($updateInfo == null)
					$updateInfo = new UpdateInfo();

				// cache it and set it to expire according to config
				b()->fileCache->set('updateInfo', $updateInfo, b()->config->cacheTimeSeconds);
			}

			$this->_updateInfo = $updateInfo;
		}

		return $this->_updateInfo;
	}

	/**
	 * @return bool
	 */
	public function doCoreUpdate()
	{
		$coreUpdater = new CoreUpdater();
		//if ($coreUpdater->start())
			return true;

		return false;
	}

	/**
	 * @param $pluginHandle
	 * @return bool
	 */
	public function doPluginUpdate($pluginHandle)
	{
		$pluginUpdater = new PluginUpdater($pluginHandle);
		if ($pluginUpdater->start())
			return true;

		return false;
	}

	/**
	 * @return UpdateInfo
	 */
	public function check()
	{
		$blocksUpdateInfo = new UpdateInfo();
		$blocksUpdateInfo->localBuild = Blocks::getBuild();
		$blocksUpdateInfo->localVersion = Blocks::getVersion();

		$plugins = b()->plugins->allInstalledPluginHandlesAndVersions;
		foreach ($plugins as $plugin)
			$blocksUpdateInfo->plugins[$plugin['handle']] = new PluginUpdateData($plugin);

		$et = new Et(EtEndPoints::Check);
		$et->getPackage()->data = $blocksUpdateInfo;
		$response = $et->phoneHome();

		$blocksUpdateInfo = $response == null ? new UpdateInfo() : new UpdateInfo($response->data);
		return $blocksUpdateInfo;
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
