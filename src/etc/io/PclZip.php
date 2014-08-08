<?php
namespace Craft;

/**
 * Class PclZip
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://buildwithcraft.com/license Craft License Agreement
 * @see       http://buildwithcraft.com
 * @package   craft.app.etc.io
 * @since     1.0
 */
class PclZip implements IZip
{
	// Public Methods
	// =========================================================================

	/**
	 * @param $sourceFolder
	 * @param $destZip
	 *
	 * @return bool
	 */
	public function zip($sourceFolder, $destZip)
	{
		$zip = new \PclZip($destZip);
		$result = $zip->create($sourceFolder, PCLZIP_OPT_REMOVE_PATH, $sourceFolder);

		if ($result == 0)
		{
			Craft::log('Unable to create zip file: '.$destZip, LogLevel::Error);
			return false;
		}

		return true;
	}

	/**
	 * @param $srcZip
	 * @param $destFolder
	 *
	 * @return bool
	 */
	public function unzip($srcZip, $destFolder)
	{
		$zip = new \PclZip($srcZip);
		$tempDestFolders = null;

		// check to see if it's a valid archive.
		if (($zipFiles = $zip->extract(PCLZIP_OPT_EXTRACT_AS_STRING)) == false)
		{
			Craft::log('Tried to unzip '.$srcZip.', but PclZip thinks it is not a valid zip archive.', LogLevel::Error);
			return false;
		}

		if (count($zipFiles) == 0)
		{
			Craft::log($srcZip.' appears to be an empty zip archive.', LogLevel::Error);
			return false;
		}

		// find out which directories we need to create in the destination.
		foreach ($zipFiles as $zipFile)
		{
			if (substr($zipFile['filename'], 0, 9) === '__MACOSX/')
			{
				continue;
			}

			$folderName = IOHelper::getFolderName($zipFile['filename']);
			if ($folderName == './')
			{
				$tempDestFolders[] = $destFolder.'/';
			}
			else
			{
				$tempDestFolders[] = $destFolder.'/'.rtrim(IOHelper::getFolderName($zipFile['filename']), '/');
			}
		}

		$tempDestFolders = array_unique($tempDestFolders);
		$finalDestFolders = array();

		foreach ($tempDestFolders as $tempDestFolder)
		{
			// Skip over the working directory
			if (rtrim($destFolder, '/') == rtrim($tempDestFolder, '/'))
			{
				continue;
			}

			// Make sure the current directory is within the working directory
			if (strpos($tempDestFolder, $destFolder) === false)
			{
				continue;
			}

			$finalDestFolders[] = $tempDestFolder;
		}

		asort($finalDestFolders);

		// Create the destination directories.
		foreach ($finalDestFolders as $finalDestFolder)
		{
			if (!IOHelper::folderExists($finalDestFolder))
			{
				if (!IOHelper::createFolder($finalDestFolder))
				{
					Craft::log('Could not create folder '.$finalDestFolder.' while unzipping: '.$srcZip, LogLevel::Error);
					return false;
				}
			}
		}

		unset($finalDestFolders);

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

			if (!IOHelper::writeToFile($destFile, $zipFile['content'], true, true))
			{
				Craft::log('Could not copy the file '.$destFile.' while unziping: '.$srcZip, LogLevel::Error);
				return false;
			}
		}

		return true;
	}

	/**
	 * Will add either a file or a folder to an existing zip file.  If it is a
	 * folder, it will add the contents recursively.
	 *
	 * @param string $sourceZip  The zip file to be added to.
	 * @param string $pathToAdd  A file or a folder to add.  If it is a folder,
	 *                           it will recursively add the contents of the folder
	 *                           to the zip.
	 * @param string $basePath   The root path of the file(s) to be added that
	 *                           will be removed before adding.
	 * @param string $pathPrefix A path to be prepended to each file before it
	 *                           is added to the zip.
	 *
	 * @return bool
	 */
	public function add($sourceZip, $pathToAdd, $basePath, $pathPrefix = null)
	{
		$zip = new \PclZip($sourceZip);

		if (IOHelper::fileExists($pathToAdd))
		{
			$folderContents = array($pathToAdd);
		}
		else
		{
			$folderContents = IOHelper::getFolderContents($pathToAdd, true);
		}

		$filesToAdd = array();

		foreach ($folderContents as $itemToZip)
		{
			if (IOHelper::isReadable($itemToZip))
			{
				if ((IOHelper::folderExists($itemToZip) && IOHelper::isFolderEmpty($itemToZip)) || IOHelper::fileExists($itemToZip))
				{
					$filesToAdd[] = $itemToZip;
				}
			}
		}

		if (!$pathPrefix)
		{
			$pathPrefix = '';
		}

		$result = $zip->add($filesToAdd, PCLZIP_OPT_ADD_PATH, $pathPrefix, PCLZIP_OPT_REMOVE_PATH, $basePath);

		if ($result == 0)
		{
			Craft::log('Unable to add to zip file: '.$sourceZip, LogLevel::Error);
			return false;
		}

		return true;
	}
}
