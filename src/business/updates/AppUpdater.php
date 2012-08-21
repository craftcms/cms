<?php
namespace Blocks;

/**
 *
 */
class AppUpdater implements IUpdater
{
	private $_buildsToUpdate;
	private $_migrationsToRun = false;
	private $_updateInfo;
	private $_downloadFilePath;
	private $_tempPackageDir;
	private $_manifestData;
	private $_writableErrors = null;

	/**
	 *
	 */
	function __construct()
	{
		$this->_updateInfo = blx()->updates->getUpdateInfo(true);
		$this->_buildsToUpdate = $this->_updateInfo->blocks->releases;
	}

	/**
	 * Performs environmental requirement checks before running an update.
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
			throw new Exception(Blocks::t('The update can’t be installed because @@@productDisplay@@@ requires PHP version "{requiredPhpVersion}" or higher and MySQL version "{requiredMySqlVersion}" or higher.  You have PHP version "{installedPhpVersion}" and MySQL version "{installedMySqlVersion}" installed.',
				array('requiredPhpVersion' => $requiredMySqlVersion,
				      'installedPhpVersion' => PHP_VERSION,
				      'requiredMySqlVersion' => $requiredMySqlVersion,
				      'installedMySqlVersion' => $installedMySqlVersion
				)));
		else
			if (!$phpCompat)
				throw new Exception(Blocks::t('The update can’t be installed because @@@productDisplay@@@ requires PHP version "{requiredPhpVersion}" or higher and you have PHP version "{installedPhpVersion}" installed.',
					array('requiredPhpVersion' => $requiredMySqlVersion,
					      'installedPhpVersion' => PHP_VERSION
					)));
			else
				if (!$databaseCompat)
					throw new Exception(Blocks::t('The update can’t be installed because @@@productDisplay@@@ requires MySQL version "{requiredMySqlVersion}" or higher and you have MySQL version "{installedMySqlVersion}" installed.',
						array('requiredMySqlVersion' => $requiredMySqlVersion,
						      'installedMySqlVersion' => $installedMySqlVersion
						)));


	}

	/**
	 * Starts the process of running a @@@productDisplay@@@ app update.
	 * @return bool
	 * @throws Exception
	 */
	public function start()
	{
		$this->checkRequirements();

		if ($this->_buildsToUpdate == null)
			throw new Exception(Blocks::t('@@@productDisplay@@@ is already up to date.'));

		Blocks::log('Starting the AppUpdater.', \CLogger::LEVEL_INFO);

		// Get the most up-to-date build.
		$latestBuild = $this->_buildsToUpdate[0];
		$this->_downloadFilePath = blx()->path->getRuntimePath()."@@@product@@@{$latestBuild->version}.{$latestBuild->build}_patch.zip";
		$this->_tempPackageDir = UpdateHelper::getTempDirForPackage($this->_downloadFilePath);

		// Download the package from ET.
		Blocks::log('Downloading patch file to '.$this->_downloadFilePath, \CLogger::LEVEL_INFO);
		if (!blx()->et->downloadPackage($latestBuild->version, $latestBuild->build, $this->_downloadFilePath))
			throw new Exception(Blocks::t('There was a problem downloading the package.'));

		// Validate the downloaded package against ET.
		Blocks::log('Validating downloaded package.', \CLogger::LEVEL_INFO);
		if (!$this->validatePackage($latestBuild->version, $latestBuild->build))
			throw new Exception(Blocks::t('There was a problem validating the downloaded package.'));

		// Unpack the downloaded package.
		Blocks::log('Unpacking the downloaded package.', \CLogger::LEVEL_INFO);
		if (!$this->unpackPackage())
			throw new Exception(Blocks::t('There was a problem unpacking the downloaded package.'));

		// Validate that the paths in the update manifest file are all writable by @@@productDisplay@@@
		Blocks::log('Validating update manifest file paths are writable.', \CLogger::LEVEL_INFO);
		if (!$this->validateManifestPathsWritable())
			throw new Exception(Blocks::t('@@@productDisplay@@@ needs to be able to write to the follow files, but can’t: {fileList}', array('fileList' => implode(',', $this->_writableErrors))));

		// Check to see if there any migrations to run.
		Blocks::log('Checking to see if there are any migrations to run in the update.', \CLogger::LEVEL_INFO);
		$this->gatherMigrations();

		// Take the site offline.
		Blocks::log('Taking the site offline for update.', \CLogger::LEVEL_INFO);
		blx()->updates->turnSystemOffBeforeUpdate();

		// If there are migrations to run, run them.
		if ($this->_migrationsToRun === true)
		{
			Blocks::log('Starting to run update migrations.', \CLogger::LEVEL_INFO);
			if (!$this->doDatabaseUpdate())
				throw new Exception(Blocks::t('There was a problem updating your database.'));
		}

		// Backup any files about to be updated.
		Blocks::log('Backing up files that are about to be updated.', \CLogger::LEVEL_INFO);
		if (!$this->backupFiles())
			throw new Exception(Blocks::t('There was a problem backing up your files for the update.'));

		// Update the files.
		Blocks::log('Performing file udpate.', \CLogger::LEVEL_INFO);
		if (!UpdateHelper::doFileUpdate($this->_getManifestData(), $this->_tempPackageDir))
			throw new Exception(Blocks::t('There was a problem updating your files.'));

		// Bring the system back online.
		Blocks::log('Turning system back on after update.', \CLogger::LEVEL_INFO);
		blx()->updates->turnSystemOnAfterUpdate();

		// Clean-up any leftover files.
		Blocks::log('Cleaning up temp files after update.', \CLogger::LEVEL_INFO);
		$this->cleanTempFiles();

		// Clear the updates cache.
		Blocks::log('Clearing the update cache.', \CLogger::LEVEL_INFO);
		if (!blx()->updates->flushUpdateInfoFromCache())
			throw new Exception(Blocks::t('The update was performed successfully, but there was a problem invalidating the update cache.'));

		// Update the db with the new @@@productDisplay@@@ info.
		Blocks::log('Setting new @@@productDisplay@@@ info in the database after update.', \CLogger::LEVEL_INFO);
		if (!blx()->updates->setNewBlocksInfo($latestBuild->version, $latestBuild->build, $latestBuild->date))
			throw new Exception(Blocks::t('The update was performed successfully, but there was a problem setting the new version and build number in the database.'));

		Blocks::log('Finished AppUpdater.', \CLogger::LEVEL_INFO);
		return true;
	}

	/**
	 * Returns the relevant lines from the update manifest file starting with the current local version/build.
	 * @return array
	 */
	private function _getManifestData()
	{
		if ($this->_manifestData == null)
		{
			$manifestData = UpdateHelper::getManifestData($this->_tempPackageDir->getRealPath());

			// Only use the manifest data starting from the local version
			for ($counter = 0; $counter < count($manifestData); $counter++)
			{
				if (strpos($manifestData[$counter], '##'.$this->_updateInfo->blocks->localVersion.'.'.$this->_updateInfo->blocks->localBuild) !== false)
					break;
			}

			$manifestData = array_slice($manifestData, $counter);
			$this->_manifestData = $manifestData;
		}

		return $this->_manifestData;
	}

	/**
	 * Scans through the update manifest file for any migrations.  If it finds one, copies it to the app's miggrations
	 * folder so they can be ran before any file updates occur.
	 * @return mixed
	 */
	public function gatherMigrations()
	{
		$manifestData = $this->_getManifestData();

		for ($i = 0; $i < count($manifestData); $i++)
		{
			$row = explode(';', $manifestData[$i]);

			// We found a migration
			if (UpdateHelper::isManifestMigrationLine($row[0]) && $row[1] == PatchManifestFileAction::Add)
			{
				Blocks::log('Found migration file: '.$row[0], \CLogger::LEVEL_INFO);
				UpdateHelper::copyMigrationFile($this->_tempPackageDir->getRealPath().'/'.$row[0]);
				$this->_migrationsToRun = true;
			}
		}
	}

	/**
	 * Run the database migrations to top.
	 * @return bool
	 */
	public function doDatabaseUpdate()
	{
		if (blx()->migrations->runToTop())
			return true;

		return false;
	}

	/**
	 * Remove any temp files and/or directories that might have been created.
	 */
	public function cleanTempFiles()
	{
		$manifestData = $this->_getManifestData();

		foreach ($manifestData as $row)
		{
			if (UpdateHelper::isManifestVersionInfoLine($row))
				continue;

			$rowData = explode(';', $row);

			// Delete any files we backed up.
			$backupFile = blx()->file->set(blx()->path->getAppPath().'../../'.$rowData[0].'.bak');
			if ($backupFile->getExists())
			{
				Blocks::log('Deleting backup file: '.$backupFile->getRealPath());
				$backupFile->delete();
			}
		}

		// Delete the temp patch dir
		$tempPatchDir = $this->_tempPackageDir;
		$tempPatchDir->delete();

		// Delete the downloaded patch file.
		$downloadPatchFile = blx()->file->set($this->_downloadFilePath);
		$downloadPatchFile->delete();
	}

	/**
	 * Calculate the MD5 of the downloaded file and verify it with ET against the stored MD5 of the patch file.
	 * @param $version
	 * @param $build
	 * @return bool
	 * @throws Exception
	 */
	public function validatePackage($version, $build)
	{
		Blocks::log('Validating MD5 for '.$this->_downloadFilePath, \CLogger::LEVEL_INFO);
		$sourceMD5 = blx()->et->getReleaseMD5($version, $build);

		if(StringHelper::isNullOrEmpty($sourceMD5))
			throw new Exception(Blocks::t('Error in validating the download.'));

		$localFile = blx()->file->set($this->_downloadFilePath, false);
		$localMD5 = $localFile->generateMD5();

		if($localMD5 === $sourceMD5)
			return true;

		return false;
	}

	/**
	 * Unzip the downloaded update file into the temp package directory.
	 * @return bool
	 */
	public function unpackPackage()
	{
		Blocks::log('Unzipping package to '.$this->_tempPackageDir->getRealPath(), \CLogger::LEVEL_INFO);
		if ($this->_tempPackageDir->getExists())
			$this->_tempPackageDir->delete();

		$this->_tempPackageDir->createDir(0754);

		$downloadPath = blx()->file->set($this->_downloadFilePath);
		if ($downloadPath->unzip($this->_tempPackageDir->getRealPath()))
			return true;

		return false;
	}

	/**
	 * Checks to see if the files that we are about to update are writable by @@@productDisplay@@@.
	 * @return bool
	 */
	public function validateManifestPathsWritable()
	{
		$manifestData = $this->_getManifestData();

		foreach ($manifestData as $row)
		{
			if (UpdateHelper::isManifestVersionInfoLine($row))
				continue;

			$rowData = explode(';', $row);
			$file = blx()->file->set(blx()->path->getAppPath().'../../'.$rowData[0]);

			// Check to see if the file we need to update is writable.
			if ($file->getExists())
			{
				if (!$file->getWritable())
					$this->_writableErrors[] = $file->getRealPath();
			}
		}

		return $this->_writableErrors === null;
	}

	/**
	 * Attempt to backup each of the update manifest files by copying them to a file with the same name with a .bak extension.
	 * If there is an exception thrown, we attempt to roll back all of the changes.
	 * @return bool
	 */
	public function backupFiles()
	{
		$manifestData = $this->_getManifestData();

		try
		{
			foreach ($manifestData as $row)
			{
				if (UpdateHelper::isManifestVersionInfoLine($row))
					continue;

				// No need to back up migration files.
				if (UpdateHelper::isManifestMigrationLine($row))
					continue;

				$rowData = explode(';', $row);
				$file = blx()->file->set(blx()->path->getAppPath().'../../'.$rowData[0]);

				// If the file doesn't exist, it's a new file.
				if ($file->getExists())
				{
					Blocks::log('Backing up file '.$file->getRealPath());
					$file->copy($file->getRealPath().'.bak');
				}
			}
		}
		catch (\Exception $e)
		{
			Blocks::log('Error updating files: '.$e->getMessage());
			UpdateHelper::rollBackFileChanges($manifestData);
			return false;
		}

		return true;
	}
}
