<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2015 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\io;

use Craft;
use craft\app\helpers\IOHelper;

/**
 * Class Zip
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
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
			Craft::error('Tried to zip the contents of '.$source.' to '.$destZip.', but the source path does not exist.', __METHOD__);
			return false;
		}

		if (IOHelper::fileExists($destZip))
		{
			IOHelper::deleteFile($destZip);
		}

		IOHelper::createFile($destZip);

		Craft::$app->getConfig()->maxPowerCaptain();

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
		Craft::$app->getConfig()->maxPowerCaptain();

		if (IOHelper::fileExists($srcZip))
		{
			if (IOHelper::getExtension($srcZip) == 'zip')
			{
				if (!IOHelper::folderExists($destFolder))
				{
					if (!IOHelper::createFolder($destFolder))
					{
						Craft::error('Tried to create the unzip destination folder, but could not: '.$destFolder, __METHOD__);
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
							Craft::error('Tried to clear the contents of the unzip destination folder, but could not: '.$destFolder, __METHOD__);
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
					Craft::error('There was an error unzipping the file: '.$srcZip, __METHOD__);
					return false;
				}
			}
			else
			{
				Craft::error($srcZip.' is not a zip file and cannot be unzipped.', __METHOD__);
				return false;
			}
		}
		else
		{
			Craft::error('Unzipping is only available for files.', __METHOD__);
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
			Craft::error('Tried to add '.$pathToAdd.' to the zip file '.$sourceZip.', but one of them does not exist.', __METHOD__);
			return false;
		}

		Craft::$app->getConfig()->maxPowerCaptain();

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
