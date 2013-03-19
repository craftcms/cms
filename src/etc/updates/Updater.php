<?php
namespace Craft;

/**
 *
 */
class Updater
{
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
	 * Performs environmental requirement checks before running an update.
	 *
	 * @throws Exception
	 */
	public function checkRequirements()
	{
		$installedMySqlVersion = craft()->db->serverVersion;
		$requiredMySqlVersion = craft()->params['requiredMysqlVersion'];
		$requiredPhpVersion = craft()->params['requiredPhpVersion'];

		$phpCompat = version_compare(PHP_VERSION, $requiredPhpVersion, '>=');
		$databaseCompat = version_compare($installedMySqlVersion, $requiredMySqlVersion, '>=');

		if (!$phpCompat && !$databaseCompat)
		{
			throw new Exception(Craft::t('The update can’t be installed because @@@appName@@@ requires PHP version "{requiredPhpVersion}" or higher and MySQL version "{requiredMySqlVersion}" or higher.  You have PHP version "{installedPhpVersion}" and MySQL version "{installedMySqlVersion}" installed.',
				array('requiredPhpVersion' => $requiredMySqlVersion,
				      'installedPhpVersion' => PHP_VERSION,
				      'requiredMySqlVersion' => $requiredMySqlVersion,
				      'installedMySqlVersion' => $installedMySqlVersion
				)));
		}
		else if (!$phpCompat)
		{
			throw new Exception(Craft::t('The update can’t be installed because @@@appName@@@ requires PHP version "{requiredPhpVersion}" or higher and you have PHP version "{installedPhpVersion}" installed.',
				array('requiredPhpVersion' => $requiredMySqlVersion,
				      'installedPhpVersion' => PHP_VERSION
			)));
		}
		else if (!$databaseCompat)
		{
			throw new Exception(Craft::t('The update can’t be installed because @@@appName@@@ requires MySQL version "{requiredMySqlVersion}" or higher and you have MySQL version "{installedMySqlVersion}" installed.',
				array('requiredMySqlVersion' => $requiredMySqlVersion,
				      'installedMySqlVersion' => $installedMySqlVersion
				)));
		}
	}

	/**
	 * @return array
	 * @throws Exception
	 */
	public function processDownload()
	{
		Craft::log ('Starting to process the update download.');
		$tempPath = craft()->path->getTempPath();

		// Download the package from ET.
		Craft::log('Downloading patch file to '.$tempPath);
		if (($fileName = craft()->et->downloadUpdate($tempPath)) !== false)
		{
			$downloadFilePath = $tempPath.$fileName;
		}
		else
		{
			throw new Exception(Craft::t('There was a problem downloading the package.'));
		}

		$uid = IOHelper::getFileName($fileName, false);

		// Validate the downloaded update against ET.
		Craft::log('Validating downloaded update.');
		if (!$this->_validateUpdate($downloadFilePath))
		{
			throw new Exception(Craft::t('There was a problem validating the downloaded package.'));
		}

		// Unpack the downloaded package.
		Craft::log('Unpacking the downloaded package.');
		$unzipFolder = craft()->path->getTempPath().IOHelper::getFileName($downloadFilePath, false);

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
		Craft::log('Validating update manifest file paths are writable.');
		$writableErrors = $this->_validateManifestPathsWritable($unzipFolder);
		if (count($writableErrors) > 0)
		{
			throw new Exception(Craft::t('@@@appName@@@ needs to be able to write to the follow paths, but can’t: {files}', array('files' => implode('<br />',  $writableErrors))));
		}

		// Backup any files about to be updated.
		Craft::log('Backing up files that are about to be updated.');
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
		Craft::log('Putting the site into maintenance mode.');
		Craft::enableMaintenanceMode();

		// Update the files.
		Craft::log('Performing file update.');
		if (!UpdateHelper::doFileUpdate(UpdateHelper::getManifestData($unzipFolder), $unzipFolder))
		{
			Craft::log('Taking the site out of maintenance mode.');
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
			Craft::log('Starting to backup database.');
			if (($dbBackupPath = craft()->db->backup()) === false)
			{
				// If uid !== false, then it's an auto update.
				if ($uid !== false)
				{
					UpdateHelper::rollBackFileChanges(UpdateHelper::getManifestData(UpdateHelper::getUnzipFolderFromUID($uid)));
				}

				Craft::log('Taking the site out of maintenance mode.');
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

			Craft::log('Taking the site out of maintenance mode.');
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
			Craft::log('Running migrations...');
			if (!craft()->migrations->runToTop($plugin))
			{
				Craft::log('Something went wrong running a migration. :-(', \CLogger::LEVEL_ERROR);
				craft()->updates->rollbackUpdate($uid, $dbBackupPath);

				Craft::log('Taking the site out of maintenance mode.');
				Craft::disableMaintenanceMode();

				throw new Exception(Craft::t('There was a problem updating your database.'));
			}
		}
		catch (\Exception $e)
		{
			craft()->updates->rollbackUpdate($uid, $dbBackupPath);

			Craft::log('Taking the site out of maintenance mode.');
			Craft::disableMaintenanceMode();

			throw new Exception(Craft::t('There was a problem updating your database.'));
		}
	}

	/**
	 * @param $uid
	 * @param $handle
	 * @throws Exception
	 * @return bool
	 */
	public function cleanUp($uid, $handle)
	{
		// If uid !== false, then it's an auto-update.
		if ($uid !== false)
		{
			$zipFile = UpdateHelper::getZipFileFromUID($uid);
			$unzipFolder = UpdateHelper::getUnzipFolderFromUID($uid);

			// Clean-up any leftover files.
			Craft::log('Cleaning up temp files after update.');
			$this->_cleanTempFiles($zipFile, $unzipFolder);
		}

		// Take the site out of maintenance mode.
		Craft::log('Taking the site out of maintenance mode.');
		Craft::disableMaintenanceMode();

		// Clear the updates cache.
		Craft::log('Clearing the update cache.');
		if (!craft()->updates->flushUpdateInfoFromCache())
		{
			throw new Exception(Craft::t('The update was performed successfully, but there was a problem invalidating the update cache.'));
		}

		if ($handle == 'craft')
		{
			// Setting new Craft info.
			Craft::log('Settings new Craft release info in craft_info table.');
			if (!craft()->updates->setNewCraftInfo(CRAFT_VERSION, CRAFT_BUILD, CRAFT_RELEASE_DATE))
			{
				throw new Exception(Craft::t('The update was performed successfully, but there was a problem setting the new info in the database info table.'));
			}
		}
		else
		{
			$plugin = craft()->plugins->getPlugin($handle);

			if ($plugin)
			{
				if (!craft()->updates->setNewPluginInfo($plugin))
				{
					throw new Exception(Craft::t('The update was performed successfully, but there was a problem setting the new info in the plugins table.'));
				}
			}
			else
			{
				throw new Exception(Craft::t('The update was performed successfully, but there was a problem setting the new info in the plugins table.'));
			}
		}

		Craft::log('Finished Updater.');
		return true;
	}

	/**
	 * Remove any temp files and/or folders that might have been created.
	 */
	private function _cleanTempFiles($zipFile, $unzipFolder)
	{
		$manifestData = UpdateHelper::getManifestData($unzipFolder);

		foreach ($manifestData as $row)
		{
			if (UpdateHelper::isManifestVersionInfoLine($row))
			{
				continue;
			}

			$rowData = explode(';', $row);

			$folder = false;
			if (UpdateHelper::isManifestLineAFolder($rowData[0]))
			{
				$folder = true;
				$tempFilePath = UpdateHelper::cleanManifestFolderLine($rowData[0]);
			}
			else
			{
				$tempFilePath = $rowData[0];
			}

			$fullPath = '';

			switch (trim($rowData[1]))
			{
				// If the file/folder was "added" in the manifest file, then it will have a backup we need to remove.
				case PatchManifestFileAction::Add:
				{
					if ($tempFilePath == '')
					{
						$fullPath = IOHelper::normalizePathSeparators(rtrim(craft()->path->getAppPath(), '/').'.bak');
					}
					else
					{
						$fullPath = IOHelper::normalizePathSeparators(craft()->path->getAppPath().$tempFilePath.'.bak');
					}

					break;
				}

				// If the file/folder was set to be deleted, there is no backup and we go ahead and remove it now.
				case PatchManifestFileAction::Remove:
				{
					if ($tempFilePath == '')
					{
						$fullPath = IOHelper::normalizePathSeparators(craft()->path->getAppPath());
					}
					else
					{
						$fullPath = IOHelper::normalizePathSeparators(craft()->path->getAppPath().$tempFilePath);
					}

					break;
				}
			}

			// Delete any files/folders we backed up.
			if ($folder)
			{
				if (($folder = IOHelper::getFolder($fullPath)) !== false)
				{
					Craft::log('Deleting folder: '.$folder->getRealPath());
					$folder->delete();
				}
			}
			else
			{
				if (($file = IOHelper::getFile($fullPath)) !== false)
				{
					Craft::log('Deleting file: '.$file->getRealPath());
					$file->delete();
				}
			}
		}

		// Delete the temp patch folder
		IOHelper::deleteFolder($unzipFolder);

		// Delete the downloaded patch file.
		IOHelper::deleteFile($zipFile);
	}

	/**
	 * Validates that the downloaded file MD5 matches the name of the file (which is the MD5 from the server)
	 *
	 * @access private
	 * @param $downloadFilePath
	 * @return bool
	 */
	private function _validateUpdate($downloadFilePath)
	{
		Craft::log('Validating MD5 for '.$downloadFilePath);
		$sourceMD5 = IOHelper::getFileName($downloadFilePath, false);

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
		Craft::log('Unzipping package to '.$unzipFolder);
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
						Craft::log('Backing up folder '.$folderPath);
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
						Craft::log('Backing up file '.$filePath);
						IOHelper::copyFile($filePath, $filePath.'.bak');
					}
				}
			}
		}
		catch (\Exception $e)
		{
			Craft::log('Error updating files: '.$e->getMessage(), \CLogger::LEVEL_ERROR);
			UpdateHelper::rollBackFileChanges($manifestData);
			return false;
		}

		return true;
	}
}
