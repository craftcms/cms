<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\app\helpers;

use Craft;
use craft\app\base\Volume;
use craft\app\elements\Asset;
use craft\app\enums\PeriodType;
use craft\app\models\VolumeFolder;

/**
 * Class Assets
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class Assets
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

        return Io::createFile(Craft::$app->getPath()->getTempPath().'/'.$filename)->getRealPath();
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
     * @param Volume $volume
     * @param Asset  $file
     *
     * @return string
     */
    public static function getUrlAppendix(Volume $volume, Asset $file)
    {
        $appendix = '';

        if (!empty($volume->expires) && DateTimeHelper::isValidIntervalString($volume->expires))
        {
            $appendix = '?mtime='.$file->dateModified->format("YmdHis");
        }

        return $appendix;
    }

    /**
     * Clean an Asset's filename.
     *
     * @param         $name
     * @param boolean $isFilename                 if set to true (default), will separate extension
     *                                            and clean the filename separately.
     * @param boolean $preventPluginModifications if set to true, will prevent plugins from modify
     *
     * @return mixed
     */
    public static function prepareAssetName($name, $isFilename = true, $preventPluginModifications = false)
    {
        if ($isFilename) {
            $baseName = Io::getFilename($name, false);
            $extension = '.'.Io::getExtension($name);
        } else {
            $baseName = $name;
            $extension = '';
        }

        $config = Craft::$app->getConfig();
        $separator = $config->get('filenameWordSeparator');

        if (!is_string($separator)) {
            $separator = null;
        }

        if ($isFilename && !$preventPluginModifications) {
            $pluginModifiedAssetName = Craft::$app->getPlugins()->callFirst('modifyAssetFilename', [$baseName], true);

            // Use the plugin-modified name, if anyone was up to the task.
            $baseName = $pluginModifiedAssetName ?: $baseName;
        }

        $baseName = Io::cleanFilename($baseName, $config->get('convertFilenamesToAscii'), $separator);

        if ($isFilename && empty($baseName)) {
            $baseName = '-';
        }

        return $baseName.$extension;
    }

    /**
     * Mirror a folder structure on a Volume.
     *
     * @param VolumeFolder $sourceParentFolder Folder who's children folder structure should be mirrored.
     * @param VolumeFolder $destinationFolder  The destination folder
     * @param array        $targetTreeMap      map of relative path => existing folder id
     *
     * @return array map of original folder id => new folder id
     */
    public static function mirrorFolderStructure(VolumeFolder $sourceParentFolder, VolumeFolder $destinationFolder, $targetTreeMap = [])
    {
        $assets = Craft::$app->getAssets();
        $sourceTree = $assets->getAllDescendantFolders($sourceParentFolder);
        $previousParent = $sourceParentFolder->getParent();
        $sourcePrefixLength = strlen($previousParent->path);
        $folderIdChanges = [];

        foreach ($sourceTree as $sourceFolder) {
            $relativePath = substr($sourceFolder->path, $sourcePrefixLength);

            // If we have a target tree map, try to see if we should just point to an existing folder.
            if ($targetTreeMap && isset($targetTreeMap[$relativePath])) {
                $folderIdChanges[$sourceFolder->id] = $targetTreeMap[$relativePath];
            } else {
                $folder = new VolumeFolder();
                $folder->name = $sourceFolder->name;
                $folder->volumeId = $destinationFolder->volumeId;
                $folder->path = ltrim(rtrim($destinationFolder->path,
                        '/').('/').$relativePath, '/');

                // Any and all parent folders should be already mirrored
                $folder->parentId = (isset($folderIdChanges[$sourceFolder->parentId]) ? $folderIdChanges[$sourceFolder->parentId] : $destinationFolder->id);

                $assets->createFolder($folder);

                $folderIdChanges[$sourceFolder->id] = $folder->id;
            }
        }

        return $folderIdChanges;
    }

    /**
     * Create an Asset transfer list based on a list of Assets and an array of
     * changing folder ids.
     *
     * @param array $assets          List of assets
     * @param array $folderIdChanges A map of folder id changes
     * @param bool  $merge           If set to true, files will be merged in folders
     *
     * @return array
     */
    public static function getFileTransferList($assets, $folderIdChanges, $merge = false)
    {
        $fileTransferList = [];

        // Build the transfer list for files
        foreach ($assets as $asset) {
            $newFolderId = $folderIdChanges[$asset->folderId];
            $transferItem = [
                'fileId' => $asset->id,
                'folderId' => $newFolderId
            ];

            // If we're merging, preemptively figure out if there'll be conflicts and resolve them
            if ($merge) {
                $conflictingAsset = Craft::$app->getAssets()->findAsset([
                    'filename' => $asset->filename,
                    'folderId' => $newFolderId
                ]);

                if ($conflictingAsset) {
                    $transferItem['userResponse'] = 'replace';
                }
            }

            $fileTransferList[] = $transferItem;
        }

        return $fileTransferList;
    }

    /**
     * Get a list of available periods for Cache duration settings.
     *
     * @return array
     */
    public static function getPeriodList()
    {
        return [
            PeriodType::Seconds => Craft::t('app', 'Seconds'),
            PeriodType::Minutes => Craft::t('app', 'Minutes'),
            PeriodType::Hours => Craft::t('app', 'Hours'),
            PeriodType::Days => Craft::t('app', 'Days'),
            PeriodType::Months => Craft::t('app', 'Months'),
            PeriodType::Years => Craft::t('app', 'Years'),
        ];
    }

    /**
     * Sorts a folder tree by Volume sort order.
     *
     * @param array &$tree array passed by reference of the sortable folders.
     */
    public static function sortFolderTree(&$tree)
    {
        $sort = [];

        foreach ($tree as $topFolder) {
            /**
             * @var VolumeFolder $topFolder
             */
            $sort[] = $topFolder->getVolume()->sortOrder;
        }

        array_multisort($sort, $tree);
    }
}
