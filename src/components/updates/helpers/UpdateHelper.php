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
			if (static::isManifestVersionInfoLine($row))
			{
				continue;
			}

			if (static::isManifestMigrationLine($row))
			{
				continue;
			}

			$rowData = explode(';', $row);
			$file = IOHelper::normalizePathSeparators(blx()->path->getAppPath().$rowData[0]);

			if (IOHelper::fileExists($file.'.bak'))
			{
				IOHelper::rename($file.'.bak', $file);
			}
		}
	}

	/**
	 * @param $backupPath
	 */
	public static function rollBackDatabaseChanges($backupPath)
	{
		$dbBackup = new DbBackup();
		$dbBackup->restore($backupPath);
	}

	/**
	 * @static
	 *
	 * @param $manifestData
	 * @param $sourceTempFolder
	 * @return bool
	 * @return bool
	 */
	public static function doFileUpdate($manifestData, $sourceTempFolder)
	{
		try
		{
			foreach ($manifestData as $row)
			{
				if (static::isManifestVersionInfoLine($row))
				{
					continue;
				}

				if (static::isManifestMigrationLine($row))
				{
					continue;
				}

				$rowData = explode(';', $row);

				$destFile = IOHelper::getRealPath(IOHelper::normalizePathSeparators(blx()->path->getAppPath().$rowData[0]));

				if (!$destFile)
				{
					$destFile = IOHelper::normalizePathSeparators(blx()->path->getAppPath().$rowData[0]);
				}

				$sourceFile = IOHelper::getRealPath(IOHelper::normalizePathSeparators($sourceTempFolder.'/app/'.$rowData[0]));

				switch (trim($rowData[1]))
				{
					// update the file
					case PatchManifestFileAction::Add:
					{
						Blocks::log('Updating file: '.$destFile, \CLogger::LEVEL_INFO);
						IOHelper::copyFile($sourceFile, $destFile);
						break;
					}

					case PatchManifestFileAction::Remove:
					{
						// rename in case we need to rollback.  the cleanup will remove the backup files.
						if ($destFile)
						{
							Blocks::log('Renaming file for delete: '.$destFile, \CLogger::LEVEL_INFO);
							IOHelper::rename($destFile, $destFile.'.bak');
						}

						break;
					}

					default:
					{
						Blocks::log('Unknown PatchManifestFileAction', \CLogger::LEVEL_ERROR);
						UpdateHelper::rollBackFileChanges($manifestData);
						return false;
					}
				}
			}
		}
		catch (\Exception $e)
		{
			Blocks::log('Error updating files: '.$e->getMessage(), \CLogger::LEVEL_ERROR);
			UpdateHelper::rollBackFileChanges($manifestData);
			return false;
		}

		return true;
	}

	/**
	 * @static
	 * @param $line
	 * @return bool
	 */
	public static function isManifestVersionInfoLine($line)
	{
		if ($line[0] == '#' && $line[1] == '#')
		{
			return true;
		}

		return false;
	}

	/**
	 * @static
	 * @param $line
	 * @return bool
	 */
	public static function isManifestMigrationLine($line)
	{
		if (strpos($line, 'migrations/') !== false)
		{
			return true;
		}

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
		$manifestFileData = IOHelper::getFileContents($manifestDataPath.'/blocks_manifest', true);

		// Remove any trailing empty newlines
		if ($manifestFileData[count($manifestFileData) - 1] == '')
		{
			array_pop($manifestFileData);
		}

		$manifestFileData = array_map('trim', $manifestFileData);

		return $manifestFileData;
	}

	/**
	 * @static
	 * @param $filePath
	 * @return string
	 */
	public static function copyMigrationFile($filePath)
	{
		$migrationFile = IOHelper::getFile($filePath);
		$destinationFile = blx()->path->getMigrationsPath().$migrationFile->getFileName();
		$migrationFile->copy($destinationFile);

		return $destinationFile;
	}
}
