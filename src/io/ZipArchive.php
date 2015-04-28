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
 * Class ZipArchive
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class ZipArchive implements ZipInterface
{
	// Public Methods
	// =========================================================================

	/**
	 * @inheritdoc
	 */
	public function zip($sourceFolder, $destZip)
	{
		$zip = new \ZipArchive();

		$zipContents = $zip->open($destZip, \ZipArchive::CREATE);

		if ($zipContents !== true)
		{
			Craft::error('Unable to create zip file: '.$destZip, __METHOD__);
			return false;
		}

		return $this->add($destZip, $sourceFolder, $sourceFolder);
	}

	/**
	 * @inheritdoc
	 */
	public function unzip($srcZip, $destFolder)
	{
		@ini_set('memory_limit', Craft::$app->getConfig()->get('phpMaxMemoryLimit'));

		$zip = new \ZipArchive();
		$zipContents = $zip->open($srcZip, \ZipArchive::CHECKCONS);

		if ($zipContents !== true)
		{
			Craft::error('Could not open the zip file: '.$srcZip, __METHOD__);
			return false;
		}

		for ($i = 0; $i < $zip->numFiles; $i++)
		{
			if (!$info = $zip->statIndex($i))
			{
				Craft::error('Could not retrieve a file from the zip archive '.$srcZip, __METHOD__);
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
				Craft::error('Could not extract file from zip archive '.$srcZip, __METHOD__);
				return false;
			}

			if (!IOHelper::writeToFile($destFolder.'/'.$info, $contents, true, true))
			{
				Craft::error('Could not copy file to '.$destFolder.'/'.$info.' while unzipping from '.$srcZip, __METHOD__);
				return false;
			}
		}

		$zip->close();
		return true;
	}

	/**
	 * @inheritdoc
	 */
	public function add($sourceZip, $pathToAdd, $basePath, $pathPrefix = null)
	{
		$zip = new \ZipArchive();
		$zipContents = $zip->open($sourceZip);

		if ($zipContents !== true)
		{
			Craft::error('Unable to open zip file: '.$sourceZip, __METHOD__);
			return false;
		}

		if (IOHelper::fileExists($pathToAdd))
		{
			$folderContents = [$pathToAdd];
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
						Craft::error('There was an error adding the file '.$itemToZip.' to the zip: '.$itemToZip, __METHOD__);
					}
				}
			}
		}

		$zip->close();
		return true;
	}
}
