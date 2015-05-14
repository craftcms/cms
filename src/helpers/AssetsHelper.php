<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2015 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\helpers;

use Craft;
use craft\app\base\Volume;
use craft\app\elements\Asset;
use craft\app\models\VolumeFolder;

/**
 * Class AssetsHelper
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class AssetsHelper
{
	// Constants
	// =========================================================================

	const INDEX_SKIP_ITEMS_PATTERN = '/.*(Thumbs\.db|__MACOSX|__MACOSX\/|__MACOSX\/.*|\.DS_STORE)$/i';

	// Public Methods
	// =========================================================================

	/**
	 * Get a temporary file path.
	 *
	 * @param string $extension extension to use. "tmp" by default.
	 *
	 * @return mixed
	 */
	public static function getTempFilePath($extension = 'tmp')
	{
		$extension = strpos($extension, '.') !== false ? pathinfo($extension, PATHINFO_EXTENSION) : $extension;
		$filename = uniqid('assets', true).'.'.$extension;

		return IOHelper::createFile(Craft::$app->getPath()->getTempPath().'/'.$filename)->getRealPath();
	}

	/**
	 * Generate a URL for a given Assets file in a Source Type.
	 *
	 * @param Volume $volume
	 * @param Asset  $file
	 *
	 * @return string
	 */
	public static function generateUrl(Volume $volume, Asset $file)
	{
		$baseUrl = $volume->getRootUrl();
		$folderPath = $file->getFolder()->path;
		$filename = $file->filename;
		$appendix = static::getUrlAppendix($volume, $file);

		return $baseUrl.$folderPath.$filename.$appendix;
	}

	/**
	 * Get appendix for an URL based on it's Source caching settings.
	 *
	 * @param Volume $source
	 * @param Asset  $file
	 *
	 * @return string
	 */
	public static function getUrlAppendix(Volume $source, Asset $file)
	{
		$appendix = '';

		//@TODO add cache apendix here
		/*if (!empty($source->getSettings()->expires) && DateTimeHelper::isValidIntervalString($source->getSettings()->expires))
		{
			$appendix = '?mtime='.$file->dateModified->format("YmdHis");
		}*/

		return $appendix;
	}

	/**
	 * Clean an Asset's filename.
	 *
	 * @param $name
	 * @param bool $isFilename if set to true (default), will separate extension
	 *                         and clean the filename separately.
	 *
	 * @return mixed
	 */
	public static function prepareAssetName($name, $isFilename = true)
	{
		if ($isFilename)
		{
			$baseName = IOHelper::getFilename($name, false);
			$extension = '.'.IOHelper::getExtension($name);
		}
		else
		{
			$baseName = $name;
			$extension =  '';
		}


		$separator = Craft::$app->getConfig()->get('filenameWordSeparator');

		if (!is_string($separator))
		{
			$separator = null;
		}

		$baseName = IOHelper::cleanFilename($baseName, Craft::$app->getConfig()->get('convertFilenamesToAscii'), $separator);

		if ($isFilename && empty($baseName))
		{
			$baseName = '-';
		}

		return $baseName.$extension;
	}

	/**
	 * Mirror a folder structure on a Volume.
	 *
	 * @param VolumeFolder $sourceParentFolder Folder who's children folder structure should be mirrored.
	 * @param VolumeFolder $destinationFolder  The destination folder
	 * @param array        $targetTreeMap map of relative path => existing folder id
	 *
	 * @return array $folderIdChanges map of original folder id => new folder id
	 */
	public static function mirrorFolderStructure(VolumeFolder $sourceParentFolder, VolumeFolder $destinationFolder, $targetTreeMap = array())
	{
		$sourceTree = Craft::$app->getAssets()->getAllDescendantFolders($sourceParentFolder);
		$previousParent = $sourceParentFolder->getParent();
		$sourcePrefixLength = strlen($previousParent->path);
		$folderIdChanges = [];

		foreach ($sourceTree as $sourceFolder)
		{
			$relativePath = substr($sourceFolder->path, $sourcePrefixLength);

			// If we have a target tree map, try to see if we should just point to an existing folder.
			if ($targetTreeMap && isset($targetTreeMap[$relativePath]))
			{
				$folderIdChanges[$sourceFolder->id] = $targetTreeMap[$relativePath];
			}
			else
			{
				$folder           = new VolumeFolder();
				$folder->name     = $sourceFolder->name;
				$folder->volumeId = $destinationFolder->volumeId;
				$folder->path     = ltrim(rtrim($destinationFolder->path, '/').('/').$relativePath, '/');

				// Any and all parent folders should be already mirrored
				$folder->parentId = (isset($folderIdChanges[$sourceFolder->parentId]) ? $folderIdChanges[$sourceFolder->parentId] : $destinationFolder->id);

				Craft::$app->getAssets()->createFolder($folder);

				$folderIdChanges[$sourceFolder->id] = $folder->id;
			}
		}

		return $folderIdChanges;
	}

	public static function getFileTransferList($assets, $folderIdChanges, $merge = false)
	{
		$fileTransferList = [];

		// Build the transfer list for files
		foreach ($assets as $asset)
		{
			$newFolderId = $folderIdChanges[$asset->folderId];
			$transferItem = ['fileId' => $asset->id, 'folderId' => $newFolderId];

			// If we're merging, preemptively figure out if there'll be conflicts and resolve them
			if ($merge)
			{
				$conflictingAsset = Craft::$app->getAssets()->findFile(['filename' => $asset->filename, 'folderId' => $newFolderId]);

				if ($conflictingAsset)
				{
					$transferItem['userResponse'] = 'replace';
				}
			}

			$fileTransferList[] = $transferItem;
		}

		return $fileTransferList;
	}
}
