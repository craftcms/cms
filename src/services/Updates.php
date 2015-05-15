<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2015 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\services;

use Craft;
use craft\app\base\Plugin;
use craft\app\base\PluginInterface;
use craft\app\enums\PluginVersionUpdateStatus;
use craft\app\enums\VersionUpdateStatus;
use craft\app\errors\Exception;
use craft\app\events\Event;
use craft\app\events\UpdateEvent;
use craft\app\helpers\IOHelper;
use craft\app\helpers\UpdateHelper;
use craft\app\models\AppUpdate as AppUpdateModel;
use craft\app\models\PluginUpdate as PluginUpdateModel;
use craft\app\models\Update as UpdateModel;
use craft\app\updates\Updater;
use yii\base\Component;

/**
 * Class Updates service.
 *
 * An instance of the Updates service is globally accessible in Craft via [[Application::updates `Craft::$app->getUpdates()`]].
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class Updates extends Component
{
	// Constants
	// =========================================================================

	/**
     * @event UpdateEvent The event that is triggered before an update is installed.
     */
    const EVENT_BEFORE_UPDATE = 'beforeUpdate';

	/**
     * @event Event The event that is triggered after an update is installed.
     */
    const EVENT_AFTER_UPDATE = 'afterUpdate';

	/**
     * @event Event The event that is triggered after an update has failed to install.
     */
    const EVENT_AFTER_UPDATE_FAIL = 'afterUpdateFail';

	// Properties
	// =========================================================================

	/**
	 * @var UpdateModel
	 */
	private $_updateModel;

	/**
	 * @var boolean
	 */
	private $_isCraftDbMigrationNeeded;

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
		return (isset($this->_updateModel) || Craft::$app->getCache()->get('updateinfo') !== false);
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
				$updateModel = Craft::$app->getCache()->get('updateinfo');
			}

			// fetch it if it wasn't cached, or if we're forcing a refresh
			if ($forceRefresh || $updateModel === false)
			{
				$etModel = $this->check();

				if ($etModel == null)
				{
					$updateModel = new UpdateModel();
					$errors[] = Craft::t('app', 'Craft is unable to determine if an update is available at this time.');
					$updateModel->errors = $errors;
				}
				else
				{
					$updateModel = $etModel->data;

					// cache it and set it to expire according to config
					Craft::$app->getCache()->set('updateinfo', $updateModel);
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
		Craft::info('Flushing update info from cache.', __METHOD__);

		if (IOHelper::clearFolder(Craft::$app->getPath()->getCompiledTemplatesPath(), true) && Craft::$app->getCache()->flush())
		{
			return true;
		}

		return false;
	}

	/**
	 * @param PluginInterface|Plugin $plugin
	 *
	 * @return bool
	 */
	public function setNewPluginInfo(PluginInterface $plugin)
	{
		$affectedRows = Craft::$app->getDb()->createCommand()->update('{{%plugins}}', [
			'version' => $plugin->version
		], [
			'handle' => $plugin::getHandle()
		])->execute();

		return (bool) $affectedRows;
	}

	/**
	 * @return UpdateModel
	 */
	public function check()
	{
		Craft::$app->getConfig()->maxPowerCaptain();

		$updateModel = new UpdateModel();
		$updateModel->app = new AppUpdateModel();
		$updateModel->app->localVersion = Craft::$app->version;
		$updateModel->app->localBuild   = Craft::$app->build;

		$plugins = Craft::$app->getPlugins()->getAllPlugins();

		$pluginUpdateModels = [];

		foreach ($plugins as $plugin)
		{
			$pluginUpdateModel = new PluginUpdateModel();
			$pluginUpdateModel->class = $plugin::className();
			$pluginUpdateModel->localVersion = $plugin->version;

			$pluginUpdateModels[$plugin::className()] = $pluginUpdateModel;
		}

		$updateModel->plugins = $pluginUpdateModels;

		$etModel = Craft::$app->getEt()->checkForUpdates($updateModel);
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
		$checkPaths = [
			Craft::$app->getPath()->getAppPath(),
			Craft::$app->getPath()->getPluginsPath(),
		];

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
		Craft::info('Preparing to update '.$handle.'.', __METHOD__);

		try
		{
			// Fire a 'beforeUpdate' event and pass in the type
			$this->trigger(static::EVENT_BEFORE_UPDATE, new UpdateEvent([
				'type' => $manual ? 'manual' : 'auto'
			]));

			$updater = new Updater();

			// Make sure we still meet the existing requirements. This will throw an exception if the server doesn't meet Craft's current requirements.
			Craft::$app->runAction('templates/requirements-check');

			// No need to get the latest update info if this is a manual update.
			if (!$manual)
			{
				$updateModel = $this->getUpdates();
				Craft::info('Updating from '.$updateModel->app->localVersion.'.'.$updateModel->app->localBuild.' to '.$updateModel->app->latestVersion.'.'.$updateModel->app->latestBuild.'.', __METHOD__);
				$result = $updater->getUpdateFileInfo();
			}

			$result['success'] = true;

			Craft::info('Finished preparing to update '.$handle.'.', __METHOD__);
			return $result;
		}
		catch (\Exception $e)
		{
			return ['success' => false, 'message' => $e->getMessage()];
		}
	}

	/**
	 * @param string $md5
	 *
	 * @return array
	 */
	public function processUpdateDownload($md5)
	{
		Craft::info('Starting to process the update download.', __METHOD__);

		try
		{
			$updater = new Updater();
			$result = $updater->processDownload($md5);
			$result['success'] = true;

			Craft::info('Finished processing the update download.', __METHOD__);
			return $result;
		}
		catch (\Exception $e)
		{
			return ['success' => false, 'message' => $e->getMessage()];
		}
	}

	/**
	 * @param string $uid
	 *
	 * @return array
	 */
	public function backupFiles($uid)
	{
		Craft::info('Starting to backup files that need to be updated.', __METHOD__);

		try
		{
			$updater = new Updater();
			$updater->backupFiles($uid);

			Craft::info('Finished backing up files.');
			return ['success' => true];
		}
		catch (\Exception $e)
		{
			return ['success' => false, 'message' => $e->getMessage()];
		}
	}

	/**
	 * @param string $uid
	 *
	 * @return array
	 */
	public function updateFiles($uid)
	{
		Craft::info('Starting to update files.', __METHOD__);

		try
		{
			$updater = new Updater();
			$updater->updateFiles($uid);

			Craft::info('Finished updating files.', __METHOD__);
			return ['success' => true];
		}
		catch (\Exception $e)
		{
			return ['success' => false, 'message' => $e->getMessage()];
		}
	}

	/**
	 * @return array
	 */
	public function backupDatabase()
	{
		Craft::info('Starting to backup database.', __METHOD__);

		try
		{
			$updater = new Updater();
			$result = $updater->backupDatabase();

			if (!$result)
			{
				Craft::info('Did not backup database because there were no migrations to run.', __METHOD__);
				return ['success' => true];
			}
			else
			{
				Craft::info('Finished backing up database.', __METHOD__);
				return ['success' => true, 'dbBackupPath' => $result];
			}
		}
		catch (\Exception $e)
		{
			return ['success' => false, 'message' => $e->getMessage()];
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
		Craft::info('Starting to update the database.', __METHOD__);

		try
		{
			$updater = new Updater();

			if ($handle == 'craft')
			{
				Craft::info('Craft wants to update the database.', __METHOD__);
				$updater->updateDatabase();
				Craft::info('Craft is done updating the database.', __METHOD__);
			}
			else
			{
				$plugin = Craft::$app->getPlugins()->getPlugin($handle);
				if ($plugin)
				{
					Craft::info('The plugin, '.$plugin->name.' wants to update the database.', __METHOD__);
					$updater->updateDatabase($plugin);
					Craft::info('The plugin, '.$plugin->name.' is done updating the database.', __METHOD__);
				}
				else
				{
					Craft::error('Cannot find a plugin with the handle '.$handle.' or it is not enabled, therefore it cannot update the database.', __METHOD__);
					throw new Exception(Craft::t('app', 'Cannot find an enabled plugin with the handle {handle}.', ['handle' => $handle]));
				}
			}

			return ['success' => true];
		}
		catch (\Exception $e)
		{
			return ['success' => false, 'message' => $e->getMessage()];
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
		Craft::info('Starting to clean up after the update.', __METHOD__);

		try
		{
			$updater = new Updater();
			$updater->cleanUp($uid, $handle);

			Craft::info('Finished cleaning up after the update.', __METHOD__);
		}
		catch (\Exception $e)
		{
			Craft::info('There was an error during cleanup, but we don\'t really care: '.$e->getMessage(), __METHOD__);
		}

		// Fire an 'afterUpdate' event
		$this->trigger(static::EVENT_AFTER_UPDATE, new Event());
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
			// Fire an 'afterUpdateFail' event
			$this->trigger(static::EVENT_AFTER_UPDATE_FAIL, new Event());

			Craft::$app->getConfig()->maxPowerCaptain();

			if ($dbBackupPath && Craft::$app->getConfig()->get('backupDbOnUpdate') && Craft::$app->getConfig()->get('restoreDbOnUpdateFailure'))
			{
				Craft::info('Rolling back any database changes.', __METHOD__);
				UpdateHelper::rollBackDatabaseChanges($dbBackupPath);
				Craft::info('Done rolling back any database changes.', __METHOD__);
			}

			// If uid !== false, it's an auto-update.
			if ($uid !== false)
			{
				Craft::info('Rolling back any file changes.', __METHOD__);
				$manifestData = UpdateHelper::getManifestData(UpdateHelper::getUnzipFolderFromUID($uid));

				if ($manifestData)
				{
					UpdateHelper::rollBackFileChanges($manifestData);
				}

				Craft::info('Done rolling back any file changes.', __METHOD__);
			}

			Craft::info('Finished rolling back changes.', __METHOD__);

			Craft::info('Taking the site out of maintenance mode.', __METHOD__);
			Craft::$app->disableMaintenanceMode();

			return ['success' => true];
		}
		catch (\Exception $e)
		{
			return ['success' => false, 'message' => $e->getMessage()];
		}
	}

	/**
	 * Returns if a plugin needs to run a database update or not.
	 *
	 * @return bool
	 */
	public function isPluginDbUpdateNeeded()
	{
		$plugins = Craft::$app->getPlugins()->getAllPlugins();

		foreach ($plugins as $plugin)
		{
			if (Craft::$app->getPlugins()->doesPluginRequireDatabaseUpdate($plugin))
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
		$storedBuild = Craft::$app->getInfo('build');
		return (Craft::$app->build != $storedBuild);
	}

	/**
	 * Returns true is the build stored in craft_info is less than the minimum required build on the file system. This
	 * effectively makes sure that a user cannot manually update past a manual breakpoint.
	 *
	 * @return bool
	 */
	public function isBreakpointUpdateNeeded()
	{
		$storedBuild = Craft::$app->getInfo('build');
		return (Craft::$app->minBuildRequired > $storedBuild);
	}

	/**
	 * Returns whether the uploaded DB schema is equal to or greater than the installed schema
	 *
	 * @return bool
	 */
	public function isSchemaVersionCompatible()
	{
		$storedSchemaVersion = Craft::$app->getInfo('schemaVersion');
		return version_compare(Craft::$app->schemaVersion, $storedSchemaVersion, '>=');
	}

	/**
	 * Returns whether Craft needs to run any database migrations.
	 *
	 * @return bool
	 */
	public function isCraftDbMigrationNeeded()
	{
		if ($this->_isCraftDbMigrationNeeded === null)
		{
			$storedSchemaVersion = Craft::$app->getInfo('schemaVersion');
			$this->_isCraftDbMigrationNeeded = version_compare(Craft::$app->schemaVersion, $storedSchemaVersion, '>');
		}

		return $this->_isCraftDbMigrationNeeded;
	}

	/**
	 * Updates the Craft version info in the craft_info table.
	 *
	 * @return bool
	 */
	public function updateCraftVersionInfo()
	{
		$info = Craft::$app->getInfo();
		$info->version       = Craft::$app->version;
		$info->build         = Craft::$app->build;
		$info->schemaVersion = Craft::$app->schemaVersion;
		$info->track         = Craft::$app->track;
		$info->releaseDate   = Craft::$app->releaseDate;

		return Craft::$app->saveInfo($info);
	}

	/**
	 * Returns a list of plugins that are in need of a database update.
	 *
	 * @return PluginInterface[]|Plugin[]|null
	 */
	public function getPluginsThatNeedDbUpdate()
	{
		$pluginsThatNeedDbUpdate = [];

		$plugins = Craft::$app->getPlugins()->getAllPlugins();

		foreach ($plugins as $plugin)
		{
			if (Craft::$app->getPlugins()->doesPluginRequireDatabaseUpdate($plugin))
			{
				$pluginsThatNeedDbUpdate[] = $plugin;
			}
		}

		return $pluginsThatNeedDbUpdate;
	}
}
