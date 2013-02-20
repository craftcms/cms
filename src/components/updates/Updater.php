<?php
namespace Blocks;

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
		$updateModel = blx()->updates->getUpdates(true);

		if (!empty($updateModel->errors))
		{
			throw new Exception(implode(',', $updateModel->errors));
		}

		if ($updateModel->blocks->releases == null)
		{
			throw new Exception(Blocks::t('Blocks is already up to date.'));
		}
	}

	/**
	 * Performs environmental requirement checks before running an update.
	 *
	 * @throws Exception
	 */
	public function checkRequirements()
	{
		$installedMySqlVersion = blx()->db->serverVersion;
		$requiredMySqlVersion = blx()->params['requiredMysqlVersion'];
		$requiredPhpVersion = blx()->params['requiredPhpVersion'];

		$phpCompat = version_compare(PHP_VERSION, $requiredPhpVersion, '>=');
		$databaseCompat = version_compare($installedMySqlVersion, $requiredMySqlVersion, '>=');

		if (!$phpCompat && !$databaseCompat)
		{
			throw new Exception(Blocks::t('The update can’t be installed because Blocks requires PHP version "{requiredPhpVersion}" or higher and MySQL version "{requiredMySqlVersion}" or higher.  You have PHP version "{installedPhpVersion}" and MySQL version "{installedMySqlVersion}" installed.',
				array('requiredPhpVersion' => $requiredMySqlVersion,
				      'installedPhpVersion' => PHP_VERSION,
				      'requiredMySqlVersion' => $requiredMySqlVersion,
				      'installedMySqlVersion' => $installedMySqlVersion
				)));
		}
		else if (!$phpCompat)
		{
			throw new Exception(Blocks::t('The update can’t be installed because Blocks requires PHP version "{requiredPhpVersion}" or higher and you have PHP version "{installedPhpVersion}" installed.',
				array('requiredPhpVersion' => $requiredMySqlVersion,
				      'installedPhpVersion' => PHP_VERSION
			)));
		}
		else if (!$databaseCompat)
		{
			throw new Exception(Blocks::t('The update can’t be installed because Blocks requires MySQL version "{requiredMySqlVersion}" or higher and you have MySQL version "{installedMySqlVersion}" installed.',
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
		Blocks::log ('Starting to process the update download.');
		$tempPath = blx()->path->getTempPath();

		// Download the package from ET.
		Blocks::log('Downloading patch file to '.$tempPath);
		if (($fileName = blx()->et->downloadUpdate($tempPath)) !== false)
		{
			$downloadFilePath = $tempPath.$fileName;
		}
		else
		{
			throw new Exception(Blocks::t('There was a problem downloading the package.'));
		}

		$uid = IOHelper::getFileName($fileName, false);

		// Validate the downloaded update against ET.
		Blocks::log('Validating downloaded update.');
		if (!$this->_validateUpdate($downloadFilePath))
		{
			throw new Exception(Blocks::t('There was a problem validating the downloaded package.'));
		}

		// Unpack the downloaded package.
		Blocks::log('Unpacking the downloaded package.');
		$unzipFolder = blx()->path->getTempPath().IOHelper::getFileName($downloadFilePath, false);

		if (!$this->_unpackPackage($downloadFilePath, $unzipFolder))
		{
			throw new Exception(Blocks::t('There was a problem unpacking the downloaded package.'));
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

		// Validate that the paths in the update manifest file are all writable by Blocks
		Blocks::log('Validating update manifest file paths are writable.');
		$writableErrors = $this->_validateManifestPathsWritable($unzipFolder);
		if (count($writableErrors) > 0)
		{
			throw new Exception(Blocks::t('Blocks needs to be able to write to the follow paths, but can’t: {files}', array('files' => implode('<br />',  $writableErrors))));
		}

		// Backup any files about to be updated.
		Blocks::log('Backing up files that are about to be updated.');
		if (!$this->_backupFiles($unzipFolder))
		{
			throw new Exception(Blocks::t('There was a problem backing up your files for the update.'));
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
		Blocks::log('Putting the site into maintenance mode.');
		blx()->updates->enableMaintenanceMode();

		// Update the files.
		Blocks::log('Performing file update.');
		if (!UpdateHelper::doFileUpdate(UpdateHelper::getManifestData($unzipFolder), $unzipFolder))
		{
			Blocks::log('Taking the site out of maintenance mode.');
			blx()->updates->disableMaintenanceMode();

			throw new Exception(Blocks::t('There was a problem updating your files.'));
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
			Blocks::log('Starting to backup database.');
			if (($dbBackupPath = blx()->db->backup()) === false)
			{
				// If uid !== false, then it's an auto update.
				if ($uid !== false)
				{
					UpdateHelper::rollBackFileChanges(UpdateHelper::getManifestData(UpdateHelper::getUnzipFolderFromUID($uid)));
				}

				Blocks::log('Taking the site out of maintenance mode.');
				blx()->updates->disableMaintenanceMode();

				throw new Exception(Blocks::t('There was a problem backing up your database.'));
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

			Blocks::log('Taking the site out of maintenance mode.');
			blx()->updates->disableMaintenanceMode();

			throw new Exception(Blocks::t('There was a problem backing up your database.'));
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
			Blocks::log('Running migrations...');
			if (!blx()->migrations->runToTop($plugin))
			{
				Blocks::log('Something went wrong running a migration. :-(', \CLogger::LEVEL_ERROR);
				blx()->updates->rollbackUpdate($uid, $dbBackupPath);

				Blocks::log('Taking the site out of maintenance mode.');
				blx()->updates->disableMaintenanceMode();

				throw new Exception(Blocks::t('There was a problem updating your database.'));
			}
		}
		catch (\Exception $e)
		{
			blx()->updates->rollbackUpdate($uid, $dbBackupPath);

			Blocks::log('Taking the site out of maintenance mode.');
			blx()->updates->disableMaintenanceMode();

			throw new Exception(Blocks::t('There was a problem updating your database.'));
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
			Blocks::log('Cleaning up temp files after update.');
			$this->_cleanTempFiles($zipFile, $unzipFolder);
		}

		// Take the site out of maintenance mode.
		Blocks::log('Taking the site out of maintenance mode.');
		blx()->updates->disableMaintenanceMode();

		// Clear the updates cache.
		Blocks::log('Clearing the update cache.');
		if (!blx()->updates->flushUpdateInfoFromCache())
		{
			throw new Exception(Blocks::t('The update was performed successfully, but there was a problem invalidating the update cache.'));
		}

		if ($handle == 'blocks')
		{
			// Setting new Blocks info.
			Blocks::log('Settings new Blocks info in blx_info table.');
			if (!blx()->updates->setNewBlocksInfo(Blocks::getVersion(), Blocks::getBuild(), Blocks::getReleaseDate()))
			{
				throw new Exception(Blocks::t('The update was performed successfully, but there was a problem setting the new info in the database info table.'));
			}
		}
		else
		{
			$plugin = blx()->plugins->getPlugin($handle);

			if ($plugin)
			{
				if (!blx()->updates->setNewPluginInfo($plugin))
				{
					throw new Exception(Blocks::t('The update was performed successfully, but there was a problem setting the new info in the plugins table.'));
				}
			}
			else
			{
				throw new Exception(Blocks::t('The update was performed successfully, but there was a problem setting the new info in the plugins table.'));
			}
		}

		Blocks::log('Finished Updater.');
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
						$fullPath = IOHelper::normalizePathSeparators(rtrim(blx()->path->getAppPath(), '/').'.bak');
					}
					else
					{
						$fullPath = IOHelper::normalizePathSeparators(blx()->path->getAppPath().$tempFilePath.'.bak');
					}

					break;
				}

				// If the file/folder was set to be deleted, there is no backup and we go ahead and remove it now.
				case PatchManifestFileAction::Remove:
				{
					if ($tempFilePath == '')
					{
						$fullPath = IOHelper::normalizePathSeparators(blx()->path->getAppPath());
					}
					else
					{
						$fullPath = IOHelper::normalizePathSeparators(blx()->path->getAppPath().$tempFilePath.'.bak');
					}

					break;
				}
			}

			// Delete any files/folders we backed up.
			if ($folder)
			{
				if (($folder = IOHelper::getFolder($fullPath)) !== false)
				{
					Blocks::log('Deleting folder: '.$folder->getRealPath());
					$folder->delete();
				}
			}
			else
			{
				if (($file = IOHelper::getFile($fullPath)) !== false)
				{
					Blocks::log('Deleting file: '.$file->getRealPath());
					$file->delete();
				}
			}

			break;
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
		Blocks::log('Validating MD5 for '.$downloadFilePath);
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
		Blocks::log('Unzipping package to '.$unzipFolder);
		if (Zip::unzip($downloadFilePath, $unzipFolder))
		{
			return true;
		}

		return false;
	}

	/**
	 * Checks to see if the files that we are about to update are writable by Blocks.
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
			$filePath = IOHelper::normalizePathSeparators(blx()->path->getAppPath().$rowData[0]);

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
				$filePath = IOHelper::normalizePathSeparators(blx()->path->getAppPath().$rowData[0]);

				// It's a folder
				if (UpdateHelper::isManifestLineAFolder($filePath))
				{
					$folderPath = UpdateHelper::cleanManifestFolderLine($filePath);
					if (IOHelper::folderExists($folderPath))
					{
						Blocks::log('Backing up folder '.$folderPath);
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
						Blocks::log('Backing up file '.$filePath);
						IOHelper::copyFile($filePath, $filePath.'.bak');
					}
				}
			}
		}
		catch (\Exception $e)
		{
			Blocks::log('Error updating files: '.$e->getMessage(), \CLogger::LEVEL_ERROR);
			UpdateHelper::rollBackFileChanges($manifestData);
			return false;
		}

		return true;
	}
}
