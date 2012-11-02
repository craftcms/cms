<?php
namespace Blocks;

/**
 *
 */
class PclZip implements IZip
{
	/**
	 * @param $sourceFolder
	 * @param $destZip
	 * @return bool
	 */
	public function zip($sourceFolder, $destZip)
	{
		$zip = new \PclZip($destZip);
		$result = $zip->create($sourceFolder, PCLZIP_OPT_REMOVE_PATH, $sourceFolder);

		if ($result == 0)
		{
			Blocks::log('Unable to create zip file: '.$destZip, \CLogger::LEVEL_ERROR);
			return false;
		}

		return true;
	}

	/**
	 * @param $srcZip
	 * @param $destFolder
	 * @return bool
	 */
	public function unzip($srcZip, $destFolder)
	{
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
	 * @param $sourceZip
	 * @param $pathToAdd
	 * @param $basePath
	 * @return bool
	 */
	public function add($sourceZip, $pathToAdd, $basePath)
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
			if ((IOHelper::fileExists($itemToZip) || IOHelper::isReadable($itemToZip)) && !IOHelper::folderExists($itemToZip))
			{
				$filesToAdd[] = $itemToZip;
			}
		}

		$result = $zip->add($filesToAdd, PCLZIP_OPT_REMOVE_PATH, $basePath);

		if ($result == 0)
		{
			Blocks::log('Unable to add to zip file: '.$sourceZip, \CLogger::LEVEL_ERROR);
			return false;
		}

		return true;
	}
}
