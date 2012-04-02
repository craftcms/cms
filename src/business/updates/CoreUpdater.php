<?php
namespace Blocks;

/**
 *
 */
class CoreUpdater implements IUpdater
{
	private $_buildsToUpdate = null;
	private $_migrationsToRun = null;
	private $_blocksUpdateInfo = null;
	private $_downloadFilePath = null;
	private $_tempPackageDir = null;
	private $_manifestData = null;

	/**
	 *
	 */
	function __construct()
	{
		$this->_blocksUpdateInfo = b()->updates->getUpdateInfo(true);
		$this->_migrationsToRun = null;
		$this->_buildsToUpdate = $this->_blocksUpdateInfo->newerReleases;
	}

	/**
	 *
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
	 * @return bool
	 * @throws Exception
	 */
	public function start()
	{
		$this->checkRequirements();

		if ($this->_buildsToUpdate == null)
			throw new Exception('Blocks is already up to date.');

		Blocks::log('Starting the CoreUpdater.', \CLogger::LEVEL_INFO);

		// get the most up-to-date build.
		$latestBuild = $this->_buildsToUpdate[0];
		$this->_downloadFilePath = b()->path->runtimePath.UpdateHelper::constructCoreReleasePatchFileName($latestBuild->version, $latestBuild->build, Blocks::getEdition());
		$this->_tempPackageDir = UpdateHelper::getTempDirForPackage($this->_downloadFilePath);

		// download the package
		Blocks::log('Downlading patch file to '.$this->_downloadFilePath, \CLogger::LEVEL_INFO);
		if (!b()->et->downloadPackage($latestBuild->version, $latestBuild->build, $this->_downloadFilePath))
			throw new Exception('There was a problem downloading the package.');

		// validate
		if (!$this->validatePackage($latestBuild->version, $latestBuild->build))
			throw new Exception('There was a problem validating the downloaded package.');

		// unpack
		if (!$this->unpackPackage())
			throw new Exception('There was a problem unpacking the downloaded package.');

		// check to see if there any migrations to run.
		$this->gatherMigrations();

		// put site in maintenance mode.
		$this->putSiteInMaintenanceMode();

		// if there are migrations, run them.
		if (!empty($this->_migrationsToRun) && $this->_migrationsToRun != null)
		{
			if (!$this->doDatabaseUpdate())
				throw new Exception('There was a problem updating your database.');
		}

		// backup files.
		if (!$this->backupFiles())
			throw new Exception('There was a problem backing up your files for the update.');

		// update files.
		if (!UpdateHelper::doFileUpdate($this->_getManifestData(), $this->_tempPackageDir))
			throw new Exception('There was a problem updating your files.');

		// take site out of maintenance mode.
		$this->takeSiteOutOfMaintenanceMode();

		// clean-up leftover files.
		$this->cleanTempFiles();

		if (!b()->updates->flushUpdateInfoFromCache())
			throw new Exception('The update was performed sucessfully, but there was a problem invalidating the update cache.');

		if (!b()->updates->setNewVersionAndBuild($latestBuild->version, $latestBuild->build))
			throw new Exception('The update was performed sucessfully, but there was a problem setting the new version and build number in the database.');

		return true;
	}

	/**
	 * @return array
	 */
	private function _getManifestData()
	{
		if ($this->_manifestData == null)
		{
			$manifestData = UpdateHelper::getManifestData($this->_tempPackageDir->realPath);

			// only use the manifest data starting from the local version
			for ($counter = 0; $counter < count($manifestData); $counter++)
			{
				if (strpos($manifestData[$counter], '##'.$this->_blocksUpdateInfo->localVersion.'.'.$this->_blocksUpdateInfo->localBuild) !== false)
					break;
			}

			$manifestData = array_slice($manifestData, $counter);
			$this->_manifestData = $manifestData;
		}

		return $this->_manifestData;
	}

	/**
	 * @return mixed
	 */
	public function gatherMigrations()
	{
		$manifestData = $this->_getManifestData();

		for ($i = 0; $i < count($manifestData); $i++)
		{
			$row = explode(';', $manifestData[$i]);

			// we found a migration
			if (UpdateHelper::isManifestMigrationLine($row[0]) && $row[1] == PatchManifestFileAction::Add)
			{
				Blocks::log('Found migration file: '.$row[0], \CLogger::LEVEL_INFO);
				$this->_migrationsToRun[] = UpdateHelper::copyMigrationFile($this->_tempPackageDir->realPath.'/'.$row[0]);
			}
		}
	}

	/**
	 * @return bool
	 */
	public function putSiteInMaintenanceMode()
	{
		$file = b()->file->set(b()->path->appPath.'../../index.php', false);
		$contents = $file->contents;
		$contents = str_replace('//include(\'offline.php\');', 'include(\'offline.php\');', $contents);
		$file->setContents(null, $contents);
		return true;
	}

	/**
	 * @return bool
	 */
	public function takeSiteOutOfMaintenanceMode()
	{
		$file = b()->file->set(b()->path->appPath.'../../index.php', false);
		$contents = $file->contents;
		$contents = str_replace('include(\'offline.php\');', '//include(\'offline.php\');', $contents);
		$file->setContents(null, $contents);
		return true;
	}

	/**
	 * @return bool
	 */
	public function doDatabaseUpdate()
	{
		foreach ($this->_migrationsToRun as $migrationName)
		{
			if (b()->updates->runMigration($migrationName))
				return true;
		}

		return false;
	}

	/**
	 *
	 */
	public function cleanTempFiles()
	{
		$manifestData = $this->_getManifestData();

		foreach ($manifestData as $row)
		{
			if (UpdateHelper::isManifestVersionInfoLine($row))
				continue;

			$rowData = explode(';', $row);

			// delete any files we backed up.
			$backupFile = b()->file->set(b()->path->appPath.'../../'.$rowData[0].'.bak');
			if ($backupFile->exists)
			{
				Blocks::log('Deleting backup file: '.$backupFile->realPath);
				$backupFile->delete();
			}
		}

		// delete the temp patch dir
		$tempPatchDir = $this->_tempPackageDir;
		$tempPatchDir->delete();

		// delete the downloaded patch file.
		$downloadPatchFile = b()->file->set($this->_downloadFilePath);
		$downloadPatchFile->delete();
	}

	/**
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

				// if the file doesn't exist, it's a new file
				if ($file->exists)
				{
					Blocks::log('Backing up file '.$file->realPath);
					$file->copy($file->realPath.'.bak');
				}
			}
		}
		catch (Exception $e)
		{
			Blocks::log('Error updating files: '.$e->getMessage());
			UpdateHelper::rollBackFileChanges($manifestData);
			return false;
		}

		return true;
	}
}
