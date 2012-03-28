<?php
namespace Blocks;

/**
 *
 */
class UpdateHelper
{
	/**
	 * @static
	 * @param $manifestData
	 */
	public static function rollBackFileChanges($manifestData)
	{
		foreach ($manifestData as $row)
		{
			if (self::isManifestVersionInfoLine($row))
				continue;

			$rowData = explode(';', $row);
			$file = b()->file->set(b()->path->appPath.'../../'.$rowData[0].'.bak');

			if ($file->exists)
				$file->rename(b()->path->appPath.'../../'.$rowData[0]);
		}
	}

	/**
	 * @static
	 *
	 * @param $manifestData
	 * @param $sourceTempDir
	 * @return bool
	 */
	public static function doFileUpdate($manifestData, $sourceTempDir)
	{
		try
		{
			foreach ($manifestData as $row)
			{
				if (self::isManifestVersionInfoLine($row))
					continue;

				$rowData = explode(';', $row);

				$destFile = b()->file->set(b()->path->appPath.'../../'.$rowData[0]);
				$sourceFile = b()->file->set($sourceTempDir->realPath.'/'.$rowData[0]);

				switch (trim($rowData[1]))
				{
					// update the file
					case PatchManifestFileAction::Add:
						Blocks::log('Updating file: '.$destFile->realPath);
						$sourceFile->copy($destFile->realPath, true);
						break;

					case PatchManifestFileAction::Remove:
						// rename in case we need to rollback.  the cleanup will remove the backup files.
						Blocks::log('Renaming file for delete: '.$destFile->realPath);
						$destFile->rename($destFile->realPath.'.bak');
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
			UpdateHelper::rollBackFileChanges($manifestData);
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
	 * @throws Exception
	 */
	public static function constructCoreReleasePatchFileName($version, $build, $edition)
	{
		if(StringHelper::isNullOrEmpty($version) || StringHelper::isNullOrEmpty($build) || StringHelper::isNullOrEmpty($edition))
			throw new Exception('Missing version, build or edition.');

		switch ($edition)
		{
			case Edition::Personal:
				return "blocks_personal_v{$version}.{$build}_patch.zip";

			case Edition::Pro:
				return "blocks_pro_v{$version}.{$build}_patch.zip";

			case Edition::Standard:
				return "blocks_standard_v{$version}.{$build}_patch.zip";
		}

		throw new Exception('Unknown Blocks Edition: '.$edition);
	}

	public static function isManifestVersionInfoLine($line)
	{
		if ($line[0] == '#' && $line[1] == '#')
			return true;

		return false;
	}

	/**
	 * @static
	 * @param $manifestDataPath
	 * @return array
	 */
	public static function getManifestData($manifestDataPath)
	{
		// get manifest file
		$manifestFile = b()->file->set($manifestDataPath.'/blocks_manifest');
		$manifestFileData = $manifestFile->contents;
		$manifestFileData = explode("\n", $manifestFileData);

		// Remove any trailing empty newlines
		if ($manifestFileData[count($manifestFileData) - 1] == '')
			array_pop($manifestFileData);

		return $manifestFileData;
	}

	/**
	 * @static
	 * @param $downloadPath
	 * @return mixed
	 */
	public static function getTempDirForPackage($downloadPath)
	{
		return b()->file->set(pathinfo($downloadPath, PATHINFO_DIRNAME).'/'.pathinfo($downloadPath, PATHINFO_FILENAME).'_temp');
	}

	/**
	 * @static
	 * @param $filePath
	 * @return string
	 */
	public static function copyMigrationFile($filePath)
	{
		$migrationFile = b()->file->set($filePath);
		$destinationFile = b()->path->migrationsPath.$migrationFile->baseName;
		$migrationFile->copy($destinationFile, true);
		return $destinationFile;
	}
}
