<?php
namespace Blocks;

/**
 *
 */
class UpdatesService extends Component
{
	private $_updateInfo;
	private $_isSystemOn;

	/**
	 * @param $forceRefresh
	 * @return mixed
	 */
	public function getAllAvailableUpdates($forceRefresh = false)
	{
		$updates = array();

		if (!$forceRefresh && !$this->isUpdateInfoCached())
			return null;

		$updateInfo = $this->getUpdateInfo($forceRefresh);

		// blocks first.
		if ($updateInfo->blocks->versionUpdateStatus == VersionUpdateStatus::UpdateAvailable && count($updateInfo->blocks->releases) > 0)
		{
			$notes = $this->_generateUpdateNotes($updateInfo->blocks->releases, 'Blocks');
			$edition = Blocks::getEdition();
			$updates[] = array(
				'name' => 'Blocks'.($edition == Edition::Standard ? '' : ' '.$edition),
				'handle' => 'Blocks',
				'version' => $updateInfo->blocks->latestVersion.' Build '.$updateInfo->blocks->latestBuild,
				'critical' => $updateInfo->blocks->criticalUpdateAvailable,
				'manualUpdateRequired' => $updateInfo->blocks->manualUpdateRequired,
				'notes' => $notes,
			);

		}

		// plugins second.
		if ($updateInfo->plugins !== null && count($updateInfo->plugins) > 0)
		{
			foreach ($updateInfo->plugins as $plugin)
			{
				if ($plugin->status == PluginVersionUpdateStatus::UpdateAvailable && count($plugin->releases) > 0)
				{
					$notes = $this->_generateUpdateNotes($plugin->releases, $plugin->displayName);
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
			if ($blocksRelease->critical)
				return true;
		}

		return false;
	}

	/**
	 * @param $blocksReleases
	 * @return bool
	 */
	public function manualUpdateRequired($blocksReleases)
	{
		foreach ($blocksReleases as $blocksRelease)
		{
			if ($blocksRelease->manual_update_required)
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
			if ($plugin->status == PluginVersionUpdateStatus::UpdateAvailable && count($plugin->releases) > 0)
			{
				foreach ($plugin->releases as $release)
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
		if ((isset($this->_updateInfo) && $this->_updateInfo->blocks->criticalUpdateAvailable))
			return true;

		return false;
	}

	/**
	 * @return mixed
	 */
	public function isManualUpdateRequired()
	{
		if ((isset($this->_updateInfo) && $this->_updateInfo->blocks->manualUpdateRequired))
			return true;

		return false;
	}

	/**
	 * @param bool $forceRefresh
	 * @return mixed
	 */
	public function getUpdateInfo($forceRefresh = false)
	{
		if (!isset($this->_updateInfo) || $forceRefresh)
		{
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
	public function flushUpdateInfoFromCache()
	{
		Blocks::log('Flushing update info from cache.');
		if (b()->fileCache->delete('updateInfo'))
			return true;

		return false;
	}

	/**
	 * @param $version
	 * @param $build
	 * @return bool
	 */
	public function setNewVersionAndBuild($version, $build)
	{
		$info = Info::model()->find();
		$info->version = $version;
		$info->build = $build;

		if ($info->save())
			return true;

		return false;
	}

	/**
	 * @return bool
	 */
	public function doCoreUpdate()
	{
		$coreUpdater = new CoreUpdater();
		if ($coreUpdater->start())
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
		$updateInfo = new UpdateInfo();
		$updateInfo->blocks->localBuild = Blocks::getBuild();
		$updateInfo->blocks->localVersion = Blocks::getVersion();

		//$plugins = b()->plugins->allInstalledPluginHandlesAndVersions;
		//foreach ($plugins as $plugin)
		//	$updateInfo->plugins[$plugin['handle']] = new PluginUpdateInfo($plugin);
		$updateInfo->plugins = null;

		$response = b()->et->check($updateInfo);

		$updateInfo = $response == null ? new UpdateInfo() : new UpdateInfo($response->data);
		return $updateInfo;
	}

	/**
	 * @return bool
	 */
	public function runMigrationsToTop()
	{
		Blocks::log('Running migrations to top.', \CLogger::LEVEL_INFO);
		$response = Migration::runToTop();
		if ($this->_wasMigrationSuccessful($response))
			return true;

		return false;
	}

	/**
	 * @param $migrationName
	 * @return bool
	 */
	public function runMigration($migrationName)
	{
		Blocks::log('Running migration '.$migrationName, \CLogger::LEVEL_INFO);
		$response = Migration::run($migrationName);
		if ($this->_wasMigrationSuccessful($response))
			return true;

		return false;
	}

	/**
	 * @return bool
	 */
	public function turnSystemOnAfterUpdate()
	{
		// if the system wasn't on before, we're leave it in an off state
		if (!$this->_isSystemOn)
			return true;
		else
		{
			if (Blocks::turnSystemOn())
				return true;
		}

		return false;
	}

	/**
	 * @static
	 * @return bool
	 */
	public function turnSystemOffBeforeUpdate()
	{
		// save the current state of the system for possible use later in the request.
		$this->_isSystemOn = Blocks::isSystemOn();

		// if it's not on, don't even bother.
		if ($this->_isSystemOn)
		{
			if (Blocks::turnSystemOff())
				return true;
		}

		return false;
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
			$notes .= '<ul><li>'.$update->notes.'</li></ul>';
		}

		return $notes;
	}

	/**
	 * @param $response
	 * @return bool
	 */
	private function _wasMigrationSuccessful($response)
	{
		if (strpos($response, 'Migrated up successfully.') !== false || strpos($response, 'No new migration found.') !== false)
			return true;

		return false;
	}

}
