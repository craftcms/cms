<?php
namespace Craft;

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
			$file = IOHelper::normalizePathSeparators(craft()->path->getAppPath().$rowData[0]);

			// It's a folder
			if (static::isManifestLineAFolder($file))
			{
				$folderPath = static::cleanManifestFolderLine($file);

				if (IOHelper::folderExists($folderPath.'.bak'))
				{
					IOHelper::rename($folderPath, $folderPath.'-tmp');
					IOHelper::rename($folderPath.'.bak', $folderPath);
					IOHelper::clearFolder($folderPath.'-tmp');
					IOHelper::deleteFolder($folderPath.'-tmp');
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
		$fullBackupPath= craft()->path->getDbBackupPath().$backupPath.'.sql';
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

				$destFile = IOHelper::normalizePathSeparators(craft()->path->getAppPath().$tempPath);
				$sourceFile = IOHelper::getRealPath(IOHelper::normalizePathSeparators($sourceTempFolder.'/app/'.$tempPath));

				switch (trim($rowData[1]))
				{
					// update the file
					case PatchManifestFileAction::Add:
					{
						if ($folder)
						{
							Craft::log('Updating folder: '.$destFile, LogLevel::Info, true);

							$tempFolder = rtrim($destFile, '/').StringHelper::UUID().'/';
							$tempTempFolder = rtrim($destFile, '/').'-tmp/';

							IOHelper::createFolder($tempFolder);
							IOHelper::copyFolder($sourceFile, $tempFolder);
							IOHelper::rename($destFile, $tempTempFolder);
							IOHelper::rename($tempFolder, $destFile);
							IOHelper::clearFolder($tempTempFolder);
							IOHelper::deleteFolder($tempTempFolder);
						}
						else
						{
							Craft::log('Updating file: '.$destFile, LogLevel::Info, true);
							IOHelper::copyFile($sourceFile, $destFile);
						}

						break;
					}
				}
			}
		}
		catch (\Exception $e)
		{
			Craft::log('Error updating files: '.$e->getMessage(), LogLevel::Error);
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
	 * Returns the local build number from the given manifest file.
	 *
	 * @param $manifestData
	 * @return bool|string
	 */
	public static function getLocalBuildFromManifest($manifestData)
	{
		if (static::isManifestVersionInfoLine($manifestData[0]))
		{
			$parts = explode(';', $manifestData[0]);
			$index = strrpos($parts[0], '.');
			$version = substr($parts[0], $index + 1);
			return $version;
		}

		return false;
	}

	/**
	 * Returns the local version number from the given manifest file.
	 *
	 * @param $manifestData
	 * @return bool|string
	 */
	public static function getLocalVersionFromManifest($manifestData)
	{
		if (static::isManifestVersionInfoLine($manifestData[0]))
		{
			$parts = explode(';', $manifestData[0]);
			$index = strrpos($parts[0], '.');
			$build = substr($parts[0], 2, $index - 2);
			return $build;
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
	 * @throws Exception
	 * @return array
	 */
	public static function getManifestData($manifestDataPath)
	{
		if (static::$_manifestData == null)
		{
			// get manifest file
			$manifestFileData = IOHelper::getFileContents($manifestDataPath.'/craft_manifest', true);

			if ($manifestFileData === false)
			{
				throw new Exception(Craft::t('There was a problem reading the update manifest data.'));
			}

			// Remove any trailing empty newlines
			if ($manifestFileData[count($manifestFileData) - 1] == '')
			{
				array_pop($manifestFileData);
			}

			$manifestData = array_map('trim', $manifestFileData);
			$updateModel = craft()->updates->getUpdates();

			// Only use the manifest data starting from the local version
			for ($counter = 0; $counter < count($manifestData); $counter++)
			{
				if (strpos($manifestData[$counter], '##'.$updateModel->app->localVersion.'.'.$updateModel->app->localBuild) !== false)
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
	 * @param $uid
	 * @return string
	 */
	public static function getUnzipFolderFromUID($uid)
	{
		return craft()->path->getTempPath().$uid.'/';
	}

	/**
	 * @param $uid
	 * @return string
	 */
	public static function getZipFileFromUID($uid)
	{
		return craft()->path->getTempPath().$uid.'.zip';
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
