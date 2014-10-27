<?php
namespace Craft;

/**
 * Class Updater
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://buildwithcraft.com/license Craft License Agreement
 * @see       http://buildwithcraft.com
 * @package   craft.app.etc.updates
 * @since     1.0
 */
class Updater
{
	// Public Methods
	// =========================================================================

	/**
	 * @return Updater
	 */
	public function __construct()
	{
		craft()->config->maxPowerCaptain();
	}

	/**
	 * @throws Exception
	 * @return null
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
	 * @return null
	 */
	public function checkRequirements()
	{
		craft()->runController('templates/requirementscheck');
	}

	/**
	 * @param string $md5
	 *
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

		Craft::log('Validating any new requirements from the patch file.');
		$errors = $this->_validateNewRequirements($unzipFolder);

		if (!empty($errors))
		{
			throw new Exception(Craft::t('Your server does not meet the following minimum requirements for @@@appName@@@ to run:')."\n\n".$this->_markdownList($errors));
		}

		// Validate that the paths in the update manifest file are all writable by Craft
		Craft::log('Validating update manifest file paths are writable.', LogLevel::Info, true);
		$writableErrors = $this->_validateManifestPathsWritable($unzipFolder);

		if (count($writableErrors) > 0)
		{
			throw new Exception(Craft::t('@@@appName@@@ needs to be able to write to the following paths, but can’t:')."\n\n".$this->_markdownList($writableErrors));
		}

		return array('uid' => $uid);
	}

	/**
	 * @param $uid
	 *
	 * @throws Exception
	 * @return null
	 */
	public function backupFiles($uid)
	{
		$unzipFolder = UpdateHelper::getUnzipFolderFromUID($uid);

		// Backup any files about to be updated.
		Craft::log('Backing up files that are about to be updated.', LogLevel::Info, true);
		if (!$this->_backupFiles($unzipFolder))
		{
			throw new Exception(Craft::t('There was a problem backing up your files for the update.'));
		}
	}

	/**
	 * @param $uid
	 *
	 * @throws Exception
	 * @return null
	 */
	public function updateFiles($uid)
	{
		$unzipFolder = UpdateHelper::getUnzipFolderFromUID($uid);

		// Put the site into maintenance mode.
		Craft::log('Putting the site into maintenance mode.', LogLevel::Info, true);
		craft()->enableMaintenanceMode();

		// Update the files.
		Craft::log('Performing file update.', LogLevel::Info, true);
		if (!UpdateHelper::doFileUpdate(UpdateHelper::getManifestData($unzipFolder), $unzipFolder))
		{
			throw new Exception(Craft::t('There was a problem updating your files.'));
		}
	}

	/**
	 * @throws Exception
	 * @return string
	 */
	public function backupDatabase()
	{
		Craft::log('Starting to backup database.', LogLevel::Info, true);
		if (($dbBackupPath = craft()->db->backup()) === false)
		{
			throw new Exception(Craft::t('There was a problem backing up your database.'));
		}
		else
		{
			return IOHelper::getFileName($dbBackupPath, false);
		}
	}

	/**
	 * @param BasePlugin|null $plugin
	 *
	 * @throws Exception
	 * @return null
	 */
	public function updateDatabase($plugin = null)
	{
		Craft::log('Running migrations...', LogLevel::Info, true);
		if (!craft()->migrations->runToTop($plugin))
		{
			throw new Exception(Craft::t('There was a problem updating your database.'));
		}

		// If plugin is null we're looking at Craft.
		if ($plugin === null)
		{
			// Setting new Craft info.
			Craft::log('Settings new Craft release info in craft_info table.', LogLevel::Info, true);
			if (!craft()->updates->updateCraftVersionInfo())
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
		craft()->disableMaintenanceMode();
	}

	/**
	 * @param $uid
	 *
	 * @throws Exception
	 * @return bool
	 */
	public function cleanUp($uid)
	{
		// Clear the updates cache.
		Craft::log('Clearing the update cache.', LogLevel::Info, true);
		if (!craft()->updates->flushUpdateInfoFromCache())
		{
			throw new Exception(Craft::t('The update was performed successfully, but there was a problem invalidating the update cache.'));
		}

		// If uid !== false, then it's an auto-update.
		if ($uid !== false)
		{
			$unzipFolder = UpdateHelper::getUnzipFolderFromUID($uid);

			// Clean-up any leftover files.
			Craft::log('Cleaning up temp files after update.', LogLevel::Info, true);
			$this->_cleanTempFiles($unzipFolder);
		}

		Craft::log('Finished Updater.', LogLevel::Info, true);
		return true;
	}

	// Private Methods
	// =========================================================================

	/**
	 * Remove any temp files and/or folders that might have been created.
	 *
	 * @param string $unzipFolder
	 *
	 * @return null
	 */
	private function _cleanTempFiles($unzipFolder)
	{
		$appPath = craft()->path->getAppPath();

		// Get rid of all the .bak files/folders.
		$filesToDelete = IOHelper::getFolderContents($appPath, true, ".*\.bak$");

		// Now delete any files/folders that were marked for deletion in the manifest file.
		$manifestData = UpdateHelper::getManifestData($unzipFolder);

		if ($manifestData)
		{
			foreach ($manifestData as $row)
			{
				if (UpdateHelper::isManifestVersionInfoLine($row))
				{
					continue;
				}

				$rowData = explode(';', $row);

				if ($rowData[1] == PatchManifestFileAction::Remove)
				{
					if (UpdateHelper::isManifestLineAFolder($rowData[0]))
					{
						$tempFilePath = UpdateHelper::cleanManifestFolderLine($rowData[0]);
					}
					else
					{
						$tempFilePath = $rowData[0];
					}

					$filesToDelete[] = $appPath.$tempFilePath;
				}
			}

			foreach ($filesToDelete as $fileToDelete)
			{
				if (IOHelper::fileExists($fileToDelete))
				{
					if (IOHelper::isWritable($fileToDelete))
					{
						Craft::log('Deleting file: '.$fileToDelete, LogLevel::Info, true);
						IOHelper::deleteFile($fileToDelete, true);
					}
				}
				else
				{
					if (IOHelper::folderExists($fileToDelete))
					{
						if (IOHelper::isWritable($fileToDelete))
						{
							Craft::log('Deleting .bak folder:'.$fileToDelete, LogLevel::Info, true);
							IOHelper::clearFolder($fileToDelete, true);
							IOHelper::deleteFolder($fileToDelete, true);
						}
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
	 * @param string $downloadFilePath
	 * @param string $sourceMD5
	 *
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
	 * @param string $downloadFilePath
	 * @param string $unzipFolder
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
	 * @param string $unzipFolder
	 *
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
	 * Attempt to backup each of the update manifest files by copying them to a file with the same name with a .bak
	 * extension. If there is an exception thrown, we attempt to roll back all of the changes.
	 *
	 * @param string $unzipFolder
	 *
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

	/**
	 * @param string $unzipFolder
	 *
	 * @throws Exception
	 * @return array
	 */
	private function _validateNewRequirements($unzipFolder)
	{
		$requirementsFolderPath = $unzipFolder.'/app/etc/requirements/';
		$requirementsFile = $requirementsFolderPath.'Requirements.php';
		$errors = array();

		if (!IOHelper::fileExists($requirementsFile))
		{
			throw new Exception(Craft::t('The Requirements file is required and it does not exist at {path}.', array('path' => $requirementsFile)));
		}

		// Make sure we can write to craft/app/requirements
		if (!IOHelper::isWritable(craft()->path->getAppPath().'etc/requirements/'))
		{
			throw new Exception(Craft::t('@@@appName@@@ needs to be able to write to your craft/app/etc/requirements folder and cannot. Please check your [permissions]({url}).', array('url' => 'http://buildwithcraft.com/docs/updating#one-click-updating')));
		}

		$tempFileName = StringHelper::UUID().'.php';

		// Make a dupe of the requirements file and give it a random file name.
		IOHelper::copyFile($requirementsFile, $requirementsFolderPath.$tempFileName);

		$newTempFilePath = craft()->path->getAppPath().'etc/requirements/'.$tempFileName;

		// Copy the random file name requirements to the requirements folder.
		// We don't want to execute any PHP from the storage folder.
		IOHelper::copyFile($requirementsFolderPath.$tempFileName, $newTempFilePath);

		require_once($newTempFilePath);

		$checker = new RequirementsChecker();
		$checker->run();

		if ($checker->getResult() == RequirementResult::Failed)
		{
			foreach ($checker->getRequirements() as $requirement)
			{
				if ($requirement->getResult() == InstallStatus::Failed)
				{
					Craft::log('Requirement "'.$requirement->getName().'" failed with the message: '.$requirement->getNotes(), LogLevel::Error, true);
					$errors[] = $requirement->getNotes();
				}
			}
		}

		// Cleanup
		IOHelper::deleteFile($newTempFilePath);

		return $errors;
	}

	/**
	 * Turns an array of messages into a Markdown-formatted bulleted list.
	 *
	 * @param string $messages
	 *
	 * @return string
	 */
	private function _markdownList($messages)
	{
		$list = '';

		foreach ($messages as $message)
		{
			$list .= '- '.$message."\n";
		}

		return $list;
	}
}
