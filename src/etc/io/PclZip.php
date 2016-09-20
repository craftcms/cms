<?php
namespace Craft;

/**
 * Class PclZip
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://craftcms.com/license Craft License Agreement
 * @see       http://craftcms.com
 * @package   craft.app.etc.io
 * @since     1.0
 */
class PclZip implements IZip
{
	// Public Methods
	// =========================================================================

	/**
	 * @inheritDoc IZip::zip()
	 *
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
	 * @inheritDoc IZip::unzip()
	 *
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
	 * @inheritDoc IZip::add()
	 *
	 * @param string $sourceZip
	 * @param string $pathToAdd
	 * @param string $basePath
	 * @param null   $pathPrefix
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

		if ($folderContents)
		{
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
