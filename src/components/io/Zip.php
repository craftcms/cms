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
	public static function zip($source, $destZip)
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

		if (class_exists('ZipArchive', false))
		{
			return static::_zipZipArchive(IOHelper::getRealPath($source), IOHelper::getRealPath($destZip));
		}

		return static::_zipPclZip(IOHelper::getRealPath($source), IOHelper::getRealPath($destZip));
	}

	/**
	 * @static
	 * @param $srcZip
	 * @param $destFolder
	 * @return bool
	 */
	public static function unzip($srcZip, $destFolder)
	{
		if (IOHelper::fileExists($srcZip))
		{
			if (IOHelper::getExtension($srcZip == 'zip'))
			{
				if (class_exists('ZipArchive', false))
				{
					$result = static::_unzipZipArchive($srcZip, $destFolder);

					if ($result === true)
					{
						return $result;
					}
					else
					{
						Blocks::log('There was an error unzipping the file using ZipArchive: '.$srcZip, \CLogger::LEVEL_ERROR);
					}
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

		// Last chance, try PclZip
		return static::_unzipPclZip($srcZip, $destFolder);
	}

	/**
	 * @static
	 * @access private
	 * @param $source
	 * @param $destZip
	 * @return bool
	 */
	private static function _zipPclZip($source, $destZip)
	{
		$zip = new \PclZip($destZip);

		$result = $zip->create($source, PCLZIP_OPT_REMOVE_PATH, $source);

		if ($result == 0)
		{
			Blocks::log('Unable to create zip file: '.$destZip, \CLogger::LEVEL_ERROR);
			return false;
		}

		return true;
	}

	/**
	 * @static
	 * @access private
	 * @param $source
	 * @param $destZip
	 * @return bool
	 */
	private static function _zipZipArchive($source, $destZip)
	{
		$zip = new \ZipArchive;
		$zipContents = $zip->open($destZip, \ZipArchive::CREATE);

		if ($zipContents !== true)
		{
			Blocks::log('Unable to create zip file: '.$destZip, \CLogger::LEVEL_ERROR);
			return false;
		}

		if (IOHelper::fileExists($source))
		{
			$folderContents = array($source);
		}
		else
		{
			$folderContents = IOHelper::getFolderContents($source, true);
		}

		foreach ($folderContents as $itemToZip)
		{
			if ((IOHelper::fileExists($itemToZip) || IOHelper::isReadable($itemToZip)) && !IOHelper::folderExists($itemToZip))
			{
				// We can't use $zip->addFile() here but it's a terrible, horrible, POS method that's buggy on Windows.
				$fileContents = IOHelper::getFileContents($itemToZip);
				$relFilePath = substr($itemToZip, strlen(IOHelper::getRealPath($source)));

				if (!$zip->addFromString($relFilePath, $fileContents))
				{
					Blocks::log('There was an error adding the file '.$itemToZip.' to the zip: '.$itemToZip, \CLogger::LEVEL_ERROR);
				}
			}
		}

		$zip->close();
		return true;
	}

	/**
	 * @static
	 * @access private
	 * @param $srcZip
	 * @param $destFolder
	 * @return bool
	 */
	private static function _unzipPclZip($srcZip, $destFolder)
	{
		@ini_set('memory_limit', '256M');

		$zip = new \PclZip($srcZip);
		$destFolders = null;

		// check to see if it's a valid archive.
		if (($zipFiles = $zip->extract(PCLZIP_OPT_EXTRACT_AS_STRING)) == false)
		{
			Blocks::log('Tried to unzip '.$srcZip.', but PclZip thinks it is not a valid zip archive.', \CLogger::LEVEL_ERROR);
			return false;
		}

		if (count($zipFiles) == 0)
		{
			Blocks::log($srcZip.' appears to be an empty zip archive.', \CLogger::LEVEL_ERROR);
			return false;
		}

		// find out which directories we need to create in the destination.
		foreach ($zipFiles as $zipFile)
		{
			if (substr($zipFile['filename'], 0, 9) === '__MACOSX/')
			{
				continue;
			}

			$destFolders[] = $destFolder.'/'.rtrim($zipFile['folder'] ? $zipFile['filename'] : IOHelper::getFolderName($zipFile['filename']), '/');
		}

		$destFolders = array_unique($destFolders);

		foreach ($destFolders as $tempDestFolder)
		{
			// Skip over the working directory
			if (rtrim($destFolder, '/') == $tempDestFolder)
			{
				continue;
			}

			// Make sure the current directory is within the working directory
			if (strpos($tempDestFolder, $destFolder) === false)
			{
				continue;
			}

			$parentDirectory = IOHelper::getFolderName($tempDestFolder);

			while (!empty($parentDirectory) && rtrim($destFolder, '/') != $parentDirectory && !in_array($parentDirectory, $destFolders))
			{
				$destFolders[] = $parentDirectory;
				$parentDirectory = IOHelper::getFolderName($parentDirectory);
			}
		}

		asort($destFolders);

		// Create the destination directories.
		foreach ($destFolders as $tempDestFolder)
		{
			if (!IOHelper::createFolder($tempDestFolder))
			{
				Blocks::log('Could not create folder '.$tempDestFolder.' while unziping: '.$srcZip, \CLogger::LEVEL_ERROR);
				return false;
			}
		}

		unset($destFolders);

		// Extract the files from the zip
		foreach ($zipFiles as $zipFile)
		{
			// folders have already been created.
			if ($zipFile['folder'])
			{
				continue;
			}

			if (substr($zipFile['filename'], 0, 9) === '__MACOSX/')
			{
				continue;
			}

			$destFile = $destFolder.'/'.$zipFile['filename'];

			if (!IOHelper::writeToFile($destFile, $zipFile['content'], true, FILE_APPEND))
			{
				Blocks::log('Could not copy the file '.$destFile.' while unziping: '.$srcZip, \CLogger::LEVEL_ERROR);
				return false;
			}
		}

		return true;
	}

	/**
	 * @static
	 * @access private
	 * @param $srcZip
	 * @param $destFolder
	 * @return bool
	 */
	private static function _unzipZipArchive($srcZip, $destFolder)
	{
		@ini_set('memory_limit', '256M');
		$zipArchive = new \ZipArchive();

		$zipContents = $zipArchive->open($srcZip, \ZipArchive::CHECKCONS);

		if ($zipContents !== true)
		{
			Blocks::log('Could not open the zip file: '.$srcZip, \CLogger::LEVEL_ERROR);
			return false;
		}

		for ($i = 0; $i < $zipArchive->numFiles; $i++)
		{
			if (!$info = $zipArchive->statIndex($i))
			{
				Blocks::log('Could not retrieve a file from the zip archive '.$srcZip, \CLogger::LEVEL_ERROR);
				return false;
			}

			// normalize directory separators
			$info = IOHelper::normalizePathSeparators($info);

			// found a directory
			if (substr($info['name'], -1) === '/')
			{
				IOHelper::createFolder($destFolder.'/'.$info['name']);
				continue;
			}

			 // Don't extract the OSX __MACOSX directory
			if (substr($info['name'], 0, 9) === '__MACOSX/')
			{
				continue;
			}

			$contents = $zipArchive->getFromIndex($i);

			if ($contents === false)
			{
				Blocks::log('Could not extract file from zip archive '.$srcZip, \CLogger::LEVEL_ERROR);
				return false;
			}

			if (!IOHelper::writeToFile($destFolder.'/'.$info['name'], $contents, true, FILE_APPEND))
			{
				Blocks::log('Could not copy file to '.$destFolder.'/'.$info['name'].' while unzipping from '.$srcZip, \CLogger::LEVEL_ERROR);
				return false;
			}
		}

		$zipArchive->close();
		return true;
	}
}
