<?php
namespace Craft;

/**
 * Helper class for updating.
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://craftcms.com/license Craft License Agreement
 * @see       http://craftcms.com
 * @package   craft.app.helpers
 * @since     1.0
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
	 * @param $handle
	 *
	 * @return null
	 */
	public static function rollBackFileChanges($manifestData, $handle)
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

			if ($handle == 'craft')
			{
				$directory = craft()->path->getAppPath();
			}
			else
			{
				$directory = craft()->path->getPluginsPath().$handle.'/';
			}

			$file = IOHelper::normalizePathSeparators($directory.$rowData[0]);

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
		$fileName = $backupPath.'.sql';
		$fullBackupPath = craft()->path->getDbBackupPath().$fileName;

		if (PathHelper::ensurePathIsContained($fileName))
		{
			$dbBackup->restore($fullBackupPath);
		}
		else
		{
			Craft::log('Someone tried to restore a database from outside of the Craft backups folder: '.$fullBackupPath, LogLevel::Warning);
		}
	}

	/**
	 * @param $manifestData
	 * @param $sourceTempFolder
	 * @param $handle
	 *
	 * @return bool
	 */
	public static function doFileUpdate($manifestData, $sourceTempFolder, $handle)
	{
		if ($handle == 'craft')
		{
			$destDirectory = craft()->path->getAppPath();
			$sourceFileDirectory = 'app/';
		}
		else
		{
			$destDirectory = craft()->path->getPluginsPath().$handle.'/';
			$sourceFileDirectory = '';
		}

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

				$destFile = IOHelper::normalizePathSeparators($destDirectory.$tempPath);
				$sourceFile = IOHelper::getRealPath(IOHelper::normalizePathSeparators($sourceTempFolder.'/'.$sourceFileDirectory.$tempPath));

				switch (trim($rowData[1]))
				{
					// update the file
					case PatchManifestFileAction::Add:
					{
						if ($folder)
						{
							Craft::log('Updating folder: '.$destFile, LogLevel::Info, true);

							// Invalidate any existing files
							if (function_exists('opcache_invalidate') && IOHelper::folderExists($destFile))
							{
								$iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($destFile));

								foreach ($iterator as $oldFile)
								{
									/** @var \SplFileInfo $file */
									if ($oldFile->isFile())
									{
										opcache_invalidate($oldFile, true);
									}
								}
							}

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

							// Invalidate opcache
							if (function_exists('opcache_invalidate') && IOHelper::fileExists($destFile))
							{
								opcache_invalidate($destFile, true);
							}

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
			UpdateHelper::rollBackFileChanges($manifestData, $handle);
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
		return strncmp($line, '##', 2) === 0;
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
		if (!static::isManifestVersionInfoLine($manifestData[0]))
		{
			return false;
		}

		preg_match('/^##(.*);/', $manifestData[0], $matches);

		return $matches[1];
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
		if (mb_strpos($line, 'migrations/') !== false)
		{
			return true;
		}

		return false;
	}

	/**
	 * Returns the relevant lines from the update manifest file starting with the current local version.
	 *
	 * @param $manifestDataPath
	 * @param $handle
	 *
	 * @throws Exception
	 * @return array
	 */
	public static function getManifestData($manifestDataPath, $handle)
	{
		if (static::$_manifestData == null)
		{
			if (IOHelper::fileExists($manifestDataPath.'/'.$handle.'_manifest'))
			{
				// get manifest file
				$manifestFileData = IOHelper::getFileContents($manifestDataPath.'/'.$handle.'_manifest', true);

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

				$localVersion = null;

				if ($handle == 'craft')
				{
					$localVersion = $updateModel->app->localVersion;
				}
				else
				{
					foreach ($updateModel->plugins as $plugin)
					{
						if (strtolower($plugin->class) == $handle)
						{
							$localVersion = $plugin->localVersion;
							break;
						}
					}
				}

				// Only use the manifest data starting from the local version
				for ($counter = 0; $counter < count($manifestData); $counter++)
				{
					if (mb_strpos($manifestData[$counter], '##'.$localVersion) !== false)
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
		return craft()->path->getTempPath().$uid.'/';
	}

	/**
	 * @param $uid
	 *
	 * @return string
	 */
	public static function getZipFileFromUID($uid)
	{
		return craft()->path->getTempPath().$uid.'.zip';
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
