<?php
namespace Craft;

/**
 * Class UpdatesService
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://craftcms.com/license Craft License Agreement
 * @see       http://craftcms.com
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
			if ($plugin->status == PluginUpdateStatus::UpdateAvailable && count($plugin->releases) > 0)
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
						if ($plugin->status == PluginUpdateStatus::UpdateAvailable)
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
		if (!empty($this->_updateModel->app->criticalUpdateAvailable))
		{
			return true;
		}

		foreach ($this->_updateModel->plugins as $pluginUpdateModel)
		{
			if ($pluginUpdateModel->criticalUpdateAvailable)
			{
				return true;
			}
		}

		return false;
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

					/*
					$updateModel->errors = null;
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

					$this->checkPluginReleaseFeeds($updateModel);
					*/
				}
				else
				{
					$updateModel = $etModel->data;

					// Search for any missing plugin updates based on their feeds
					$this->checkPluginReleaseFeeds($updateModel);

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
			'version' => $plugin->getVersion(),
			'schemaVersion' => $plugin->getSchemaVersion()
		), array(
			'class' => $plugin->getClassHandle()
		));

		$success = (bool) $affectedRows;

		return $success;
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
	 * Check plugins’ release feeds and include any pending updates in the given UpdateModel
	 *
	 * @param UpdateModel $updateModel
	 */
	public function checkPluginReleaseFeeds(UpdateModel $updateModel)
	{
		$userAgent = 'Craft/'.craft()->getVersion().'.'.craft()->getBuild();

		foreach ($updateModel->plugins as $pluginUpdateModel)
		{
			// Only check plugins where the update status isn't already known from the ET response
			if ($pluginUpdateModel->status != PluginUpdateStatus::Unknown)
			{
				continue;
			}

			// Get the plugin and its feed URL
			$plugin = craft()->plugins->getPlugin($pluginUpdateModel->class);
			$feedUrl = $plugin->getReleaseFeedUrl();

			// Skip if the plugin doesn't have a feed URL
			if ($feedUrl === null)
			{
				continue;
			}

			// Make sure it's HTTPS
			if (strncmp($feedUrl, 'https://', 8) !== 0)
			{
				Craft::log('The “'.$plugin->getName().'” plugin has a release feed URL, but it doesn’t begin with https://, so it’s getting skipped ('.$feedUrl.').', LogLevel::Warning);
				continue;
			}

			try
			{
				// Fetch it
				$client = new \Guzzle\Http\Client();
				$client->setUserAgent($userAgent, true);

				$options = array(
					'timeout'         => 5,
					'connect_timeout' => 2,
					'allow_redirects' => true,
					'verify'          => false
				);

				$request = $client->get($feedUrl, null, $options);

				// Potentially long-running request, so close session to prevent session blocking on subsequent requests.
				craft()->session->close();

				$response = $request->send();

				if (!$response->isSuccessful())
				{
					Craft::log('Error in calling '.$feedUrl.'. Response: '.$response->getBody(), LogLevel::Warning);
					continue;
				}

				$responseBody = $response->getBody();
				$releases = JsonHelper::decode($responseBody);

				if (!$releases)
				{
					Craft::log('The “'.$plugin->getName()."” plugin release feed didn’t come back as valid JSON:\n".$responseBody, LogLevel::Warning);
					continue;
				}

				$releaseModels = array();
				$releaseTimestamps = array();

				foreach ($releases as $release)
				{
					// Validate ite info
					$errors = array();

					// Any missing required attributes?
					$missingAttributes = array();

					foreach (array('version', 'downloadUrl', 'date', 'notes') as $attribute)
					{
						if (empty($release[$attribute]))
						{
							$missingAttributes[] = $attribute;
						}
					}

					if ($missingAttributes)
					{
						$errors[] = 'Missing required attributes ('.implode(', ', $missingAttributes).')';
					}

					// downloadUrl could be missing.
					if (!empty($release['downloadUrl']))
					{
						// Invalid URL?
						if (strncmp($release['downloadUrl'], 'https://', 8) !== 0)
						{
							$errors[] = 'Download URL doesn’t begin with https:// ('.$release['downloadUrl'].')';
						}
					}

					// release date could be missing.
					if (!empty($release['date']))
					{
						// Invalid date?
						$date = DateTime::createFromString($release['date']);
						if (!$date)
						{
							$errors[] = 'Invalid date ('.$release['date'].')';
						}
					}

					// Validation complete. Were there any errors?
					if ($errors)
					{
						Craft::log('A “'.$plugin->getName()."” release was skipped because it is invalid:\n - ".implode("\n - ", $errors), LogLevel::Warning);
						continue;
					}

					// All good! Let's make sure it's a pending update
					if (!version_compare($release['version'], $plugin->getVersion(), '>'))
					{
						continue;
					}

					// Create the release note HTML
					if (!is_array($release['notes']))
					{
						$release['notes'] = array_filter(preg_split('/[\r\n]+/', $release['notes']));
					}

					$notes = '';
					$inList = false;

					foreach ($release['notes'] as $line)
					{
						// Escape any HTML
						$line = htmlspecialchars($line, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

						// Is this a heading?
						if (preg_match('/^#\s+(.+)/', $line, $match))
						{
							if ($inList)
							{
								$notes .= "</ul>\n";
								$inList = false;
							}

							$notes .= '<h3>'.$match[1]."</h3>\n";
						}
						else
						{
							if (!$inList)
							{
								$notes .= "<ul>\n";
								$inList = true;
							}

							if (preg_match('/^\[(\w+)\]\s+(.+)/', $line, $match))
							{
								$class = strtolower($match[1]);
								$line = $match[2];
							}
							else
							{
								$class = null;
							}

							// Parse Markdown code
							$line = StringHelper::parseMarkdownLine($line);

							$notes .= '<li'.($class ? ' class="'.$class.'"' : '').'>'.$line."</li>\n";
						}
					}

					if ($inList)
					{
						$notes .= "</ul>\n";
					}

					$critical = !empty($release['critical']);

					// Populate the release model
					$releaseModel = new PluginNewReleaseModel();
					$releaseModel->version = $release['version'];
					$releaseModel->date = $date;
					$releaseModel->localizedDate = $date->localeDate();
					$releaseModel->notes = $notes;
					$releaseModel->critical = $critical;
					$releaseModel->manualDownloadEndpoint = $release['downloadUrl'];

					$releaseModels[] = $releaseModel;
					$releaseTimestamps[] = $date->getTimestamp();

					if ($critical)
					{
						$pluginUpdateModel->criticalUpdateAvailable = true;
					}
				}

				if ($releaseModels)
				{
					// Sort release models by timestamp
					array_multisort($releaseTimestamps, SORT_DESC, $releaseModels);
					$latestRelease = $releaseModels[0];

					$pluginUpdateModel->displayName = $plugin->getName();
					$pluginUpdateModel->localVersion = $plugin->getVersion();
					$pluginUpdateModel->latestDate = $latestRelease->date;
					$pluginUpdateModel->latestVersion = $latestRelease->version;
					$pluginUpdateModel->manualDownloadEndpoint = $latestRelease->manualDownloadEndpoint;
					$pluginUpdateModel->manualUpdateRequired = true;
					$pluginUpdateModel->releases = $releaseModels;
					$pluginUpdateModel->status = PluginUpdateStatus::UpdateAvailable;
				}
				else
				{
					$pluginUpdateModel->status = PluginUpdateStatus::UpToDate;
				}
			}
			catch (\Exception $e)
			{
				Craft::log('There was a problem getting the update feed for “'.$plugin->getName().'”, so it was skipped: '.$e->getMessage(), LogLevel::Error);
				continue;
			}
		}
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

				if ($handle == 'craft')
				{
					Craft::log('Updating from '.$updateModel->app->localVersion.'.'.$updateModel->app->localBuild.' to '.$updateModel->app->latestVersion.'.'.$updateModel->app->latestBuild.'.', LogLevel::Info, true);
				}
				else
				{
					$latestVersion = null;
					$localVersion = null;
					$class = null;

					foreach ($updateModel->plugins as $pluginUpdateModel)
					{
						if (strtolower($pluginUpdateModel->class) === $handle)
						{
							$latestVersion = $pluginUpdateModel->latestVersion;
							$localVersion = $pluginUpdateModel->localVersion;
							$class = $pluginUpdateModel->class;

							break;
						}
					}

					Craft::log('Updating plugin "'.$class.'" from '.$localVersion.' to '.$latestVersion.'.', LogLevel::Info, true);
				}

				$result = $updater->getUpdateFileInfo($handle);

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
	 * @param string $handle
	 *
	 * @return array
	 */
	public function processUpdateDownload($md5, $handle)
	{
		Craft::log('Starting to process the update download.', LogLevel::Info, true);

		try
		{
			$updater = new Updater();
			$result = $updater->processDownload($md5, $handle);
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
	 * @param string $handle
	 *
	 * @return array
	 */
	public function backupFiles($uid, $handle)
	{
		Craft::log('Starting to backup files that need to be updated.', LogLevel::Info, true);

		try
		{
			$updater = new Updater();
			$updater->backupFiles($uid, $handle);

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
	 * @param string $handle
	 *
	 * @return array
	 */
	public function updateFiles($uid, $handle)
	{
		Craft::log('Starting to update files.', LogLevel::Info, true);

		try
		{
			$updater = new Updater();
			$updater->updateFiles($uid, $handle);

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
				// Make sure plugins are loaded.
				craft()->plugins->loadPlugins();

				$plugin = craft()->plugins->getPlugin($handle);

				if ($plugin)
				{
					Craft::log('The plugin "'.$plugin->getName().'" wants to update the database.', LogLevel::Info, true);
					$updater->updateDatabase($plugin);
					Craft::log('The plugin "'.$plugin->getName().'" is done updating the database.', LogLevel::Info, true);
				}
				else
				{
					Craft::log('Cannot find a plugin with the handle "'.$handle.'" or it is not enabled, therefore it cannot update the database.', LogLevel::Error);
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
	 * @param string $handle
	 * @param bool   $dbBackupPath
	 *
	 * @return array
	 */
	public function rollbackUpdate($uid, $handle, $dbBackupPath = false)
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
				$manifestData = UpdateHelper::getManifestData(UpdateHelper::getUnzipFolderFromUID($uid), $handle);

				if ($manifestData)
				{
					UpdateHelper::rollBackFileChanges($manifestData, $handle);
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
