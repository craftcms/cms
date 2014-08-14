<?php
namespace Craft;

/**
 * Class UpdatesService
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://buildwithcraft.com/license Craft License Agreement
 * @see       http://buildwithcraft.com
 * @package   craft.app.services
 * @since     1.0
 */
class UpdatesService extends BaseApplicationComponent
{
	// Properties
	// =========================================================================

	/**
	 * @var UpdateModel
	 */
	private $_updateModel;

	// Public Methods
	// =========================================================================

	/**
	 * @param $craftReleases
	 *
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
	 *
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
		return (isset($this->_updateModel) || craft()->cache->get('updateinfo') !== false);
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
		return (!empty($this->_updateModel->app->criticalUpdateAvailable));
	}

	/**
	 * @return mixed
	 */
	public function isManualUpdateRequired()
	{
		return (!empty($this->_updateModel->app->manualUpdateRequired));
	}

	/**
	 * @param bool $forceRefresh
	 *
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
				$updateModel = craft()->cache->get('updateinfo');
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
					craft()->cache->set('updateinfo', $updateModel);
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

		if (IOHelper::clearFolder(craft()->path->getCompiledTemplatesPath(), true) && craft()->cache->flush())
		{
			return true;
		}

		return false;
	}

	/**
	 * @param BasePlugin $plugin
	 *
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
	 * Checks to see if Craft can write to a defined set of folders/files that are
	 * needed for auto-update to work.
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
	 *
	 * @return array
	 */
	public function prepareUpdate($manual, $handle)
	{
		Craft::log('Preparing to update '.$handle.'.', LogLevel::Info, true);

		try
		{
			// Fire an 'onBeginUpdate' event and pass in the type
			$this->onBeginUpdate(new Event($this, array(
				'type' => $manual ? 'manual' : 'auto'
			)));

			$updater = new Updater();

			// Make sure we still meet the existing requirements.
			$updater->checkRequirements();

			// No need to get the latest update info if this is a manual update.
			if (!$manual)
			{
				$updateModel = $this->getUpdates();
				Craft::log('Updating from '.$updateModel->app->localVersion.'.'.$updateModel->app->localBuild.' to '.$updateModel->app->latestVersion.'.'.$updateModel->app->latestBuild.'.', LogLevel::Info, true);
				$result = $updater->getUpdateFileInfo();

			}

			$result['success'] = true;

			Craft::log('Finished preparing to update '.$handle.'.', LogLevel::Info, true);
			return $result;
		}
		catch (\Exception $e)
		{
			return array('success' => false, 'message' => $e->getMessage());
		}
	}

	/**
	 * @param string $md5
	 *
	 * @return array
	 */
	public function processUpdateDownload($md5)
	{
		Craft::log('Starting to process the update download.', LogLevel::Info, true);

		try
		{
			$updater = new Updater();
			$result = $updater->processDownload($md5);
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
	 * @param string $uid
	 *
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
	 * @param string $uid
	 *
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
	 * @return array
	 */
	public function backupDatabase()
	{
		Craft::log('Starting to backup database.', LogLevel::Info, true);

		try
		{
			$updater = new Updater();
			$result = $updater->backupDatabase();

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
	 * @param string $handle
	 *
	 * @throws Exception
	 * @return array
	 */
	public function updateDatabase($handle)
	{
		Craft::log('Starting to update the database.', LogLevel::Info, true);

		try
		{
			$updater = new Updater();

			if ($handle == 'craft')
			{
				Craft::log('Craft wants to update the database.', LogLevel::Info, true);
				$updater->updateDatabase();
				Craft::log('Craft is done updating the database.', LogLevel::Info, true);
			}
			else
			{
				$plugin = craft()->plugins->getPlugin($handle);
				if ($plugin)
				{
					Craft::log('The plugin, '.$plugin->getName().' wants to update the database.', LogLevel::Info, true);
					$updater->updateDatabase($plugin);
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
	 * @param string $uid
	 * @param string $handle
	 *
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

			// Fire an 'onEndUpdate' event and pass in that it was a successful update.
			$this->onEndUpdate(new Event($this, array(
				'success' => true
			)));
		}
		catch (\Exception $e)
		{
			Craft::log('There was an error during cleanup, but we don\'t really care: '.$e->getMessage());

			// Fire an 'onEndUpdate' event and pass in that it was a successful update.
			$this->onEndUpdate(new Event($this, array(
				'success' => true
			)));
		}
	}

	/**
	 * @param string $uid
	 * @param bool   $dbBackupPath
	 *
	 * @return array
	 */
	public function rollbackUpdate($uid, $dbBackupPath = false)
	{
		try
		{
			// Fire an 'onEndUpdate' event and pass in that the update failed.
			$this->onEndUpdate(new Event($this, array(
				'success' => false
			)));

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

			Craft::log('Taking the site out of maintenance mode.', LogLevel::Info, true);
			craft()->disableMaintenanceMode();

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
	 * Returns whether a different Craft build has been uploaded.
	 *
	 * @return bool
	 */
	public function hasCraftBuildChanged()
	{
		return (CRAFT_BUILD != craft()->getBuild());
	}

	/**
	 * Returns true is the build stored in craft_info is less than the minimum required build on the file system. This
	 * effectively makes sure that a user cannot manually update past a manual breakpoint.
	 *
	 * @return bool
	 */
	public function isBreakpointUpdateNeeded()
	{
		return (CRAFT_MIN_BUILD_REQUIRED > craft()->getBuild());
	}

	/**
	 * Returns whether the uploaded DB schema is equal to or greater than the installed schema
	 *
	 * @return bool
	 */
	public function isSchemaVersionCompatible()
	{
		return version_compare(CRAFT_SCHEMA_VERSION, craft()->getSchemaVersion(), '>=');
	}

	/**
	 * Returns whether Craft needs to run any database migrations.
	 *
	 * @return bool
	 */
	public function isCraftDbMigrationNeeded()
	{
		return version_compare(CRAFT_SCHEMA_VERSION, craft()->getSchemaVersion(), '>');
	}

	/**
	 * Updates the Craft version info in the craft_info table.
	 *
	 * @return bool
	 */
	public function updateCraftVersionInfo()
	{
		$info = craft()->getInfo();
		$info->version = CRAFT_VERSION;
		$info->build = CRAFT_BUILD;
		$info->schemaVersion = CRAFT_SCHEMA_VERSION;
		$info->track = CRAFT_TRACK;
		$info->releaseDate = CRAFT_RELEASE_DATE;

		return craft()->saveInfo($info);
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

	/**
	 * Fires an 'onBeginUpdate' event.
	 *
	 * @param Event $event
	 *
	 * @return null
	 */
	public function onBeginUpdate(Event $event)
	{
		$this->raiseEvent('onBeginUpdate', $event);
	}

	/**
	 * Fires an 'onEndUpdate' event.
	 *
	 * @param Event $event
	 *
	 * @return null
	 */
	public function onEndUpdate(Event $event)
	{
		$this->raiseEvent('onEndUpdate', $event);
	}
}
