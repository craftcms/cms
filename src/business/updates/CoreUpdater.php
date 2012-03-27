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

		$latestBuild = $this->_buildsToUpdate[0];
		$downloadFilePath = b()->path->runtimePath.UpdateHelper::constructCoreReleasePatchFileName($latestBuild->version, $latestBuild->build, Blocks::getEdition());

		// download the package
		if (!b()->et->downloadPackage($latestBuild->version, $latestBuild->build, $downloadFilePath))
			throw new Exception('There was a problem downloading the package.');

		// validate
		if (!$this->validatePackage($latestBuild->version, $latestBuild->build, $latestBuild))
			throw new Exception('There was a problem validating the downloaded package.');

		// unpack
		if (!$this->unpackPackage($downloadFilePath))
			throw new Exception('There was a problem unpacking the downloaded package.');

		$manifest = $this->generateMasterManifest();

		if (!empty($this->_migrationsToRun))
		{
			if ($this->_migrationsToRun != null)
			{
				if (!$this->doDatabaseUpdate())
					throw new Exception('There was a problem updating your database.');
			}
		}

		if (!$this->backupFiles($manifest))
			throw new Exception('There was a problem backing up your files for the update.');

		if (!UpdateHelper::doFileUpdate($manifest))
			throw new Exception('There was a problem updating your files.');

		$this->cleanTempFiles($manifest);
		return true;
	}

	/**
	 * @return
	 */
	public function generateMasterManifest()
	{
		$masterManifest = b()->file->set(b()->path->runtimePath.'manifest_'.uniqid());
		$masterManifest->exists ? $masterManifest->delete() : $masterManifest->create();

		$updatedFiles = array();

		foreach ($this->_buildsToUpdate as $buildToUpdate)
		{
			$downloadedFile = b()->path->runtimePath.UpdateHelper::constructCoreReleasePatchFileName($buildToUpdate->version, $buildToUpdate->build, Blocks::getEdition());
			$tempDir = UpdateHelper::getTempDirForPackage($downloadedFile);

			$manifestData = UpdateHelper::getManifestData($tempDir->realPath);

			for ($i = 0; $i < count($manifestData); $i++)
			{
				// first line is version information
				if ($i == 0)
					continue;

				// normalize directory separators
				$manifestData[$i] = b()->path->normalizeDirectorySeparators($manifestData[$i]);
				$row = explode(';', $manifestData[$i]);

				// catch any rogue blank lines
				if (count($row) > 1)
				{
					$counter = 0;
					$found = UpdateHelper::inManifestList($counter, $manifestData[$i], $updatedFiles);

					if ($found)
						$updatedFiles[$counter] = $tempDir->realPath.';'.$manifestData[$i];
					else
						$updatedFiles[] = $tempDir->realPath.';'.$manifestData[$i];
				}
			}
		}

		if (count($updatedFiles) > 0)
		{
			// write the updated files out
			$uniqueUpdatedFiles = array_unique($updatedFiles, SORT_STRING);

			for ($counter = 0; $counter < count($uniqueUpdatedFiles); $counter++)
			{
				$row = explode(';', $uniqueUpdatedFiles[$counter]);

				// we found a migration
				if (strpos($row[1], '/migrations/') !== false && $row[2] == PatchManifestFileAction::Add)
					$this->_migrationsToRun[] = UpdateHelper::copyMigrationFile($row[0].'/'.$row[1]);

				$manifestContent = $uniqueUpdatedFiles[$counter].PHP_EOL;

				// if we're on the last one don't write the last newline.
				if ($counter == count($uniqueUpdatedFiles) - 1)
					$manifestContent = $uniqueUpdatedFiles[$counter];

				$masterManifest->setContents(null, $manifestContent, true, FILE_APPEND);
			}
		}

		return $masterManifest;
	}

	/**
	 * @todo Fix
	 * @return bool
	 */
	public function putSiteInMaintenanceMode()
	{
		$file = b()->file->set(BLOCKS_BASE_PATH.'../index.php', false);
		$contents = $file->contents;
		$contents = str_replace('//header(\'location:offline.php\');', 'header(\'location:offline.php\');', $contents);
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
			$response = Migration::run($migrationName);
			if (strpos($response, 'Migrated up successfully.') !== false || strpos($response, 'No new migration found.') !== false)
				return false;
		}

		return true;
	}

	/**
	 * @param $manifestFile
	 */
	public function cleanTempFiles($manifestFile)
	{
		$manifestData = explode("\n", $manifestFile->contents);

		foreach ($manifestData as $row)
		{
			$rowData = explode(';', $row);
			$tempDir = b()->file->set($rowData[0]);
			$tempFile = b()->file->set(str_replace('_temp', '', $rowData[0]).'.zip');

			// delete the temp dirs
			if ($tempDir->exists)
				$tempDir->delete();

			// delete the downloaded zip file
			if ($tempFile->exists)
				$tempFile->delete();

			// delete the cms files we backed up.
			$backupFile = b()->file->set(BLOCKS_BASE_PATH.'../'.$rowData[1].'.bak');
			if ($backupFile->exists)
				$backupFile->delete();
		}

		// delete the manifest file.
		$manifestFile->delete();
	}

	/**
	 * @param $version
	 * @param $build
	 * @param $destinationPath
	 * @return bool
	 * @throws Exception
	 */
	public function validatePackage($version, $build, $destinationPath)
	{
		$sourceMD5 = b()->et->getReleaseMD5($version, $build);

		if(StringHelper::isNullOrEmpty($sourceMD5))
			throw new Exception('Error in getting the MD5 hash for the download.');

		$localFile = b()->file->set($destinationPath, false);
		$localMD5 = $localFile->generateMD5();

		if($localMD5 === $sourceMD5)
			return true;

		return false;
	}

	/**
	 * @param $downloadPath
	 * @return bool
	 */
	public function unpackPackage($downloadPath)
	{
		$tempDir = UpdateHelper::getTempDirForPackage($downloadPath);
		$tempDir->exists ? $tempDir->delete() : $tempDir->createDir(0754);

		$downloadPath = b()->file->set($downloadPath);
		if ($downloadPath->unzip($tempDir->realPath))
			return true;

		return false;
	}

	/**
	 * @param $masterManifest
	 * @return bool
	 */
	public function backupFiles($masterManifest)
	{
		$manifestData = explode("\r\n", $masterManifest->contents);

		try
		{
			foreach ($manifestData as $row)
			{
				$rowData = explode(';', $row);
				$file = b()->file->set(BLOCKS_BASE_PATH.'../'.$rowData[1]);

				// if the file doesn't exist, it's a new file
				if ($file->exists)
					$file->copy($file->realPath.'.bak');
			}
		}
		catch (Exception $e)
		{
			Blocks::log('Error updating files: '.$e->getMessage());
			UpdateHelper::rollBackFileChanges($masterManifest);
			return false;
		}

		return true;
	}
}
