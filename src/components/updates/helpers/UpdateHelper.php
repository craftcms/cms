<?php
namespace Blocks;

/**
 *
 */
class UpdateHelper
{
	private static $_manifestData;

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

			// It's a folder
			if (static::isManifestLineAFolder($file))
			{
				$folderPath = static::cleanManifestFolderLine($file);

				if (IOHelper::folderExists($folderPath.'.bak'))
				{
					IOHelper::clearFolder($folderPath);
					IOHelper::copyFolder($folderPath.'.bak/', $folderPath.'/');
				}
			}
			// It's a file.
			else
			{
				if (IOHelper::fileExists($file.'.bak'))
				{
					IOHelper::rename($file.'.bak', $file);
				}
			}
		}
	}

	/**
	 * @param $backupPath
	 */
	public static function rollBackDatabaseChanges($backupPath)
	{
		$dbBackup = new DbBackup();
		$fullBackupPath= blx()->path->getDbBackupPath().$backupPath.'.sql';
		$dbBackup->restore($fullBackupPath);
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

				$folder = false;
				$rowData = explode(';', $row);

				if (static::isManifestLineAFolder($rowData[0]))
				{
					$folder = true;
					$tempPath = static::cleanManifestFolderLine($rowData[0]);
				}
				else
				{
					$tempPath = $rowData[0];
				}

				$destFile = IOHelper::normalizePathSeparators(blx()->path->getAppPath().$tempPath);
				$sourceFile = IOHelper::getRealPath(IOHelper::normalizePathSeparators($sourceTempFolder.'/app/'.$tempPath));

				switch (trim($rowData[1]))
				{
					// update the file
					case PatchManifestFileAction::Add:
					{
						if ($folder)
						{
							Blocks::log('Updating folder: '.$destFile, \CLogger::LEVEL_INFO);

							$tempFolder = rtrim($destFile, '/').StringHelper::UUID();
							IOHelper::rename($destFile, $tempFolder);
							IOHelper::copyFolder($sourceFile, $destFile);
							IOHelper::deleteFolder($tempFolder);
						}
						else
						{
							Blocks::log('Updating file: '.$destFile, \CLogger::LEVEL_INFO);
							IOHelper::copyFile($sourceFile, $destFile);
						}

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
	 * Returns the relevant lines from the update manifest file starting with the current local version/build.
	 *
	 * @static
	 * @param $manifestDataPath
	 * @return array
	 */
	public static function getManifestData($manifestDataPath)
	{
		if (static::$_manifestData == null)
		{
			// get manifest file
			$manifestFileData = IOHelper::getFileContents($manifestDataPath.'/blocks_manifest', true);

			// Remove any trailing empty newlines
			if ($manifestFileData[count($manifestFileData) - 1] == '')
			{
				array_pop($manifestFileData);
			}

			$manifestData = array_map('trim', $manifestFileData);
			$updateModel = blx()->updates->getUpdates();

			// Only use the manifest data starting from the local version
			for ($counter = 0; $counter < count($manifestData); $counter++)
			{
				if (strpos($manifestData[$counter], '##'.$updateModel->blocks->localVersion.'.'.$updateModel->blocks->localBuild) !== false)
				{
					break;
				}
			}

			$manifestData = array_slice($manifestData, $counter);
			static::$_manifestData = $manifestData;
		}

		return static::$_manifestData;
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

	/**
	 * @param $uid
	 * @return string
	 */
	public static function getUnzipFolderFromUID($uid)
	{
		return blx()->path->getTempPath().$uid.'/';
	}

	/**
	 * @param $uid
	 * @return string
	 */
	public static function getZipFileFromUID($uid)
	{
		return blx()->path->getTempPath().$uid.'.zip';
	}

	/**
	 * @param $line
	 * @return bool
	 */
	public static function isManifestLineAFolder($line)
	{
		if (substr($line, -1) == '*')
		{
			return true;
		}

		return false;
	}

	/**
	 * @param $line
	 * @return string
	 */
	public static function cleanManifestFolderLine($line)
	{
		$line = rtrim($line, '*');
		return rtrim($line, '/');
	}
}
