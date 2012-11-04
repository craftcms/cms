<?php
namespace Blocks;

/**
 *
 */
class ZipArchive implements IZip
{

	/**
	 * @param $sourceFolder
	 * @param $destZip
	 * @return bool
	 */
	public function zip($sourceFolder, $destZip)
	{
		$zip = new \ZipArchive();

		$zipContents = $zip->open($destZip, \ZipArchive::CREATE);

		if ($zipContents !== true)
		{
			Blocks::log('Unable to create zip file: '.$destZip, \CLogger::LEVEL_ERROR);
			return false;
		}

		return $this->add($destZip, $sourceFolder, $sourceFolder);
	}

	/**
	 * @param $srcZip
	 * @param $destFolder
	 * @return bool
	 */
	public function unzip($srcZip, $destFolder)
	{
		@ini_set('memory_limit', '256M');

		$zip = new \ZipArchive();
		$zipContents = $zip->open($srcZip, \ZipArchive::CHECKCONS);

		if ($zipContents !== true)
		{
			Blocks::log('Could not open the zip file: '.$srcZip, \CLogger::LEVEL_ERROR);
			return false;
		}

		for ($i = 0; $i < $zip->numFiles; $i++)
		{
			if (!$info = $zip->statIndex($i))
			{
				Blocks::log('Could not retrieve a file from the zip archive '.$srcZip, \CLogger::LEVEL_ERROR);
				return false;
			}

			// normalize directory separators
			$info = IOHelper::normalizePathSeparators($info['name']);

			// found a directory
			if (substr($info, -1) === '/')
			{
				IOHelper::createFolder($destFolder.'/'.$info);
				continue;
			}

			 // Don't extract the OSX __MACOSX directory
			if (substr($info, 0, 9) === '__MACOSX/')
			{
				continue;
			}

			$contents = $zip->getFromIndex($i);

			if ($contents === false)
			{
				Blocks::log('Could not extract file from zip archive '.$srcZip, \CLogger::LEVEL_ERROR);
				return false;
			}

			if (!IOHelper::writeToFile($destFolder.'/'.$info, $contents, true, FILE_APPEND))
			{
				Blocks::log('Could not copy file to '.$destFolder.'/'.$info.' while unzipping from '.$srcZip, \CLogger::LEVEL_ERROR);
				return false;
			}
		}

		$zip->close();
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
		$zip = new \ZipArchive();
		$zipContents = $zip->open($sourceZip);

		if ($zipContents !== true)
		{
			Blocks::log('Unable to open zip file: '.$sourceZip, \CLogger::LEVEL_ERROR);
			return false;
		}

		if (IOHelper::fileExists($pathToAdd))
		{
			$folderContents = array($pathToAdd);
		}
		else
		{
			$folderContents = IOHelper::getFolderContents($pathToAdd, true);
			$basePath = IOHelper::getRealPath($pathToAdd);
		}

		foreach ($folderContents as $itemToZip)
		{
			if ((IOHelper::fileExists($itemToZip) || IOHelper::isReadable($itemToZip)) && !IOHelper::folderExists($itemToZip))
			{
				// We can't use $zip->addFile() here but it's a terrible, horrible, POS method that's buggy on Windows.
				$fileContents = IOHelper::getFileContents($itemToZip);
				$relFilePath = substr($itemToZip, strlen($basePath));

				if (!$zip->addFromString($relFilePath, $fileContents))
				{
					Blocks::log('There was an error adding the file '.$itemToZip.' to the zip: '.$itemToZip, \CLogger::LEVEL_ERROR);
				}
			}
		}

		$zip->close();
		return true;
	}
}
