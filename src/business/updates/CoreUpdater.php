<?php
namespace Blocks;

/**
 *
 */
class CoreUpdater implements IUpdater
{
	private $_buildsToUpdate;
	private $_migrationsToRun = false;
	private $_updateInfo;
	private $_downloadFilePath;
	private $_tempPackageDir;
	private $_manifestData;
	private $_writeableErrors = null;

	/**
	 *
	 */
	function __construct()
	{
		$this->_updateInfo = b()->updates->getUpdateInfo(true);
		$this->_buildsToUpdate = $this->_updateInfo->blocks->releases;
	}

	/**
	 * Performs environmental requirement checks before running an update.
	 */
	public function checkRequirements()
	{
		$installedMysqlVersion = b()->db->serverVersion;
		$requiredMysqlVersion = b()->params['requiredMysqlVersion'];
		$requiredPhpVersion = b()->params['requiredPhpVersion'];

		$phpCompat = version_compare(PHP_VERSION, $requiredPhpVersion, '>=');
		$databaseCompat = version_compare($installedMysqlVersion, $requiredMysqlVersion, '>=');

		if (!$phpCompat && !$databaseCompat)
			throw new Exception('The update cannot be installed because Blocks requires PHP version '.$requiredPhpVersion.' or higher and MySQL version '.$requiredMysqlVersion.' or higher.  You have PHP version '.PHP_VERSION.' and MySQL version '.$installedMysqlVersion.' installed.');
		else
			if (!$phpCompat)
				throw new Exception('The update cannot be installed because Blocks requires PHP version '.$requiredPhpVersion.' or higher and you have PHP version '.PHP_VERSION.' installed.');
			else
				if (!$databaseCompat)
					throw new Exception('The update cannot be installed because Blocks requires MySQL version '.$requiredMysqlVersion.' or higher and you have MySQL version '.PHP_VERSION.' installed.');


	}

	/**
	 * Starts the process of running a Blocks app update.
	 * @return bool
	 * @throws Exception
	 */
	public function start()
	{
		$this->checkRequirements();

		if ($this->_buildsToUpdate == null)
			throw new Exception('Blocks is already up to date.');

		Blocks::log('Starting the CoreUpdater.', \CLogger::LEVEL_INFO);

		// Get the most up-to-date build.
		$latestBuild = $this->_buildsToUpdate[0];
		$this->_downloadFilePath = b()->path->runtimePath.UpdateHelper::constructCoreReleasePatchFileName($latestBuild->version, $latestBuild->build, Blocks::getEdition());
		$this->_tempPackageDir = UpdateHelper::getTempDirForPackage($this->_downloadFilePath);

		// Download the package from ET.
		Blocks::log('Downloading patch file to '.$this->_downloadFilePath, \CLogger::LEVEL_INFO);
		if (!b()->et->downloadPackage($latestBuild->version, $latestBuild->build, $this->_downloadFilePath))
			throw new Exception('There was a problem downloading the package.');

		// Validate the downloaded package against ET.
		Blocks::log('Validating downloaded package.', \CLogger::LEVEL_INFO);
		if (!$this->validatePackage($latestBuild->version, $latestBuild->build))
			throw new Exception('There was a problem validating the downloaded package.');

		// Unpack the downloded package.
		Blocks::log('Unpacking the downloaded package.', \CLogger::LEVEL_INFO);
		if (!$this->unpackPackage())
			throw new Exception('There was a problem unpacking the downloaded package.');

		// Validate that the paths in the update manifest file are all writeable by Blocks
		Blocks::log('Validating update manifest file paths are writeable.', \CLogger::LEVEL_INFO);
		if (!$this->validateManifestPathsWriteable())
			throw new Exception('Blocks needs to be able to write to the follow files, but can\'t: '.implode(',', $this->_writeableErrors));

		// Check to see if there any migrations to run.
		Blocks::log('Checking to see if there are any migrations to run in the update.', \CLogger::LEVEL_INFO);
		$this->gatherMigrations();

		// Take the site offline.
		Blocks::log('Taking the site offline for update.', \CLogger::LEVEL_INFO);
		b()->updates->turnSystemOffBeforeUpdate();

		// If there are migrations to run, run them.
		if ($this->_migrationsToRun === true)
		{
			Blocks::log('Starting to run update migrations.', \CLogger::LEVEL_INFO);
			if (!$this->doDatabaseUpdate())
				throw new Exception('There was a problem updating your database.');
		}

		// Backup any files about to be updated.
		Blocks::log('Backing up files that are about to be updated.', \CLogger::LEVEL_INFO);
		if (!$this->backupFiles())
			throw new Exception('There was a problem backing up your files for the update.');

		// Update the files.
		Blocks::log('Performing file udpate.', \CLogger::LEVEL_INFO);
		if (!UpdateHelper::doFileUpdate($this->_getManifestData(), $this->_tempPackageDir))
			throw new Exception('There was a problem updating your files.');

		// Bring the system back online.
		Blocks::log('Turning system back on after update.', \CLogger::LEVEL_INFO);
		b()->updates->turnSystemOnAfterUpdate();

		// Clean-up any leftover files.
		Blocks::log('Cleaning up temp files after update.', \CLogger::LEVEL_INFO);
		$this->cleanTempFiles();

		// Clear the updates cache.
		Blocks::log('Clearing the update cache.', \CLogger::LEVEL_INFO);
		if (!b()->updates->flushUpdateInfoFromCache())
			throw new Exception('The update was performed successfully, but there was a problem invalidating the update cache.');

		// Update the db with the new Blocks info.
		Blocks::log('Setting new Blocks info in the database after update.', \CLogger::LEVEL_INFO);
		if (!b()->updates->setNewBlocksInfo($latestBuild->version, $latestBuild->build, $latestBuild->date))
			throw new Exception('The update was performed successfully, but there was a problem setting the new version and build number in the database.');

		Blocks::log('Finished CoreUpdater.', \CLogger::LEVEL_INFO);
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
			$manifestData = UpdateHelper::getManifestData($this->_tempPackageDir->realPath);

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
				UpdateHelper::copyMigrationFile($this->_tempPackageDir->realPath.'/'.$row[0]);
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
		if (b()->migrations->runToTop())
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
			$backupFile = b()->file->set(b()->path->appPath.'../../'.$rowData[0].'.bak');
			if ($backupFile->exists)
			{
				Blocks::log('Deleting backup file: '.$backupFile->realPath);
				$backupFile->delete();
			}
		}

		// Delete the temp patch dir
		$tempPatchDir = $this->_tempPackageDir;
		$tempPatchDir->delete();

		// Delete the downloaded patch file.
		$downloadPatchFile = b()->file->set($this->_downloadFilePath);
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
		$sourceMD5 = b()->et->getReleaseMD5($version, $build);

		if(StringHelper::isNullOrEmpty($sourceMD5))
			throw new Exception('Error in getting the MD5 hash for the download.');

		$localFile = b()->file->set($this->_downloadFilePath, false);
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
		Blocks::log('Unzipping package to '.$this->_tempPackageDir->realPath, \CLogger::LEVEL_INFO);
		if ($this->_tempPackageDir->exists)
			$this->_tempPackageDir->delete();

		$this->_tempPackageDir->createDir(0754);

		$downloadPath = b()->file->set($this->_downloadFilePath);
		if ($downloadPath->unzip($this->_tempPackageDir->realPath))
			return true;

		return false;
	}

	/**
	 * Checks to see if the files that we are about to update are writeable by Blocks.
	 * @return bool
	 */
	public function validateManifestPathsWriteable()
	{
		$manifestData = $this->_getManifestData();

		foreach ($manifestData as $row)
		{
			if (UpdateHelper::isManifestVersionInfoLine($row))
				continue;

			$rowData = explode(';', $row);
			$file = b()->file->set(b()->path->appPath.'../../'.$rowData[0]);

			// Check to see if the file we need to update is writeable.
			if ($file->exists)
			{
				if (!$file->writeable)
					$this->_writeableErrors[] = $file->realPath;
			}
		}

		return $this->_writeableErrors === null;
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
				$file = b()->file->set(b()->path->appPath.'../../'.$rowData[0]);

				// If the file doesn't exist, it's a new file.
				if ($file->exists)
				{
					Blocks::log('Backing up file '.$file->realPath);
					$file->copy($file->realPath.'.bak');
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
