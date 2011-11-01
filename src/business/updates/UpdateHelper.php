<?php

class UpdateHelper
{
	public static function rollBackFileChanges($manifestFile)
	{
		$manifestData = explode("\n", $manifestFile->getContents());

		foreach ($manifestData as $row)
		{
			$rowData = explode(';', $row);
			$file = Blocks::app()->file->set(BLOCKS_BASE_PATH.'..'.DIRECTORY_SEPARATOR.$rowData[1].'.bak');

			if ($file->exists)
				$file->rename($rowData[1]);
		}
	}

	public static function doFileUpdate($masterManifest)
	{
		$manifestData = explode("\n", $masterManifest->getContents());

		try
		{
			foreach ($manifestData as $row)
			{
				$rowData = explode(';', $row);

				$destFile = Blocks::app()->file->set(BLOCKS_BASE_PATH.'..'.DIRECTORY_SEPARATOR.$rowData[1]);
				$sourceFile = Blocks::app()->file->set($rowData[0].DIRECTORY_SEPARATOR.$rowData[1]);

				switch (trim($rowData[2]))
				{
					// update the file
					case PatchManifestFileAction::Add:
						$sourceFile->copy($destFile->getRealPath(), true);
						break;

					case PatchManifestFileAction::Remove:
						// rename in case we need to rollback.  the cleanup will remove the backup files.
						$destFile->rename($destFile->getRealPath().'.bak');
						break;

					default:
						Blocks::log('Unknown PatchManifestFileAction');
						UpdateHelper::rollBackFileChanges($manifestData);
						return false;
				}
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

	public static function constructCoreReleasePatchFileName($version, $build, $edition)
	{
		if(StringHelper::IsNullOrEmpty($version) || StringHelper::IsNullOrEmpty($build) || StringHelper::IsNullOrEmpty($edition))
			throw new BlocksException('Missing versionNumber or buildNumber or edition.');

		switch ($edition)
		{
			case BlocksEdition::Personal:
				return BLOCKSBUILDS_PERSONAL_FILENAME.'v'.$version.'.'.$build.'_patch.zip';

			case BlocksEdition::Pro:
				return BLOCKSBUILDS_PRO_FILENAME.'v'.$version.'.'.$build.'_patch.zip';

			case BlocksEdition::Standard:
				return BLOCKSBUILDS_STANDARD_FILENAME.'v'.$version.'.'.$build.'_patch.zip';
		}

		throw new BlocksException('Unknown Blocks Edition: '.$edition);
	}

	public static function stripRootBlocksPath($path)
	{
		if (strpos($path, 'blocks') == 0)
			$path = substr($path, 7);

		return $path;
	}

	public static function getManifestData($manifestDataPath)
	{
		// get manifest file
		$manifestFile = Blocks::app()->file->set($manifestDataPath.DIRECTORY_SEPARATOR.'blocks_manifest');
		$manifestFileData = $manifestFile->getContents();
		return explode("\n", $manifestFileData);
	}

	public static function getTempDirForPackage($downloadPath)
	{
		$downloadPath = Blocks::app()->file->set($downloadPath);
		return Blocks::app()->file->set($downloadPath->getDirName().DIRECTORY_SEPARATOR.$downloadPath->getFileName().'_temp');
	}

	public static function copyMigrationFile($filePath)
	{
		$migrationFile = Blocks::app()->file->set($filePath);
		$destinationFile = Blocks::app()->getBasePath().DIRECTORY_SEPARATOR.'migrations'.DIRECTORY_SEPARATOR.$migrationFile->getBaseName();
		$migrationFile->copy($destinationFile, true);
		return $destinationFile;
	}

	public static function inManifestList(&$counter, $manifestDataRow, $fileList)
	{
		$found = false;
		for ($counter; $counter < count($fileList); $counter++)
		{
			$pieces = explode(';', $fileList[$counter]);
			if ($manifestDataRow === $pieces[1].';'.$pieces[2])
			{
				$found = true;
				break;
			}
		}

		return $found;
	}

}
