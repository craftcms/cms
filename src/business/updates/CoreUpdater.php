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
		$localPHPVersion = Blocks::app()->config->getLocalPHPVersion();
		$localDatabaseType = Blocks::app()->config->getDatabaseType();
		$localDatabaseVersion = Blocks::app()->config->getDatabaseVersion();
		$requiredDatabaseVersion = Blocks::app()->config->getDatabaseRequiredVersionByType($localDatabaseType);
		$requiredPHPVersion = Blocks::app()->config->getRequiredPHPVersion();

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
			$downloadFilePath = Blocks::app()->path->getRuntimePath().UpdateHelper::constructCoreReleasePatchFileName($buildToUpdate['version'], $buildToUpdate['build'], $this->_edition);

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

		if (!UpdateHelper::doFileUpdate($manifest))
			throw new BlocksException('There was a problem updating your files.');

		$this->cleanTempFiles($manifest);
		return true;
	}

	public function generateMasterManifest()
	{
		$masterManifest = Blocks::app()->file->set(Blocks::app()->path->getRuntimePath().'manifest_'.uniqid());
		$masterManifest->exists ? $masterManifest->delete() : $masterManifest->create();

		$updatedFiles = array();

		foreach ($this->_buildsToUpdate as $buildToUpdate)
		{
			$downloadedFile = Blocks::app()->path->getRuntimePath().UpdateHelper::constructCoreReleasePatchFileName($buildToUpdate['version'], $buildToUpdate['build'], $this->_edition);
			$tempDir = UpdateHelper::getTempDirForPackage($downloadedFile);

			$manifestData = UpdateHelper::getManifestData($tempDir->getRealPath());

			for ($i = 0; $i < count($manifestData); $i++)
			{
				// first line is version information
				if ($i == 0)
					continue;

				// normalize directory separators
				$manifestData[$i] = Blocks::app()->path->normalizeDirectorySeparators($manifestData[$i]);
				$row = explode(';', $manifestData[$i]);

				// catch any rogue blank lines
				if (count($row) > 1)
				{
					$counter = 0;
					$found = UpdateHelper::inManifestList($counter, $manifestData[$i], $updatedFiles);

					if ($found)
						$updatedFiles[$counter] = $tempDir->getRealPath().';'.$manifestData[$i];
					else
						$updatedFiles[] = $tempDir->getRealPath().';'.$manifestData[$i];
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

	public function putSiteInMaintenanceMode()
	{
		$file = Blocks::app()->file->set(Blocks::app()->path->getBasePath().'../index.php', false);
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
		$manifestData = explode("\n", $manifestFile->getContents());

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
			$backupFile = Blocks::app()->file->set(Blocks::app()->path->getBasePath().'../'.$rowData[1].'.bak');
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
		$tempDir = UpdateHelper::getTempDirForPackage($downloadPath);
		$tempDir->exists ? $tempDir->delete() : $tempDir->createDir(0754);

		$downloadPath = Blocks::app()->file->set($downloadPath);
		if ($downloadPath->unzip($tempDir->getRealPath()))
			return true;

		return false;
	}

	public function backupFiles($masterManifest)
	{
		$manifestData = explode("\r\n", $masterManifest->getContents());

		try
		{
			foreach ($manifestData as $row)
			{
				$rowData = explode(';', $row);
				$file = Blocks::app()->file->set(Blocks::app()->path->getBasePath().'../'.$rowData[1]);

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
