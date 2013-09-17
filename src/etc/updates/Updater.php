<?php
namespace Craft;

/**
 *
 */
class Updater
{
	/**
	 *
	 */
	function __construct()
	{
		craft()->config->maxPowerCaptain();
	}

	/**
	 * @throws Exception
	 */
	public function getLatestUpdateInfo()
	{
		$updateModel = craft()->updates->getUpdates(true);

		if (!empty($updateModel->errors))
		{
			throw new Exception(implode(',', $updateModel->errors));
		}

		if ($updateModel->app->releases == null)
		{
			throw new Exception(Craft::t('@@@appName@@@ is already up to date.'));
		}
	}

	/**
	 * @return array
	 */
	public function getUpdateFileInfo()
	{
		$md5 = craft()->et->getUpdateFileInfo();
		return array('md5' => $md5);
	}

	/**
	 * Performs environmental requirement checks before running an update.
	 *
	 * @throws Exception
	 */
	public function checkRequirements()
	{
		craft()->runController('templates/requirementscheck');
	}

	/**
	 * @param $md5
	 * @throws Exception
	 * @return array
	 */
	public function processDownload($md5)
	{
		Craft::log('Starting to process the update download.', LogLevel::Info, true);
		$tempPath = craft()->path->getTempPath();

		// Download the package from ET.
		Craft::log('Downloading patch file to '.$tempPath, LogLevel::Info, true);
		if (($fileName = craft()->et->downloadUpdate($tempPath, $md5)) !== false)
		{
			$downloadFilePath = $tempPath.$fileName;
		}
		else
		{
			throw new Exception(Craft::t('There was a problem downloading the package.'));
		}

		$uid = StringHelper::UUID();

		// Validate the downloaded update against ET.
		Craft::log('Validating downloaded update.', LogLevel::Info, true);
		if (!$this->_validateUpdate($downloadFilePath, $md5))
		{
			throw new Exception(Craft::t('There was a problem validating the downloaded package.'));
		}

		// Unpack the downloaded package.
		Craft::log('Unpacking the downloaded package.', LogLevel::Info, true);
		$unzipFolder = craft()->path->getTempPath().$uid;

		if (!$this->_unpackPackage($downloadFilePath, $unzipFolder))
		{
			throw new Exception(Craft::t('There was a problem unpacking the downloaded package.'));
		}

		return array('uid' => $uid);
	}

	/**
	 * @param $uid
	 * @throws Exception
	 */
	public function backupFiles($uid)
	{
		$unzipFolder = UpdateHelper::getUnzipFolderFromUID($uid);

		// Validate that the paths in the update manifest file are all writable by Craft
		Craft::log('Validating update manifest file paths are writable.', LogLevel::Info, true);
		$writableErrors = $this->_validateManifestPathsWritable($unzipFolder);
		if (count($writableErrors) > 0)
		{
			throw new Exception(Craft::t('@@@appName@@@ needs to be able to write to the follow paths, but can’t:<br /> {files}', array('files' => implode('<br />',  $writableErrors))));
		}

		// Backup any files about to be updated.
		Craft::log('Backing up files that are about to be updated.', LogLevel::Info, true);
		if (!$this->_backupFiles($unzipFolder))
		{
			throw new Exception(Craft::t('There was a problem backing up your files for the update.'));
		}
	}

	/**
	 * @param $uid
	 * @throws Exception
	 */
	public function updateFiles($uid)
	{
		$unzipFolder = UpdateHelper::getUnzipFolderFromUID($uid);

		// Put the site into maintenance mode.
		Craft::log('Putting the site into maintenance mode.', LogLevel::Info, true);
		Craft::enableMaintenanceMode();

		// Update the files.
		Craft::log('Performing file update.', LogLevel::Info, true);
		if (!UpdateHelper::doFileUpdate(UpdateHelper::getManifestData($unzipFolder), $unzipFolder))
		{
			Craft::log('Taking the site out of maintenance mode.', LogLevel::Info, true);
			Craft::disableMaintenanceMode();

			throw new Exception(Craft::t('There was a problem updating your files.'));
		}
	}

	/**
	 * @param $uid
	 * @return bool
	 * @throws Exception
	 */
	public function backupDatabase($uid)
	{
		try
		{
			Craft::log('Starting to backup database.', LogLevel::Info, true);
			if (($dbBackupPath = craft()->db->backup()) === false)
			{
				// If uid !== false, then it's an auto update.
				if ($uid !== false)
				{
					UpdateHelper::rollBackFileChanges(UpdateHelper::getManifestData(UpdateHelper::getUnzipFolderFromUID($uid)));
				}

				Craft::log('Taking the site out of maintenance mode.', LogLevel::Info, true);
				Craft::disableMaintenanceMode();

				throw new Exception(Craft::t('There was a problem backing up your database.'));
			}
			else
			{
				return IOHelper::getFileName($dbBackupPath, false);
			}
		}
		catch (\Exception $e)
		{
			// If uid !== false, then it's an auto update.
			if ($uid !== false)
			{
				UpdateHelper::rollBackFileChanges(UpdateHelper::getManifestData(UpdateHelper::getUnzipFolderFromUID($uid)));
			}

			Craft::log('Taking the site out of maintenance mode.', LogLevel::Info, true);
			Craft::disableMaintenanceMode();

			throw new Exception(Craft::t('There was a problem backing up your database.'));
		}
	}

	/**
	 * @param      $uid
	 * @param bool $dbBackupPath
	 * @param null $plugin
	 * @throws Exception
	 */
	public function updateDatabase($uid, $dbBackupPath = false, $plugin = null)
	{
		try
		{
			Craft::log('Running migrations...', LogLevel::Info, true);
			if (!craft()->migrations->runToTop($plugin))
			{
				Craft::log('Something went wrong running a migration. :-(', LogLevel::Error);
				craft()->updates->rollbackUpdate($uid, $dbBackupPath);

				Craft::log('Taking the site out of maintenance mode.', LogLevel::Info, true);
				Craft::disableMaintenanceMode();

				throw new Exception(Craft::t('There was a problem updating your database.'));
			}

			// If plugin is null we're looking at Craft.
			if ($plugin === null)
			{
				// Setting new Craft info.
				Craft::log('Settings new Craft release info in craft_info table.', LogLevel::Info, true);
				if (!craft()->updates->setNewCraftInfo(CRAFT_VERSION, CRAFT_BUILD, CRAFT_RELEASE_DATE))
				{
					throw new Exception(Craft::t('The update was performed successfully, but there was a problem setting the new info in the database info table.'));
				}
			}
			else
			{
				if (!craft()->updates->setNewPluginInfo($plugin))
				{
					throw new Exception(Craft::t('The update was performed successfully, but there was a problem setting the new info in the plugins table.'));
				}
			}

			// Take the site out of maintenance mode.
			Craft::log('Taking the site out of maintenance mode.', LogLevel::Info, true);
			Craft::disableMaintenanceMode();
		}
		catch (\Exception $e)
		{
			craft()->updates->rollbackUpdate($uid, $dbBackupPath);

			Craft::log('Taking the site out of maintenance mode.', LogLevel::Info, true);
			Craft::disableMaintenanceMode();

			throw new Exception(Craft::t('There was a problem updating your database.'));
		}
	}

	/**
	 * @param $uid
	 * @throws Exception
	 * @return bool
	 */
	public function cleanUp($uid)
	{
		// If uid !== false, then it's an auto-update.
		if ($uid !== false)
		{
			// Clean-up any leftover files.
			Craft::log('Cleaning up temp files after update.', LogLevel::Info, true);
			$this->_cleanTempFiles();
		}

		// Clear the updates cache.
		Craft::log('Clearing the update cache.', LogLevel::Info, true);
		if (!craft()->updates->flushUpdateInfoFromCache())
		{
			throw new Exception(Craft::t('The update was performed successfully, but there was a problem invalidating the update cache.'));
		}

		Craft::log('Finished Updater.', LogLevel::Info, true);
		return true;
	}

	/**
	 * Remove any temp files and/or folders that might have been created.
	 */
	private function _cleanTempFiles()
	{
		$appPath = craft()->path->getAppPath();

		// Get rid of all the .bak files/folders.
		$baks = IOHelper::getFolderContents($appPath, true, ".*\.bak$");

		foreach ($baks as $bak)
		{
			if (IOHelper::fileExists($bak))
			{
				if (IOHelper::isWritable($bak))
				{
					Craft::log('Deleting file: '.$bak, LogLevel::Info, true);
					IOHelper::deleteFile($bak, true);
				}
			}
			else
			{
				if (IOHelper::folderExists($bak))
				{
					if (IOHelper::isWritable($bak))
					{
						Craft::log('Deleting .bak folder:'.$bak, LogLevel::Info, true);
						IOHelper::clearFolder($bak, true);
						IOHelper::deleteFolder($bak, true);
					}
				}
			}
		}

		// Clear the temp folder.
		IOHelper::clearFolder(craft()->path->getTempPath(), true);
	}

	/**
	 * Validates that the downloaded file MD5 the MD5 of the file from Elliott
	 *
	 * @access private
	 * @param $downloadFilePath
	 * @param $sourceMD5
	 * @return bool
	 */
	private function _validateUpdate($downloadFilePath, $sourceMD5)
	{
		Craft::log('Validating MD5 for '.$downloadFilePath, LogLevel::Info, true);
		$localMD5 = IOHelper::getFileMD5($downloadFilePath);

		if ($localMD5 === $sourceMD5)
		{
			return true;
		}

		return false;
	}

	/**
	 * Unzip the downloaded update file into the temp package folder.
	 *
	 * @access private
	 *
	 * @param $downloadFilePath
	 * @param $unzipFolder
	 *
	 * @return bool
	 */
	private function _unpackPackage($downloadFilePath, $unzipFolder)
	{
		Craft::log('Unzipping package to '.$unzipFolder, LogLevel::Info, true);
		if (Zip::unzip($downloadFilePath, $unzipFolder))
		{
			return true;
		}

		return false;
	}

	/**
	 * Checks to see if the files that we are about to update are writable by Craft.
	 *
	 * @access private
	 * @param $unzipFolder
	 * @return bool
	 */
	private function _validateManifestPathsWritable($unzipFolder)
	{
		$manifestData = UpdateHelper::getManifestData($unzipFolder);
		$writableErrors = array();

		foreach ($manifestData as $row)
		{
			if (UpdateHelper::isManifestVersionInfoLine($row))
			{
				continue;
			}

			$rowData = explode(';', $row);
			$filePath = IOHelper::normalizePathSeparators(craft()->path->getAppPath().$rowData[0]);

			if (UpdateHelper::isManifestLineAFolder($filePath))
			{
				$filePath = UpdateHelper::cleanManifestFolderLine($filePath);
			}

			// Check to see if the file/folder we need to update is writable.
			if (IOHelper::fileExists($filePath) || IOHelper::folderExists($filePath))
			{
				if (!IOHelper::isWritable($filePath))
				{
					$writableErrors[] = $filePath;
				}
			}
			// In this case, it's an 'added' update file.
			else if (($folderPath = IOHelper::folderExists(IOHelper::getFolderName($filePath))) == true)
			{
				if (!IOHelper::isWritable($folderPath))
				{
					$writableErrors[] = $filePath;
				}

			}
		}

		return $writableErrors;
	}

	/**
	 * Attempt to backup each of the update manifest files by copying them to a file with the same name with a .bak extension.
	 * If there is an exception thrown, we attempt to roll back all of the changes.
	 *
	 * @access private
	 * @param $unzipFolder
	 * @return bool
	 */
	private function _backupFiles($unzipFolder)
	{
		$manifestData = UpdateHelper::getManifestData($unzipFolder);

		try
		{
			foreach ($manifestData as $row)
			{
				if (UpdateHelper::isManifestVersionInfoLine($row))
				{
					continue;
				}

				// No need to back up migration files.
				if (UpdateHelper::isManifestMigrationLine($row))
				{
					continue;
				}

				$rowData = explode(';', $row);
				$filePath = IOHelper::normalizePathSeparators(craft()->path->getAppPath().$rowData[0]);

				// It's a folder
				if (UpdateHelper::isManifestLineAFolder($filePath))
				{
					$folderPath = UpdateHelper::cleanManifestFolderLine($filePath);
					if (IOHelper::folderExists($folderPath))
					{
						Craft::log('Backing up folder '.$folderPath, LogLevel::Info, true);
						IOHelper::createFolder($folderPath.'.bak');
						IOHelper::copyFolder($folderPath.'/', $folderPath.'.bak/');
					}
				}
				// It's a file.
				else
				{
					// If the file doesn't exist, it's probably a new file.
					if (IOHelper::fileExists($filePath))
					{
						Craft::log('Backing up file '.$filePath, LogLevel::Info, true);
						IOHelper::copyFile($filePath, $filePath.'.bak');
					}
				}
			}
		}
		catch (\Exception $e)
		{
			Craft::log('Error updating files: '.$e->getMessage(), LogLevel::Error);
			UpdateHelper::rollBackFileChanges($manifestData);
			return false;
		}

		return true;
	}
}
