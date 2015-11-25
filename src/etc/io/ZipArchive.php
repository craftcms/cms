<?php
namespace Craft;

/**
 * Class ZipArchive
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://craftcms.com/license Craft License Agreement
 * @see       http://craftcms.com
 * @package   craft.app.etc.io
 * @since     1.0
 */
class ZipArchive implements IZip
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
		$zip = new \ZipArchive();

		$zipContents = $zip->open($destZip, \ZipArchive::CREATE);

		if ($zipContents !== true)
		{
			Craft::log('Unable to create zip file: '.$destZip, LogLevel::Error);
			return false;
		}

		return $this->add($destZip, $sourceFolder, $sourceFolder);
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
		@ini_set('memory_limit', craft()->config->get('phpMaxMemoryLimit'));

		$zip = new \ZipArchive();
		$zipContents = $zip->open($srcZip, \ZipArchive::CHECKCONS);

		if ($zipContents !== true)
		{
			Craft::log('Could not open the zip file: '.$srcZip, LogLevel::Error);
			return false;
		}

		for ($i = 0; $i < $zip->numFiles; $i++)
		{
			if (!$info = $zip->statIndex($i))
			{
				Craft::log('Could not retrieve a file from the zip archive '.$srcZip, LogLevel::Error);
				return false;
			}

			// normalize directory separators
			$info = IOHelper::normalizePathSeparators($info['name']);

			// found a directory
			if (mb_substr($info, -1) === '/')
			{
				IOHelper::createFolder($destFolder.'/'.$info);
				continue;
			}

			 // Don't extract the OSX __MACOSX directory
			if (mb_substr($info, 0, 9) === '__MACOSX/')
			{
				continue;
			}

			$contents = $zip->getFromIndex($i);

			if ($contents === false)
			{
				Craft::log('Could not extract file from zip archive '.$srcZip, LogLevel::Error);
				return false;
			}

			if (!IOHelper::writeToFile($destFolder.'/'.$info, $contents, true, true))
			{
				Craft::log('Could not copy file to '.$destFolder.'/'.$info.' while unzipping from '.$srcZip, LogLevel::Error);
				return false;
			}
		}

		$zip->close();
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
		$zip = new \ZipArchive();
		$zipContents = $zip->open($sourceZip);

		if ($zipContents !== true)
		{
			Craft::log('Unable to open zip file: '.$sourceZip, LogLevel::Error);
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
				$relFilePath = mb_substr($itemToZip, mb_strlen($basePath));

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
						Craft::log('There was an error adding the file '.$itemToZip.' to the zip: '.$itemToZip, LogLevel::Error);
					}
				}
			}
		}

		$zip->close();
		return true;
	}
}
