<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2015 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\helpers;

use Craft;
use craft\app\db\DbBackup;
use craft\app\enums\PatchManifestFileAction;
use craft\app\errors\Exception;

/**
 * Class UpdateHelper
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class UpdateHelper
{
	// Properties
	// =========================================================================

	/**
	 * @var
	 */
	private static $_manifestData;

	// Public Methods
	// =========================================================================

	/**
	 * @param $manifestData
	 *
	 * @return null
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
			$file = IOHelper::normalizePathSeparators(Craft::$app->getPath()->getAppPath().'/'.$rowData[0]);

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
	 * Rolls back any changes made to the DB during the update process.
	 *
	 * @param $backupPath
	 *
	 * @return null
	 */
	public static function rollBackDatabaseChanges($backupPath)
	{
		$dbBackup = new DbBackup();
		$fullBackupPath = Craft::$app->getPath()->getDbBackupPath().'/'.$backupPath.'.sql';
		$dbBackup->restore($fullBackupPath);
	}

	/**
	 * @param $manifestData
	 * @param $sourceTempFolder
	 *
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

				$destFile = IOHelper::normalizePathSeparators(Craft::$app->getPath()->getAppPath().'/'.$tempPath);
				$sourceFile = IOHelper::getRealPath(IOHelper::normalizePathSeparators($sourceTempFolder.'/app/'.$tempPath));

				switch (trim($rowData[1]))
				{
					// update the file
					case PatchManifestFileAction::Add:
					{
						if ($folder)
						{
							Craft::info('Updating folder: '.$destFile, __METHOD__);

							$tempFolder = rtrim($destFile, '/').StringHelper::UUID();
							$tempTempFolder = rtrim($destFile, '/').'-tmp';

							IOHelper::createFolder($tempFolder);
							IOHelper::copyFolder($sourceFile, $tempFolder);
							IOHelper::rename($destFile, $tempTempFolder);
							IOHelper::rename($tempFolder, $destFile);
							IOHelper::clearFolder($tempTempFolder);
							IOHelper::deleteFolder($tempTempFolder);
						}
						else
						{
							Craft::info('Updating file: '.$destFile, __METHOD__);
							IOHelper::copyFile($sourceFile, $destFile);
						}

						break;
					}
				}
			}
		}
		catch (\Exception $e)
		{
			Craft::error('Error updating files: '.$e->getMessage(), __METHOD__);
			UpdateHelper::rollBackFileChanges($manifestData);
			return false;
		}

		return true;
	}

	/**
	 * @param $line
	 *
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
	 *
	 * @return bool|string
	 */
	public static function getLocalBuildFromManifest($manifestData)
	{
		if (static::isManifestVersionInfoLine($manifestData[0]))
		{
			$parts = explode(';', $manifestData[0]);
			$index = mb_strrpos($parts[0], '.');
			$version = mb_substr($parts[0], $index + 1);
			return $version;
		}

		return false;
	}

	/**
	 * Returns the local version number from the given manifest file.
	 *
	 * @param $manifestData
	 *
	 * @return bool|string
	 */
	public static function getLocalVersionFromManifest($manifestData)
	{
		if (static::isManifestVersionInfoLine($manifestData[0]))
		{
			$parts = explode(';', $manifestData[0]);
			$index = mb_strrpos($parts[0], '.');
			$build = mb_substr($parts[0], 2, $index - 2);

			return $build;
		}

		return false;
	}

	/**
	 * Return true if line is a manifest migration line.
	 *
	 * @param $line
	 *
	 * @return bool
	 */
	public static function isManifestMigrationLine($line)
	{
		if (StringHelper::contains($line, 'migrations/'))
		{
			return true;
		}

		return false;
	}

	/**
	 * Returns the relevant lines from the update manifest file starting with the current local version/build.
	 *
	 * @param $manifestDataPath
	 *
	 * @throws Exception
	 * @return array
	 */
	public static function getManifestData($manifestDataPath)
	{
		if (static::$_manifestData == null)
		{
			if (IOHelper::fileExists($manifestDataPath.'/craft_manifest'))
			{
				// get manifest file
				$manifestFileData = IOHelper::getFileContents($manifestDataPath.'/craft_manifest', true);

				if ($manifestFileData === false)
				{
					throw new Exception(Craft::t('app', 'There was a problem reading the update manifest data.'));
				}

				// Remove any trailing empty newlines
				if ($manifestFileData[count($manifestFileData) - 1] == '')
				{
					array_pop($manifestFileData);
				}

				$manifestData = array_map('trim', $manifestFileData);
				$updateModel = Craft::$app->getUpdates()->getUpdates();

				// Only use the manifest data starting from the local version
				for ($counter = 0; $counter < count($manifestData); $counter++)
				{
					if (StringHelper::contains($manifestData[$counter], '##'.$updateModel->app->localVersion.'.'.$updateModel->app->localBuild))
					{
						break;
					}
				}

				$manifestData = array_slice($manifestData, $counter);
				static::$_manifestData = $manifestData;
			}
		}

		return static::$_manifestData;
	}

	/**
	 * @param $uid
	 *
	 * @return string
	 */
	public static function getUnzipFolderFromUID($uid)
	{
		return Craft::$app->getPath()->getTempPath().'/'.$uid;
	}

	/**
	 * @param $uid
	 *
	 * @return string
	 */
	public static function getZipFileFromUID($uid)
	{
		return Craft::$app->getPath()->getTempPath().'/'.$uid.'.zip';
	}

	/**
	 * @param $line
	 *
	 * @return bool
	 */
	public static function isManifestLineAFolder($line)
	{
		if (mb_substr($line, -1) == '*')
		{
			return true;
		}

		return false;
	}

	/**
	 * @param $line
	 *
	 * @return string
	 */
	public static function cleanManifestFolderLine($line)
	{
		$line = rtrim($line, '*');
		return rtrim($line, '/');
	}
}
