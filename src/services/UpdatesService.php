<?php
namespace Blocks;

/**
 *
 */
class UpdatesService extends \CApplicationComponent
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

		if (!$forceRefresh && !$this->getIsUpdateInfoCached())
			return null;

		$updateInfo = $this->getUpdateInfo($forceRefresh);

		// blocks first.
		if ($updateInfo->blocks->versionUpdateStatus == VersionUpdateStatus::UpdateAvailable && count($updateInfo->blocks->releases) > 0)
		{
			$notes = $this->_generateUpdateNotes($updateInfo->blocks->releases, 'Blocks');
			$product = Blocks::getProduct();
			$updates[] = array(
				'name' => Product::display($product),
				'handle' => 'Blocks',
				'version' => $updateInfo->blocks->latestVersion.' Build '.$updateInfo->blocks->latestBuild,
				'critical' => $updateInfo->blocks->criticalUpdateAvailable,
				'manualUpdateRequired' => $updateInfo->blocks->manualUpdateRequired,
				'notes' => $notes,
				'latestVersion' => $updateInfo->blocks->latestVersion,
				'latestBuild' => $updateInfo->blocks->latestBuild,
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
	public function getIsUpdateInfoCached()
	{
		return (isset($this->_updateInfo) || b()->fileCache->get('updateInfo') !== false);
	}

	/**
	 * @return mixed
	 */
	public function getIsCriticalUpdateAvailable()
	{
		if ((isset($this->_updateInfo) && $this->_updateInfo->blocks->criticalUpdateAvailable))
			return true;

		return false;
	}

	/**
	 * @return mixed
	 */
	public function getIsManualUpdateRequired()
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
	 * @param $releaseDate
	 * @return bool
	 */
	public function setNewBlocksInfo($version, $build, $releaseDate)
	{
		$info = Info::model()->find();
		$info->version = $version;
		$info->build = $build;
		$info->release_date = $releaseDate;

		if ($info->save())
			return true;

		return false;
	}

	/**
	 * @return bool
	 */
	public function doAppUpdate()
	{
		$appUpdater = new AppUpdater();
		if ($appUpdater->start())
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

		$plugins = b()->plugins->enabledPluginClassNamesAndVersions;

		if ($plugins)
		{
			foreach ($plugins as $className => $localVersion)
			{
				$pluginUpdateInfo = new PluginUpdateInfo();
				$pluginUpdateInfo->class = $className;
				$pluginUpdateInfo->localVersion = $localVersion;

				$updateInfo->plugins[$className] = $pluginUpdateInfo;
			}
		}

		$response = b()->et->check($updateInfo);

		$updateInfo = $response == null ? new UpdateInfo() : new UpdateInfo($response->data);
		return $updateInfo;
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
	 * Checks to see if Blocks can write to a defined set of folders/files that are needed for auto-update to work.
	 * @return array|null
	 */
	public function getUnwritableDirectories()
	{
		$checkPaths = array(
			b()->file->set(b()->path->appPath, false),
			b()->file->set(b()->path->pluginsPath, false),
		);

		$errorPath = null;
		foreach ($checkPaths as $writablePath)
		{
			if (!$writablePath->writable)
			{
				$errorPath[] = $writablePath->realPath;
			}
		}

		return $errorPath;
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
}
