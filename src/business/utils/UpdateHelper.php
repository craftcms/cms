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

			if (self::isManifestMigrationLine($row))
				continue;

			$rowData = explode(';', $row);
			$file = b()->file->set(b()->path->getAppPath().'../../'.$rowData[0].'.bak');

			if ($file->getExists())
				$file->rename(b()->path->getAppPath().'../../'.$rowData[0]);
		}
	}

	/**
	 * @static
	 *
	 * @param $manifestData
	 * @param $sourceTempDir
	 * @return bool
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

				if (self::isManifestMigrationLine($row))
					continue;

				$rowData = explode(';', $row);

				$destFile = b()->file->set(b()->path->getAppPath().'../../'.$rowData[0]);
				$sourceFile = b()->file->set($sourceTempDir->getRealPath().'/'.$rowData[0]);

				switch (trim($rowData[1]))
				{
					// update the file
					case PatchManifestFileAction::Add:
						Blocks::log('Updating file: '.$destFile->getRealPath());
						$sourceFile->copy($destFile->getRealPath(), true);
						break;

					case PatchManifestFileAction::Remove:
						// rename in case we need to rollback.  the cleanup will remove the backup files.
						Blocks::log('Renaming file for delete: '.$destFile->getRealPath());
						$destFile->rename($destFile->getRealPath().'.bak');
						break;

					default:
						Blocks::log('Unknown PatchManifestFileAction');
						UpdateHelper::rollBackFileChanges($manifestData);
						return false;
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

	/**
	 * @static
	 * @param $version
	 * @param $build
	 * @param $product
	 * @return string
	 * @throws Exception
	 */
	public static function constructAppReleasePatchFileName($version, $build, $product)
	{
		if(StringHelper::isNullOrEmpty($version) || StringHelper::isNullOrEmpty($build) || StringHelper::isNullOrEmpty($product))
			throw new Exception('Missing version, build or product.');

		switch ($product)
		{
			case Product::Blocks:
				return "Blocks{$version}.{$build}_patch.zip";

			case Product::BlocksPro:
				return "BlocksPro{$version}.{$build}_patch.zip";
		}

		throw new Exception('Unknown Blocks Product: '.$product);
	}

	/**
	 * @static
	 * @param $line
	 * @return bool
	 */
	public static function isManifestVersionInfoLine($line)
	{
		if ($line[0] == '#' && $line[1] == '#')
			return true;

		return false;
	}

	/**
	 * @static
	 * @param $line
	 * @return bool
	 */
	public static function isManifestMigrationLine($line)
	{
		if (strpos($line, '/migrations/') !== false)
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
		$manifestFileData = preg_split('/[\r\n]/', $manifestFileData);

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
		$destinationFile = b()->path->getMigrationsPath().$migrationFile->baseName;
		$migrationFile->copy($destinationFile, true);
		return $destinationFile;
	}
}
