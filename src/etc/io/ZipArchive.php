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
	 * Will add either a file or a folder to an existing zip file.  If it is a folder, it will add the contents recursively.
	 *
	 * @param string $sourceZip     The zip file to be added to.
	 * @param string $pathToAdd     A file or a folder to add.  If it is a folder, it will recursively add the contents of the folder to the zip.
	 * @param string $basePath      The root path of the file(s) to be added that will be removed before adding.
	 * @param string $pathPrefix    A path to be prepended to each file before it is added to the zip.
	 * @return bool
	 */
	public function add($sourceZip, $pathToAdd, $basePath, $pathPrefix = null)
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
		}

		foreach ($folderContents as $itemToZip)
		{
			if (IOHelper::isReadable($itemToZip))
			{
				// Figure out the relative path we'll be adding to the zip.
				$relFilePath = substr($itemToZip, strlen($basePath));

				if ($pathPrefix)
				{
					$pathPrefix = IOHelper::normalizePathSeparators($pathPrefix);
					$relFilePath = $pathPrefix.$relFilePath;
				}

				if (IOHelper::folderExists($itemToZip))
				{
					if (IOHelper::isFolderEmpty($itemToZip))
					{
						$zip->addEmptyDir($relFilePath);
					}
				}
				elseif (IOHelper::fileExists($itemToZip))
				{
					// We can't use $zip->addFile() here but it's a terrible, horrible, POS method that's buggy on Windows.
					$fileContents = IOHelper::getFileContents($itemToZip);

					if (!$zip->addFromString($relFilePath, $fileContents))
					{
						Blocks::log('There was an error adding the file '.$itemToZip.' to the zip: '.$itemToZip, \CLogger::LEVEL_ERROR);
					}
				}
			}
		}

		$zip->close();
		return true;
	}
}
