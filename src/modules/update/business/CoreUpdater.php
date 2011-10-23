<?php

class CoreUpdater
{
	private $_latestVersionNumber;
	private $_latestBuildNumber;
	private $_edition;
	private $_type;
	private $_localVersionNumber;
	private $_localBuildNumber;
	private $_localEdition;
	private $_buildsToUpdate;
	private $_migrationsToRun;

	function __construct($latestVersionNumber, $latestBuildNumber, $edition)
	{
		$this->_latestBuildNumber = $latestBuildNumber;
		$this->_latestVersionNumber = $latestVersionNumber;
		$this->_edition = $edition;
		$this->_type = CoreReleaseFileType::Patch;
		$this->_localBuildNumber = Blocks::getBuildNumber();
		$this->_localVersionNumber = Blocks::getVersion();
		$this->_localEdition = Blocks::getEdition();
		$this->_migrationsToRun = null;
		$this->_buildsToUpdate = null;
	}


	public function checkRequirements()
	{
		$localPHPVersion = Blocks::app()->configRepo->getLocalPHPVersion();
		$localDatabaseType = Blocks::app()->configRepo->getDatabaseType();
		$localDatabaseVersion = Blocks::app()->configRepo->getDatabaseVersion();
		$requiredDatabaseVersion = Blocks::app()->configRepo->getDatabaseRequiredVersionByType($localDatabaseType);
		$requiredPHPVersion = Blocks::app()->configRepo->getRequiredPHPVersion();

		$phpCompat = version_compare($localPHPVersion, $requiredPHPVersion, '>=');
		$databaseCompat = version_compare($localDatabaseVersion, $requiredDatabaseVersion, '>=');

		if (!$phpCompat && !$databaseCompat)
			throw new BlocksException('The update cannot be installed because Blocks requires PHP version '.$requiredPHPVersion.' or higher and '.$localDatabaseType.' version '.$requiredDatabaseVersion.' or higher.  You have PHP version '.$localPHPVersion.' and '.$localDatabaseType.' version '.$localDatabaseVersion.' installed.');
		else
			if (!$phpCompat)
				throw new BlocksException('The update cannot be installed because Blocks requires PHP version '.$requiredPHPVersion.' or higher and you have PHP version '.$localPHPVersion.' installed.');
			else
				if (!$databaseCompat)
					throw new BlocksException('The update cannot be installed because Blocks requires '.$localDatabaseType.' version '.$requiredDatabaseVersion.' or higher and you have '.$localDatabaseType.' version '.$localPHPVersion.' installed.');
	}

	public function getReleaseNumbersToUpdate()
	{
		$client = new HttpClient(APIWebServiceEndPoints::GetReleaseNumbersToUpdate, array(
			'timeout'       =>  30,
			'maxredirects'  =>  0
		));

		$client->setParameterGet(array(
			'buildNumber' => $this->_localBuildNumber,
		));

		$response = $client->request('GET');

		if ($response->isSuccessful())
		{
			$buildsToUpdate = CJSON::decode($response->getBody());
			return empty($buildsToUpdate) ? null : $buildsToUpdate;
		}
		else
		{
			throw new BlocksException('Error in calling '.APIWebServiceEndPoints::GetReleaseNumbersToUpdate.' Response: '.$response->getBody());
		}
	}

	public function start()
	{
		$this->checkRequirements();

		$this->_buildsToUpdate = $this->getReleaseNumbersToUpdate();

		if ($this->_buildsToUpdate == null)
		{
			Blocks::app()->user->setFlash('notice', 'Blocks is already up to date.');
			return false;
		}

		foreach ($this->_buildsToUpdate as $buildToUpdate)
		{
			$downloadFilePath = Blocks::app()->getRuntimePath().DIRECTORY_SEPARATOR.$this->constructCoreReleasePatchFileName($buildToUpdate['version'], $buildToUpdate['build']);

			// download the package
			if (!$this->downloadPackage($buildToUpdate['version'], $buildToUpdate['build'], $downloadFilePath))
				throw new BlocksException('There was a problem downloading the package.');

			// validate
			if (!$this->validatePackage($buildToUpdate['version'], $buildToUpdate['build'], $downloadFilePath))
				throw new BlocksException('There was a problem validating the downloaded package.');

			// unpack
			if (!$this->unpackPackage($downloadFilePath))
				throw new BlocksException('There was a problem unpacking the downloaded package.');
		}

		$manifest = $this->generateMasterManifest();

		if (!empty($this->_migrationsToRun))
		{
			if ($this->_migrationsToRun != null)
			{
				if (!$this->doDatabaseUpdate())
					throw new BlocksException('There was a problem updating your database.');
			}
		}

		if (!$this->backupFiles($manifest))
			throw new BlocksException('There was a problem backing up your files for the update.');

		if (!UpdateHelper::doFileUpdate($manifest, UpdaterType::Core, true))
			throw new BlocksException('There was a problem updating your files.');

		$this->cleanTempFiles($manifest);
		return true;
	}

	public function resume($manifestId, $status)
	{
		if ($status == '0')
			throw new BlocksException('There was a problem performing the update.  Please try again.');

		$manifestFile = Blocks::app()->file->set(Blocks::app()->getRuntimePath().DIRECTORY_SEPARATOR.'manifest_'.$manifestId);

		if (!UpdateHelper::doFileUpdate($manifestFile, UpdaterType::Core, false))
			throw new BlocksException('There was a problem updating your files.');

		$this->cleanTempFiles($manifestFile);
		return true;
	}

	public function generateMasterManifest()
	{
		$masterManifest = Blocks::app()->file->set(Blocks::app()->getRuntimePath().DIRECTORY_SEPARATOR.'manifest_'.uniqid());
		$masterManifest->exists ? $masterManifest->delete() : $masterManifest->create();

		$updaterFiles = array();
		$coreFiles = array();

		foreach ($this->_buildsToUpdate as $buildToUpdate)
		{
			$downloadedFile = Blocks::app()->getRuntimePath().DIRECTORY_SEPARATOR.$this->constructCoreReleasePatchFileName($buildToUpdate['version'], $buildToUpdate['build']);
			$tempDir = $this->getTempDirForPackage($downloadedFile);

			$manifestData = $this->getManifestData($tempDir->getRealPath());

			// we get all of the updater files and core files separately because we need to update the updater files first.
			for ($i = 0; $i < count($manifestData); $i++)
			{
				// first line is version information
				if ($i == 0)
					continue;

				// normalize directory separators
				$manifestData[$i] = str_replace('\\', '/', $manifestData[$i]);
				$row = explode(';', $manifestData[$i]);

				// catch any rogue blank lines
				if (count($row) > 1)
				{
					switch (trim($row[2]))
					{
						// here we only want to add the latest unique file/action/updatetype combinations
						case UpdaterType::Updater:

							$counter = 0;
							$found = $this->inManifestList($counter, $manifestData[$i], $updaterFiles);

							if ($found)
								$updaterFiles[$counter] = $tempDir->getRealPath().';'.$manifestData[$i];
							else
								$updaterFiles[] = $tempDir->getRealPath().';'.$manifestData[$i];

							break;

						case UpdaterType::Core:

							$counter = 0;
							$found = $this->inManifestList($counter, $manifestData[$i], $coreFiles);

							if ($found)
								$coreFiles[$counter] = $tempDir->getRealPath().';'.$manifestData[$i];
							else
								$coreFiles[] = $tempDir->getRealPath().';'.$manifestData[$i];

							break;
					}
				}
			}
		}

		if (count($updaterFiles) > 0)
		{
			// write the updater files first.
			$uniqueUpdaterFiles = array_unique($updaterFiles, SORT_STRING);

			for ($counter = 0; $counter < count($uniqueUpdaterFiles); $counter++)
			{
				$row = explode(';', $uniqueUpdaterFiles[$counter]);
				$this->processMigration($row);

				$manifestContent = $uniqueUpdaterFiles[$counter].PHP_EOL;

				// if we're on the last one and there are no changed core files, don't write the last newline.
				if ($counter == count($uniqueUpdaterFiles) - 1)
				{
					if ($coreFiles == null)
						$manifestContent = $uniqueUpdaterFiles[$counter];
				}

				$masterManifest->setContents(null, $manifestContent, true, FILE_APPEND);
			}
		}

		if (count($coreFiles) > 0)
		{
			// write the core files
			$uniqueCoreFiles = array_unique($coreFiles, SORT_STRING);

			for ($counter = 0; $counter < count($uniqueCoreFiles); $counter++)
			{
				$row = explode(';', $uniqueCoreFiles[$counter]);
				$this->processMigration($row);

				$manifestContent = $uniqueCoreFiles[$counter].PHP_EOL;

				// if we're on the last one don't write the last newline.
				if ($counter == count($uniqueCoreFiles) - 1)
					$manifestContent = $uniqueCoreFiles[$counter];

				$masterManifest->setContents(null, $manifestContent, true, FILE_APPEND);
			}
		}

		return $masterManifest;
	}

	private function processMigration($row)
	{
		if (strpos($row[1], '/migrations/') !== false && $row[2] == PatchManifestFileAction::Add)
			$this->copyMigrationFile($row);
	}

	private function copyMigrationFile($fileInfo)
	{
		$migrationFile = Blocks::app()->file->set($fileInfo[0].DIRECTORY_SEPARATOR.$fileInfo[1]);
		$destinationFile = Blocks::app()->getBasePath().DIRECTORY_SEPARATOR.'migrations'.DIRECTORY_SEPARATOR.$migrationFile->getBaseName();
		$migrationFile->copy($destinationFile, true);
		$this->_migrationsToRun[] = $destinationFile;
	}

	private function inManifestList(&$counter, $manifestDataRow, $fileList)
	{
		$found = false;
		for ($counter; $counter < count($fileList); $counter++)
		{
			$pieces = explode(';', $fileList[$counter]);
			if ($manifestDataRow === $pieces[1].';'.$pieces[2].';'.$pieces[3])
			{
				$found = true;
				break;
			}
		}

		return $found;
	}

	public function putSiteInMaintenanceMode()
	{
		$file = Blocks::app()->file->set(BLOCKS_BASE_PATH.'index.php', false);
		$contents = $file->getContents();
		$contents = str_replace('//header(\'location:offline.php\');', 'header(\'location:offline.php\');', $contents);
		$file->setContents(null, $contents);
		return true;
	}

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

	public function cleanTempFiles($manifestFile)
	{
		$manifestData = explode(PHP_EOL, $manifestFile->getContents());

		foreach ($manifestData as $row)
		{
			$rowData = explode(';', $row);
			$tempDir = Blocks::app()->file->set($rowData[0]);
			$tempFile = Blocks::app()->file->set(str_replace('_temp', '', $rowData[0]).'.zip');

			// delete the temp dirs
			if ($tempDir->exists)
				$tempDir->delete();

			// delete the downloaded zip file
			if ($tempFile->exists)
				$tempFile->delete();

			// delete the cms files we backed up.
			$backupFile = Blocks::app()->file->set(BLOCKS_BASE_PATH.$rowData[1].'.bak');
			if ($backupFile->exists)
				$backupFile->delete();
		}

		// delete the manifest file.
		$manifestFile->delete();
	}

	public function downloadPackage($version, $build, $destinationPath)
	{
		$client = new HttpClient(APIWebServiceEndPoints::DownloadPackage, array(
			'timeout'       =>  30,
			'maxredirects'  =>  0
		));

		$client->setParameterPost(array(
			'versionNumber' => $version,
			'buildNumber' => $build,
			'edition' => $this->_edition,
			'type' => CoreReleaseFileType::Patch
		));

		$client->setStream($destinationPath);

		$response = $client->request('POST');

		if ($response->isSuccessful())
		{
			return true;
		}
		else
		{
			throw new BlocksException('Error in calling '.APIWebServiceEndPoints::DownloadPackage.' Response: '.$response->getBody());
		}
	}

	public function validatePackage($version, $build, $destinationPath)
	{
		$client = new HttpClient(APIWebServiceEndPoints::GetCoreReleaseFileMD5, array(
			'timeout'       =>  30,
			'maxredirects'  =>  0
		));

		$client->setParameterGet(array(
			'versionNumber' => $version,
			'buildNumber' => $build,
			'edition' => $this->_edition,
			'type' => CoreReleaseFileType::Patch
		));

		$response = $client->request('GET');

		if ($response->isSuccessful())
		{
			$sourceMD5 = $response->getBody();

			if(StringHelper::IsNullOrEmpty($sourceMD5))
				throw new BlocksException('Error in getting the MD5 hash for the download.');
		}
		else
		{
			throw new BlocksException('Error in calling '.APIWebServiceEndPoints::GetCoreReleaseFileMD5.' Response: '.$response->getBody());
		}

		$localFile = Blocks::app()->file->set($destinationPath, false);
		$localMD5 = $localFile->generateMD5();

		if($localMD5 === $sourceMD5)
			return true;

		return false;
	}

	public function unpackPackage($downloadPath)
	{
		$tempDir = $this->getTempDirForPackage($downloadPath);
		$tempDir->exists ? $tempDir->delete() : $tempDir->createDir(0754);

		$downloadPath = Blocks::app()->file->set($downloadPath);
		if ($downloadPath->unzip($tempDir->getRealPath()))
			return true;

		return false;
	}

	private function getTempDirForPackage($downloadPath)
	{
		$downloadPath = Blocks::app()->file->set($downloadPath);
		return Blocks::app()->file->set($downloadPath->getDirName().DIRECTORY_SEPARATOR.$downloadPath->getFileName().'_temp');
	}

	private function constructCoreReleasePatchFileName($version, $build)
	{
		if(StringHelper::IsNullOrEmpty($version) || StringHelper::IsNullOrEmpty($build) || StringHelper::IsNullOrEmpty($this->_edition))
			throw new BlocksException('Missing versionNumber or buildNumber or edition.');

		switch ($this->_edition)
		{
			case BlocksEdition::Personal:
				return BLOCKSBUILDS_PERSONAL_FILENAME.'v'.$version.'.'.$build.'_patch.zip';

			case BlocksEdition::Pro:
				return BLOCKSBUILDS_PRO_FILENAME.'v'.$version.'.'.$build.'_patch.zip';

			case BlocksEdition::Standard:
				return BLOCKSBUILDS_STANDARD_FILENAME.'v'.$version.'.'.$build.'_patch.zip';
		}

		throw new BlocksException('Unknown Blocks Edition: '.$this->_edition);
	}

	private function getManifestData($tempDirPath)
	{
		// get manifest file
		$manifestFile = Blocks::app()->file->set($tempDirPath.DIRECTORY_SEPARATOR.'blocks_manifest');
		$manifestFileData = $manifestFile->getContents();
		return explode(PHP_EOL, $manifestFileData);
	}

	public function backupFiles($masterManifest)
	{
		$manifestData = explode(PHP_EOL, $masterManifest->getContents());

		try
		{
			foreach ($manifestData as $row)
			{
				$rowData = explode(';', $row);
				$file = Blocks::app()->file->set(BLOCKS_BASE_PATH.$rowData[1]);

				// if the file doesn't exist, it's a new file
				if ($file->exists)
					$file->copy($file->getRealPath().'.bak');
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
