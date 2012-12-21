<?php
namespace Blocks;

/**
 *
 */
class Zip
{
	/**
	 * @static
	 * @param $source
	 * @param $destZip
	 * @return bool 'true' if the zip was successfully create, 'false' if not.
	 */
	public static function compress($source, $destZip)
	{
		$source = IOHelper::normalizePathSeparators($source);
		$destZip = IOHelper::normalizePathSeparators($destZip);

		if (!IOHelper::folderExists($source) && !IOHelper::fileExists($destZip))
		{
			Blocks::log('Tried to zip the contents of '.$source.' to '.$destZip.', but the source path does not exist.', \CLogger::LEVEL_ERROR);
			return false;
		}

		if (IOHelper::fileExists($destZip))
		{
			IOHelper::deleteFile($destZip);
		}

		IOHelper::createFile($destZip);

		@ini_set('memory_limit', '256M');

		$zip = static::_getZipInstance($destZip);
		return $zip->zip(IOHelper::getRealPath($source), IOHelper::getRealPath($destZip));
	}

	/**
	 * @static
	 * @param $srcZip
	 * @param $destFolder
	 * @return bool
	 */
	public static function unzip($srcZip, $destFolder)
	{
		@ini_set('memory_limit', '256M');

		if (IOHelper::fileExists($srcZip))
		{
			if (IOHelper::getExtension($srcZip) == 'zip')
			{
				$zip = static::_getZipInstance($srcZip);
				$result = $zip->unzip($srcZip, $destFolder);

				if ($result === true)
				{
					return $result;
				}
				else
				{
					Blocks::log('There was an error unzipping the file: '.$srcZip, \CLogger::LEVEL_ERROR);
					return false;
				}
			}
			else
			{
				Blocks::log($srcZip.' is not a zip file and cannot be unzipped.', \CLogger::LEVEL_ERROR);
				return false;
			}
		}
		else
		{
			Blocks::log('Unzipping is only available for files.', \CLogger::LEVEL_ERROR);
			return false;
		}
	}

	/**
	 * @param      $sourceZip
	 * @param      $pathToAdd
	 * @param      $basePath
	 * @param null $pathPrefix
	 * @return bool
	 */
	public static function add($sourceZip, $pathToAdd, $basePath, $pathPrefix = null)
	{
		$sourceZip = IOHelper::normalizePathSeparators($sourceZip);
		$pathToAdd = IOHelper::normalizePathSeparators($pathToAdd);
		$basePath = IOHelper::normalizePathSeparators($basePath);

		if (!IOHelper::fileExists($sourceZip) || (!IOHelper::fileExists($pathToAdd) && !IOHelper::folderExists($pathToAdd)))
		{
			Blocks::log('Tried to add '.$pathToAdd.' to the zip file '.$sourceZip.', but one of them does not exist.', \CLogger::LEVEL_ERROR);
			return false;
		}

		@ini_set('memory_limit', '256M');

		$zip = static::_getZipInstance($sourceZip);
		if ($zip->add($sourceZip, $pathToAdd, $basePath, $pathPrefix))
		{
			return true;
		}

		return false;
	}

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
