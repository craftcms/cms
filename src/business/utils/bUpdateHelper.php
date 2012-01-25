<?php

/**
 *
 */
class bUpdateHelper
{
	/**
	 * @static
	 * @param $manifestFile
	 */
	public static function rollBackFileChanges($manifestFile)
	{
		$manifestData = explode("\n", $manifestFile->contents);

		foreach ($manifestData as $row)
		{
			$rowData = explode(';', $row);
			$file = Blocks::app()->file->set(BLOCKS_BASE_PATH.'../'.$rowData[1].'.bak');

			if ($file->exists)
				$file->rename($rowData[1]);
		}
	}

	/**
	 * @static
	 * @param $masterManifest
	 * @return bool
	 */
	public static function doFileUpdate($masterManifest)
	{
		$manifestData = explode("\n", $masterManifest->contents);

		try
		{
			foreach ($manifestData as $row)
			{
				$rowData = explode(';', $row);

				$destFile = Blocks::app()->file->set(BLOCKS_BASE_PATH.'../'.$rowData[1]);
				$sourceFile = Blocks::app()->file->set($rowData[0].'/'.$rowData[1]);

				switch (trim($rowData[2]))
				{
					// update the file
					case bPatchManifestFileAction::Add:
						$sourceFile->copy($destFile->realPath, true);
						break;

					case bPatchManifestFileAction::Remove:
						// rename in case we need to rollback.  the cleanup will remove the backup files.
						$destFile->rename($destFile->realPath.'.bak');
						break;

					default:
						Blocks::log('Unknown bPatchManifestFileAction');
						bUpdateHelper::rollBackFileChanges($manifestData);
						return false;
				}
			}
		}
		catch (Exception $e)
		{
			Blocks::log('Error updating files: '.$e->getMessage());
			bUpdateHelper::rollBackFileChanges($masterManifest);
			return false;
		}

		return true;
	}

	/**
	 * @static
	 * @param $version
	 * @param $build
	 * @param $edition
	 * @return string
	 * @throws bException
	 */
	public static function constructCoreReleasePatchFileName($version, $build, $edition)
	{
		if(bStringHelper::isNullOrEmpty($version) || bStringHelper::isNullOrEmpty($build) || bStringHelper::isNullOrEmpty($edition))
			throw new bException('Missing version, build or edition.');

		switch ($edition)
		{
			case bEdition::Personal:
				return "blocks_personal_v{$version}.{$build}_patch.zip";

			case bEdition::Pro:
				return "blocks_pro_v{$version}.{$build}_patch.zip";

			case bEdition::Standard:
				return "blocks_standard_v{$version}.{$build}_patch.zip";
		}

		throw new bException('Unknown Blocks Edition: '.$edition);
	}

	/**
	 * @static
	 * @param $path
	 * @return string
	 */
	public static function stripRootBlocksPath($path)
	{
		if (strpos($path, 'blocks') == 0)
			$path = substr($path, 7);

		return $path;
	}

	/**
	 * @static
	 * @param $manifestDataPath
	 * @return array
	 */
	public static function getManifestData($manifestDataPath)
	{
		// get manifest file
		$manifestFile = Blocks::app()->file->set($manifestDataPath.'/blocks_manifest');
		$manifestFileData = $manifestFile->contents;
		return explode("\n", $manifestFileData);
	}

	/**
	 * @static
	 * @param $downloadPath
	 * @return mixed
	 */
	public static function getTempDirForPackage($downloadPath)
	{
		$downloadPath = Blocks::app()->file->set($downloadPath);
		return Blocks::app()->file->set($downloadPath->dirName.'/'.$downloadPath->fileName.'_temp');
	}

	/**
	 * @static
	 * @param $filePath
	 * @return string
	 */
	public static function copyMigrationFile($filePath)
	{
		$migrationFile = Blocks::app()->file->set($filePath);
		$destinationFile = Blocks::app()->path->migrationsPath.$migrationFile->baseName;
		$migrationFile->copy($destinationFile, true);
		return $destinationFile;
	}

	/**
	 * @static
	 * @param $counter
	 * @param $manifestDataRow
	 * @param $fileList
	 * @return bool
	 */
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
