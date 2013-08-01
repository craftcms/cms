<?php
namespace Craft;

/**
 *
 */
class UpdatesService extends BaseApplicationComponent
{
	private $_updateModel;

	/**
	 * @param $craftReleases
	 * @return bool
	 */
	public function criticalCraftUpdateAvailable($craftReleases)
	{
		foreach ($craftReleases as $craftRelease)
		{
			if ($craftRelease->critical)
			{
				return true;
			}
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
					{
						return true;
					}
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
		return (isset($this->_updateModel) || craft()->fileCache->get('updateinfo') !== false);
	}

	/**
	 * @return int
	 */
	public function getTotalAvailableUpdates()
	{
		$count = 0;

		if ($this->isUpdateInfoCached())
		{
			$updateModel = $this->getUpdates();

			// Could be false!
			if ($updateModel)
			{
				if (!empty($updateModel->app))
				{
					if ($updateModel->app->versionUpdateStatus == VersionUpdateStatus::UpdateAvailable)
					{
						if (isset($updateModel->app->releases) && count($updateModel->app->releases) > 0)
						{
							$count++;
						}
					}
				}

				if (!empty($updateModel->plugins))
				{
					foreach ($updateModel->plugins as $plugin)
					{
						if ($plugin->status == PluginVersionUpdateStatus::UpdateAvailable)
						{
							if (isset($plugin->releases) && count($plugin->releases) > 0)
							{
								$count++;
							}
						}
					}
				}
			}
		}

		return $count;
	}

	/**
	 * @return mixed
	 */
	public function isCriticalUpdateAvailable()
	{
		if ((isset($this->_updateModel) && $this->_updateModel->app->criticalUpdateAvailable))
		{
			return true;
		}

		return false;
	}

	/**
	 * @return mixed
	 */
	public function isManualUpdateRequired()
	{
		if ((isset($this->_updateModel) && $this->_updateModel->app->manualUpdateRequired))
		{
			return true;
		}

		return false;
	}

	/**
	 * @param bool $forceRefresh
	 * @return UpdateModel|false
	 */
	public function getUpdates($forceRefresh = false)
	{
		if (!isset($this->_updateModel) || $forceRefresh)
		{
			$updateModel = false;

			if (!$forceRefresh)
			{
				// get the update info from the cache if it's there
				$updateModel = craft()->fileCache->get('updateinfo');
			}

			// fetch it if it wasn't cached, or if we're forcing a refresh
			if ($forceRefresh || $updateModel === false)
			{
				$etModel = $this->check();

				if ($etModel == null)
				{
					$updateModel = new UpdateModel();
					$errors[] = Craft::t('Craft is unable to determine if an update is available at this time.');
					$updateModel->errors = $errors;
				}
				else
				{
					$updateModel = $etModel->data;

					// cache it and set it to expire according to config
					craft()->fileCache->set('updateinfo', $updateModel);
				}
			}

			$this->_updateModel = $updateModel;
		}

		return $this->_updateModel;
	}

	/**
	 * @return bool
	 */
	public function flushUpdateInfoFromCache()
	{
		Craft::log('Flushing update info from cache.', LogLevel::Info, true);

		if (IOHelper::clearFolder(craft()->path->getCompiledTemplatesPath(), true) && IOHelper::clearFolder(craft()->path->getCachePath(), true))
		{
			return true;
		}

		return false;
	}

	/**
	 * @param $version
	 * @param $build
	 * @param $releaseDate
	 * @return bool
	 */
	public function setNewCraftInfo($version, $build, $releaseDate)
	{
		$info = Craft::getInfo();

		$info->version     = $version;
		$info->build       = $build;
		$info->releaseDate = $releaseDate;

		// TODO: Deprecate after next breakpoint release.
		$info->track = '@@@track@@@';

		return Craft::saveInfo($info);
	}

	/**
	 * @param BasePlugin $plugin
	 * @return bool
	 */
	public function setNewPluginInfo(BasePlugin $plugin)
	{
		$affectedRows = craft()->db->createCommand()->update('plugins', array(
			'version' => $plugin->getVersion()
		), array(
			'class' => $plugin->getClassHandle()
		));

		return (bool) $affectedRows;
	}

	/**
	 * @return UpdateModel
	 */
	public function check()
	{
		craft()->config->maxPowerCaptain();

		$updateModel = new UpdateModel();
		$updateModel->app = new AppUpdateModel();
		$updateModel->app->localBuild   = CRAFT_BUILD;
		$updateModel->app->localVersion = CRAFT_VERSION;

		$plugins = craft()->plugins->getPlugins();

		$pluginUpdateModels = array();

		foreach ($plugins as $plugin)
		{
			$pluginUpdateModel = new PluginUpdateModel();
			$pluginUpdateModel->class = $plugin->getClassHandle();
			$pluginUpdateModel->localVersion = $plugin->version;

			$pluginUpdateModels[$plugin->getClassHandle()] = $pluginUpdateModel;
		}

		$updateModel->plugins = $pluginUpdateModels;

		$etModel = craft()->et->checkForUpdates($updateModel);
		return $etModel;
	}

	/**
	 * Checks to see if Craft can write to a defined set of folders/files that are needed for auto-update to work.
	 *
	 * @return array|null
	 */
	public function getUnwritableFolders()
	{
		$checkPaths = array(
			craft()->path->getAppPath(),
			craft()->path->getPluginsPath(),
		);

		$errorPath = null;

		foreach ($checkPaths as $writablePath)
		{
			if (!IOHelper::isWritable($writablePath))
			{
				$errorPath[] = IOHelper::getRealPath($writablePath);
			}
		}

		return $errorPath;
	}

	/**
	 * @param $manual
	 * @param $handle
	 * @return array
	 */
	public function prepareUpdate($manual, $handle)
	{
		Craft::log('Preparing to update '.$handle.'.', LogLevel::Info, true);

		try
		{
			$updater = new Updater();

			// No need to get the latest update info if this is a manual update.
			if (!$manual)
			{
				$updater->getLatestUpdateInfo();
			}

			$updater->checkRequirements();

			Craft::log('Finished preparing to update '.$handle.'.', LogLevel::Info, true);
			return array('success' => true);
		}
		catch (\Exception $e)
		{
			return array('success' => false, 'message' => $e->getMessage());
		}
	}

	/**
	 * @return array
	 */
	public function processUpdateDownload()
	{
		Craft::log('Starting to process the update download.', LogLevel::Info, true);

		try
		{
			$updater = new Updater();
			$result = $updater->processDownload();
			$result['success'] = true;

			Craft::log('Finished processing the update download.', LogLevel::Info, true);
			return $result;
		}
		catch (\Exception $e)
		{
			return array('success' => false, 'message' => $e->getMessage());
		}
	}

	/**
	 * @param $uid
	 * @return array
	 */
	public function backupFiles($uid)
	{
		Craft::log('Starting to backup files that need to be updated.', LogLevel::Info, true);

		try
		{
			$updater = new Updater();
			$updater->backupFiles($uid);

			Craft::log('Finished backing up files.', LogLevel::Info, true);
			return array('success' => true);
		}
		catch (\Exception $e)
		{
			return array('success' => false, 'message' => $e->getMessage());
		}
	}

	/**
	 * @param $uid
	 * @return array
	 */
	public function updateFiles($uid)
	{
		Craft::log('Starting to update files.', LogLevel::Info, true);

		try
		{
			$updater = new Updater();
			$updater->updateFiles($uid);

			Craft::log('Finished updating files.', LogLevel::Info, true);
			return array('success' => true);
		}
		catch (\Exception $e)
		{
			return array('success' => false, 'message' => $e->getMessage());
		}
	}

	/**
	 * @param $uid
	 * @return array
	 */
	public function backupDatabase($uid)
	{
		Craft::log('Starting to backup database.', LogLevel::Info, true);

		try
		{
			$updater = new Updater();
			$result = $updater->backupDatabase($uid);

			if (!$result)
			{
				Craft::log('Did not backup database because there were no migrations to run.', LogLevel::Info, true);
				return array('success' => true);
			}
			else
			{
				Craft::log('Finished backing up database.', LogLevel::Info, true);
				return array('success' => true, 'dbBackupPath' => $result);
			}
		}
		catch (\Exception $e)
		{
			return array('success' => false, 'message' => $e->getMessage());
		}
	}

	/**
	 * @param      $uid
	 * @param      $handle
	 * @param bool $dbBackupPath
	 *
	 * @throws Exception
	 * @return array
	 */
	public function updateDatabase($uid, $handle, $dbBackupPath = false)
	{
		Craft::log('Starting to update the database.', LogLevel::Info, true);

		try
		{
			$updater = new Updater();

			if ($handle == 'craft')
			{
				Craft::log('Craft wants to update the database.', LogLevel::Info, true);
				$updater->updateDatabase($uid, $dbBackupPath);
				Craft::log('Craft is done updating the database.', LogLevel::Info, true);
			}
			else
			{
				$plugin = craft()->plugins->getPlugin($handle);
				if ($plugin)
				{
					Craft::log('The plugin, '.$plugin->getName().' wants to update the database.', LogLevel::Info, true);
					$updater->updateDatabase($uid, $dbBackupPath, $plugin);
					Craft::log('The plugin, '.$plugin->getName().' is done updating the database.', LogLevel::Info, true);
				}
				else
				{
					Craft::log('Cannot find a plugin with the handle '.$handle.' or it is not enabled, therefore it cannot update the database.', LogLevel::Error);
					throw new Exception(Craft::t('Cannot find an enabled plugin with the handle {handle}.', array('handle' => $handle)));
				}
			}

			return array('success' => true);
		}
		catch (\Exception $e)
		{
			return array('success' => false, 'message' => $e->getMessage());
		}
	}

	/**
	 * @param $uid
	 * @param $handle
	 * @return array
	 */
	public function updateCleanUp($uid, $handle)
	{
		Craft::log('Starting to clean up after the update.', LogLevel::Info, true);

		try
		{
			$updater = new Updater();
			$updater->cleanUp($uid, $handle);

			Craft::log('Finished cleaning up after the update.', LogLevel::Info, true);
			return array('success' => true);
		}
		catch (\Exception $e)
		{
			return array('success' => false, 'message' => $e->getMessage());
		}
	}

	/**
	 * @param      $uid
	 * @param bool $dbBackupPath
	 * @return array
	 */
	public function rollbackUpdate($uid, $dbBackupPath = false)
	{
		try
		{
			craft()->config->maxPowerCaptain();

			if ($dbBackupPath && craft()->config->get('backupDbOnUpdate') && craft()->config->get('restoreDbOnUpdateFailure'))
			{
				Craft::log('Rolling back any database changes.', LogLevel::Info, true);
				UpdateHelper::rollBackDatabaseChanges($dbBackupPath);
				Craft::log('Done rolling back any database changes.', LogLevel::Info, true);
			}

			// If uid !== false, it's an auto-update.
			if ($uid !== false)
			{
				Craft::log('Rolling back any file changes.', LogLevel::Info, true);
				$manifestData = UpdateHelper::getManifestData(UpdateHelper::getUnzipFolderFromUID($uid));

				if ($manifestData)
				{
					UpdateHelper::rollBackFileChanges($manifestData);
				}

				Craft::log('Done rolling back any file changes.', LogLevel::Info, true);
			}

			Craft::log('Finished rolling back changes.', LogLevel::Info, true);
			return array('success' => true);
		}
		catch (\Exception $e)
		{
			return array('success' => false, 'message' => $e->getMessage());
		}
	}

	/**
	 * Returns if a plugin needs to run a database update or not.
	 *
	 * @return bool
	 */
	public function isPluginDbUpdateNeeded()
	{
		$plugins = craft()->plugins->getPlugins();

		foreach ($plugins as $plugin)
		{
			if (craft()->plugins->doesPluginRequireDatabaseUpdate($plugin))
			{
				return true;
			}
		}

		return false;
	}

	/**
	 * Returns if Craft needs to run a database update or not.
	 *
	 * @access private
	 * @return bool
	 */
	public function isCraftDbUpdateNeeded()
	{
		return (CRAFT_BUILD > Craft::getBuild());
	}

	/**
	 * Returns true is the build stored in craft_info is less than the minimum required build on the file system.
	 * This effectively makes sure that a user cannot manually update past a manual breakpoint.
	 *
	 * @return bool
	 */
	public function isBreakpointUpdateNeeded()
	{
		// Only Craft has the concept of a breakpoint, not plugins.
		if ($this->isCraftDbUpdateNeeded())
		{
			return (Craft::getBuild() < CRAFT_MIN_BUILD_REQUIRED);
		}
		else
		{
			return false;
		}
	}

	/**
	 * Returns true if the track on the file system matches the track in the database, false otherwise.
	 * This effectively makes sure that a user cannot change tracks while manually updating.
	 *
	 * @return bool
	 */
	public function isTrackValid()
	{
		if (($track = Craft::getTrack()) && $track != CRAFT_TRACK)
		{
			return false;
		}

		return true;
	}

	/**
	 * Returns a list of plugins that are in need of a database update.
	 *
	 * @return array|null
	 */
	public function getPluginsThatNeedDbUpdate()
	{
		$pluginsThatNeedDbUpdate = array();

		$plugins = craft()->plugins->getPlugins();

		foreach ($plugins as $plugin)
		{
			if (craft()->plugins->doesPluginRequireDatabaseUpdate($plugin))
			{
				$pluginsThatNeedDbUpdate[] = $plugin;
			}
		}

		return $pluginsThatNeedDbUpdate;
	}
}
