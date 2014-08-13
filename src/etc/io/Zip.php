<?php
namespace Craft;

/**
 * Class Zip
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://buildwithcraft.com/license Craft License Agreement
 * @see       http://buildwithcraft.com
 * @package   craft.app.etc.io
 * @since     1.0
 */
class Zip
{
	// Protected Methods
	// =========================================================================

	/**
	 * @param $source
	 * @param $destZip
	 *
	 * @return bool 'true' if the zip was successfully created, 'false' if not.
	 */
	public static function compress($source, $destZip)
	{
		$source = IOHelper::normalizePathSeparators($source);
		$destZip = IOHelper::normalizePathSeparators($destZip);

		if (!IOHelper::folderExists($source) && !IOHelper::fileExists($destZip))
		{
			Craft::log('Tried to zip the contents of '.$source.' to '.$destZip.', but the source path does not exist.', LogLevel::Error);
			return false;
		}

		if (IOHelper::fileExists($destZip))
		{
			IOHelper::deleteFile($destZip);
		}

		IOHelper::createFile($destZip);

		craft()->config->maxPowerCaptain();

		$zip = static::_getZipInstance($destZip);
		return $zip->zip(IOHelper::getRealPath($source), IOHelper::getRealPath($destZip));
	}

	/**
	 * @param $srcZip
	 * @param $destFolder
	 *
	 * @return bool
	 */
	public static function unzip($srcZip, $destFolder)
	{
		craft()->config->maxPowerCaptain();

		if (IOHelper::fileExists($srcZip))
		{
			if (IOHelper::getExtension($srcZip) == 'zip')
			{
				if (!IOHelper::folderExists($destFolder))
				{
					if (!IOHelper::createFolder($destFolder))
					{
						Craft::log('Tried to create the unzip destination folder, but could not: '.$destFolder, LogLevel::Error);
						return false;
					}
				}
				else
				{
					// If the destination folder exists and it has contents, clear them.
					if (($conents = IOHelper::getFolderContents($destFolder)) !== false)
					{
						// Begin the great purge.
						if (!IOHelper::clearFolder($destFolder))
						{
							Craft::log('Tried to clear the contents of the unzip destination folder, but could not: '.$destFolder, LogLevel::Error);
							return false;
						}
					}

				}

				$zip = static::_getZipInstance($srcZip);
				$result = $zip->unzip($srcZip, $destFolder);

				if ($result === true)
				{
					return $result;
				}
				else
				{
					Craft::log('There was an error unzipping the file: '.$srcZip, LogLevel::Error);
					return false;
				}
			}
			else
			{
				Craft::log($srcZip.' is not a zip file and cannot be unzipped.', LogLevel::Error);
				return false;
			}
		}
		else
		{
			Craft::log('Unzipping is only available for files.', LogLevel::Error);
			return false;
		}
	}

	/**
	 * @param      $sourceZip
	 * @param      $pathToAdd
	 * @param      $basePath
	 * @param null $pathPrefix
	 *
	 * @return bool
	 */
	public static function add($sourceZip, $pathToAdd, $basePath, $pathPrefix = null)
	{
		$sourceZip = IOHelper::normalizePathSeparators($sourceZip);
		$pathToAdd = IOHelper::normalizePathSeparators($pathToAdd);
		$basePath = IOHelper::normalizePathSeparators($basePath);

		if (!IOHelper::fileExists($sourceZip) || (!IOHelper::fileExists($pathToAdd) && !IOHelper::folderExists($pathToAdd)))
		{
			Craft::log('Tried to add '.$pathToAdd.' to the zip file '.$sourceZip.', but one of them does not exist.', LogLevel::Error);
			return false;
		}

		craft()->config->maxPowerCaptain();

		$zip = static::_getZipInstance($sourceZip);

		if ($zip->add($sourceZip, $pathToAdd, $basePath, $pathPrefix))
		{
			return true;
		}

		return false;
	}

	// Private Methods
	// =========================================================================

	/**
	 * @return PclZip|ZipArchive
	 */
	private static function _getZipInstance()
	{
		if (class_exists('ZipArchive'))
		{
			return new ZipArchive();
		}

		return new PclZip();
	}
}
