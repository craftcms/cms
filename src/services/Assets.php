<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\services;

use Craft;
use craft\base\Volume;
use craft\db\Query;
use craft\elements\Asset;
use craft\elements\db\AssetQuery;
use craft\elements\User;
use craft\errors\AssetConflictException;
use craft\errors\AssetLogicException;
use craft\errors\FileException;
use craft\errors\ImageException;
use craft\errors\VolumeException;
use craft\errors\VolumeObjectExistsException;
use craft\errors\VolumeObjectNotFoundException;
use craft\events\AssetThumbEvent;
use craft\events\GetAssetThumbUrlEvent;
use craft\events\GetAssetUrlEvent;
use craft\events\ReplaceAssetEvent;
use craft\helpers\Assets as AssetsHelper;
use craft\helpers\DateTimeHelper;
use craft\helpers\Db;
use craft\helpers\FileHelper;
use craft\helpers\Image;
use craft\helpers\Json;
use craft\helpers\UrlHelper;
use craft\models\AssetTransform;
use craft\models\FolderCriteria;
use craft\models\VolumeFolder;
use craft\queue\jobs\GeneratePendingTransforms;
use craft\records\VolumeFolder as VolumeFolderRecord;
use craft\volumes\Temp;
use yii\base\Component;
use yii\base\InvalidArgumentException;
use yii\base\NotSupportedException;

/**
 * Assets service.
 * An instance of the Assets service is globally accessible in Craft via [[\craft\base\ApplicationTrait::getAssets()|`Craft::$app->assets`]].
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class Assets extends Component
{
    // Constants
    // =========================================================================

    /**
     * @event AssetEvent The event that is triggered before an asset is replaced.
     */
    const EVENT_BEFORE_REPLACE_ASSET = 'beforeReplaceFile';

    /**
     * @event AssetEvent The event that is triggered after an asset is replaced.
     */
    const EVENT_AFTER_REPLACE_ASSET = 'afterReplaceFile';

    /**
     * @event GetAssetUrlEvent The event that is triggered when a transform is being generated for an Asset.
     */
    const EVENT_GET_ASSET_URL = 'getAssetUrl';

    /**
     * @event GetAssetThumbUrlEvent The event that is triggered when a thumbnail is being generated for an Asset.
     * @todo rename to GET_THUMB_URL in Craft 4
     */
    const EVENT_GET_ASSET_THUMB_URL = 'getAssetThumbUrl';

    /**
     * @event AssetThumbEvent The event that is triggered when a thumbnail path is requested.
     */
    const EVENT_GET_THUMB_PATH = 'getThumbPath';

    // Properties
    // =========================================================================

    /**
     * @var
     */
    private $_foldersById;

    /**
     * @var bool Whether a Generate Pending Transforms job has already been queued up in this request
     */
    private $_queuedGeneratePendingTransformsJob = false;

    // Public Methods
    // =========================================================================

    /**
     * Returns a file by its ID.
     *
     * @param int $assetId
     * @param int|null $siteId
     * @return Asset|null
     */
    public function getAssetById(int $assetId, int $siteId = null)
    {
        /** @var Asset|null $asset */
        $asset = Craft::$app->getElements()->getElementById($assetId, Asset::class, $siteId);

        return $asset;
    }

    /**
     * Gets the total number of assets that match a given criteria.
     *
     * @param mixed $criteria
     * @return int
     */
    public function getTotalAssets($criteria = null): int
    {
        if ($criteria instanceof AssetQuery) {
            $query = $criteria;
        } else {
            $query = Asset::find();
            if ($criteria) {
                Craft::configure($query, $criteria);
            }
        }

        return $query->count();
    }

    /**
     * Replace an Asset's file.
     *
     * Replace an Asset's file by it's id, a local file and the filename to use.
     *
     * @param Asset $asset
     * @param string $pathOnServer
     * @param string $filename
     * @throws FileException If there was a problem with the actual file.
     * @throws AssetLogicException If the Asset to be replaced cannot be found.
     */
    public function replaceAssetFile(Asset $asset, string $pathOnServer, string $filename)
    {
        // Fire a 'beforeReplaceFile' event
        if ($this->hasEventHandlers(self::EVENT_BEFORE_REPLACE_ASSET)) {
            $this->trigger(self::EVENT_BEFORE_REPLACE_ASSET, new ReplaceAssetEvent([
                'asset' => $asset,
                'replaceWith' => $pathOnServer,
                'filename' => $filename
            ]));
        }

        $asset->tempFilePath = $pathOnServer;
        $asset->newFilename = $filename;
        $asset->avoidFilenameConflicts = true;
        $asset->setScenario(Asset::SCENARIO_REPLACE);

        Craft::$app->getElements()->saveElement($asset);

        // Fire an 'afterReplaceFile' event
        if ($this->hasEventHandlers(self::EVENT_AFTER_REPLACE_ASSET)) {
            $this->trigger(self::EVENT_AFTER_REPLACE_ASSET, new ReplaceAssetEvent([
                'asset' => $asset,
                'filename' => $filename
            ]));
        }
    }

    /**
     * Move or rename an Asset.
     *
     * @param Asset $asset The asset whose file should be renamed
     * @param VolumeFolder $folder The Volume Folder to move the Asset to.
     * @param string $filename The new filename
     * @return bool Whether the asset was renamed successfully
     * @throws AssetLogicException if the asset’s volume is missing
     */
    public function moveAsset(Asset $asset, VolumeFolder $folder, string $filename = ''): bool
    {
        // Set the new combined target location, and save it
        $asset->newFilename = $filename;
        $asset->newFolderId = $folder->id;
        $asset->setScenario(Asset::SCENARIO_FILEOPS);

        return Craft::$app->getElements()->saveElement($asset);
    }

    /**
     * Save an Asset folder.
     *
     * @param VolumeFolder $folder
     * @param bool $indexExisting Set to true to just index the folder if it already exists on volume.
     * @throws AssetConflictException if a folder already exists with such a name
     * @throws InvalidArgumentException if $folder doesn’t have a parent
     * @throws VolumeObjectExistsException if the file actually exists on the volume, but on in the index
     */
    public function createFolder(VolumeFolder $folder, bool $indexExisting = false)
    {
        $parent = $folder->getParent();

        if (!$parent) {
            throw new InvalidArgumentException('Folder ' . $folder->id . ' doesn’t have a parent.');
        }

        $existingFolder = $this->findFolder([
            'parentId' => $folder->parentId,
            'name' => $folder->name
        ]);

        if ($existingFolder && (!$folder->id || $folder->id !== $existingFolder->id)) {
            throw new AssetConflictException(Craft::t('app',
                'A folder with the name “{folderName}” already exists in the volume.',
                ['folderName' => $folder->name]));
        }

        $volume = $parent->getVolume();

        try {
            $volume->createDir(rtrim($folder->path, '/'));
        } catch (VolumeObjectExistsException $exception) {
            // Rethrow exception unless this is a temporary Volume or we're allowed to index it silently
            if ($folder->volumeId !== null && !$indexExisting) {
                throw $exception;
            }
        }

        $this->storeFolderRecord($folder);
    }

    /**
     * Rename a folder by it's id.
     *
     * @param int $folderId
     * @param string $newName
     * @throws AssetConflictException If a folder already exists with such name in Assets Index
     * @throws AssetLogicException If the folder to be renamed can't be found or trying to rename the top folder.
     * @throws VolumeObjectExistsException If a folder already exists with such name in the Volume, but not in Index
     * @throws VolumeObjectNotFoundException If the folder to be renamed can't be found in the Volume.
     * @return string The new folder name after cleaning it.
     */
    public function renameFolderById(int $folderId, string $newName): string
    {
        $newName = AssetsHelper::prepareAssetName($newName, false);
        $folder = $this->getFolderById($folderId);

        if (!$folder) {
            throw new AssetLogicException(Craft::t('app',
                'No folder exists with the ID “{id}”',
                ['id' => $folderId]));
        }

        if (!$folder->parentId) {
            throw new AssetLogicException(Craft::t('app',
                'It’s not possible to rename the top folder of a Volume.'));
        }

        $conflictingFolder = $this->findFolder([
            'parentId' => $folder->parentId,
            'name' => $newName
        ]);

        if ($conflictingFolder) {
            throw new AssetConflictException(Craft::t('app',
                'A folder with the name “{folderName}” already exists in the folder.',
                ['folderName' => $folder->name]));
        }

        $parentFolderPath = dirname($folder->path);
        $newFolderPath = (($parentFolderPath && $parentFolderPath !== '.') ? $parentFolderPath . '/' : '') . $newName . '/';

        $volume = $folder->getVolume();

        $volume->renameDir(rtrim($folder->path, '/'), $newName);
        $descendantFolders = $this->getAllDescendantFolders($folder);

        foreach ($descendantFolders as $descendantFolder) {
            $descendantFolder->path = preg_replace('#^' . $folder->path . '#', $newFolderPath, $descendantFolder->path);
            $this->storeFolderRecord($descendantFolder);
        }

        // Now change the affected folder
        $folder->name = $newName;
        $folder->path = $newFolderPath;
        $this->storeFolderRecord($folder);

        return $newName;
    }

    /**
     * Deletes a folder by its ID.
     *
     * @param array|int $folderIds
     * @param bool $deleteDir Should the volume directory be deleted along the record, if applicable. Defaults to true.
     * @throws VolumeException If deleting a single folder and it cannot be deleted.
     */
    public function deleteFoldersByIds($folderIds, bool $deleteDir = true)
    {
        foreach ((array)$folderIds as $folderId) {
            $folder = $this->getFolderById($folderId);

            if ($folder) {
                if ($deleteDir) {
                    $volume = $folder->getVolume();
                    $volume->deleteDir($folder->path);
                }

                VolumeFolderRecord::deleteAll(['id' => $folderId]);
            }
        }
    }

    /**
     * Get the folder tree for Assets by volume ids
     *
     * @param array $allowedVolumeIds
     * @param array $additionalCriteria additional criteria for filtering the tree
     * @return array
     */
    public function getFolderTreeByVolumeIds($allowedVolumeIds, array $additionalCriteria = []): array
    {
        static $volumeFolders = [];

        $tree = [];

        // Get the tree for each source
        foreach ($allowedVolumeIds as $volumeId) {
            // Add additional criteria but prevent overriding volumeId and order.
            $criteria = array_merge($additionalCriteria, [
                'volumeId' => $volumeId,
                'order' => 'path'
            ]);
            $cacheKey = md5(Json::encode($criteria));

            // If this has not been yet fetched, fetch it.
            if (empty($volumeFolders[$cacheKey])) {
                $folders = $this->findFolders($criteria);
                $subtree = $this->_getFolderTreeByFolders($folders);
                $volumeFolders[$cacheKey] = reset($subtree);
            }

            $tree[$volumeId] = $volumeFolders[$cacheKey];
        }

        AssetsHelper::sortFolderTree($tree);

        return $tree;
    }

    /**
     * Get the folder tree for Assets by a folder id.
     *
     * @param int $folderId
     * @return array
     */
    public function getFolderTreeByFolderId(int $folderId): array
    {
        if (($folder = $this->getFolderById($folderId)) === null) {
            return [];
        }

        return $this->_getFolderTreeByFolders([$folder]);
    }

    /**
     * Returns a folder by its ID.
     *
     * @param int $folderId
     * @return VolumeFolder|null
     */
    public function getFolderById(int $folderId)
    {
        if ($this->_foldersById !== null && array_key_exists($folderId, $this->_foldersById)) {
            return $this->_foldersById[$folderId];
        }

        $result = $this->_createFolderQuery()
            ->where(['id' => $folderId])
            ->one();

        if (!$result) {
            return $this->_foldersById[$folderId] = null;
        }

        return $this->_foldersById[$folderId] = new VolumeFolder($result);
    }

    /**
     * Finds folders that match a given criteria.
     *
     * @param mixed $criteria
     * @return VolumeFolder[]
     */
    public function findFolders($criteria = null): array
    {
        if (!($criteria instanceof FolderCriteria)) {
            $criteria = new FolderCriteria($criteria);
        }

        $query = $this->_createFolderQuery();

        $this->_applyFolderConditions($query, $criteria);

        if ($criteria->order) {
            $query->orderBy($criteria->order);
        }

        if ($criteria->offset) {
            $query->offset($criteria->offset);
        }

        if ($criteria->limit) {
            $query->limit($criteria->limit);
        }

        $results = $query->all();
        $folders = [];

        foreach ($results as $result) {
            $folder = new VolumeFolder($result);
            $this->_foldersById[$folder->id] = $folder;
            $folders[$folder->id] = $folder;
        }

        return $folders;
    }

    /**
     * Returns all of the folders that are descendants of a given folder.
     *
     * @param VolumeFolder $parentFolder
     * @param string $orderBy
     * @return array
     */
    public function getAllDescendantFolders(VolumeFolder $parentFolder, string $orderBy = 'path'): array
    {
        /** @var $query Query */
        $query = $this->_createFolderQuery()
            ->where([
                'and',
                ['like', 'path', $parentFolder->path . '%', false],
                ['volumeId' => $parentFolder->volumeId],
                ['not', ['parentId' => null]]
            ]);

        if ($orderBy) {
            $query->orderBy($orderBy);
        }

        $results = $query->all();
        $descendantFolders = [];

        foreach ($results as $result) {
            $folder = new VolumeFolder($result);
            $this->_foldersById[$folder->id] = $folder;
            $descendantFolders[$folder->id] = $folder;
        }

        return $descendantFolders;
    }

    /**
     * Finds the first folder that matches a given criteria.
     *
     * @param mixed $criteria
     * @return VolumeFolder|null
     */
    public function findFolder($criteria = null)
    {
        if (!($criteria instanceof FolderCriteria)) {
            $criteria = new FolderCriteria($criteria);
        }

        $criteria->limit = 1;
        $folder = $this->findFolders($criteria);

        if (is_array($folder) && !empty($folder)) {
            return array_pop($folder);
        }

        return null;
    }

    /**
     * Returns the root folder for a given volume ID.
     *
     * @param int $volumeId The volume ID
     * @return VolumeFolder|null The root folder in that volume, or null if the volume doesn’t exist
     */
    public function getRootFolderByVolumeId(int $volumeId)
    {
        return $this->findFolder([
            'volumeId' => $volumeId,
            'parentId' => ':empty:'
        ]);
    }

    /**
     * Gets the total number of folders that match a given criteria.
     *
     * @param mixed $criteria
     * @return int
     */
    public function getTotalFolders($criteria): int
    {
        if (!($criteria instanceof FolderCriteria)) {
            $criteria = new FolderCriteria($criteria);
        }

        $query = (new Query())
            ->from(['{{%volumefolders}}']);

        $this->_applyFolderConditions($query, $criteria);

        return (int)$query->count('[[id]]');
    }

    // File and folder managing
    // -------------------------------------------------------------------------

    /**
     * Returns the URL for an asset, possibly with a given transform applied.
     *
     * @param Asset $asset
     * @param AssetTransform|string|array|null $transform
     * @param bool|null $generateNow Whether the transformed image should be
     * generated immediately if it doesn’t exist. Default is null, meaning it
     * will be left up to the `generateTransformsBeforePageLoad` sconfig setting.
     * @return string|null
     */
    public function getAssetUrl(Asset $asset, $transform = null, bool $generateNow = null)
    {
        // Maybe a plugin wants to do something here
        $event = new GetAssetUrlEvent([
            'transform' => $transform,
            'asset' => $asset,
        ]);
        $this->trigger(self::EVENT_GET_ASSET_URL, $event);

        // If a plugin set the url, we'll just use that.
        if ($event->url !== null) {
            return $event->url;
        }

        if ($transform === null || !Image::canManipulateAsImage(pathinfo($asset->filename, PATHINFO_EXTENSION))) {
            $volume = $asset->getVolume();

            return AssetsHelper::generateUrl($volume, $asset);
        }

        // Get the transform index model
        $assetTransforms = Craft::$app->getAssetTransforms();
        $index = $assetTransforms->getTransformIndex($asset, $transform);

        // Does the file actually exist?
        if ($index->fileExists) {
            return $assetTransforms->getUrlForTransformByAssetAndTransformIndex($asset, $index);
        }

        if ($generateNow === null) {
            $generateNow = Craft::$app->getConfig()->getGeneral()->generateTransformsBeforePageLoad;
        }

        if ($generateNow) {
            try {
                return $assetTransforms->ensureTransformUrlByIndexModel($index);
            } catch (ImageException $exception) {
                Craft::warning($exception->getMessage(), __METHOD__);
                $assetTransforms->deleteTransformIndex($index->id);
                return null;
            }
        }

        // Queue up a new Generate Pending Transforms job
        if (!$this->_queuedGeneratePendingTransformsJob) {
            Craft::$app->getQueue()->push(new GeneratePendingTransforms());
            $this->_queuedGeneratePendingTransformsJob = true;
        }

        // Return the temporary transform URL
        return UrlHelper::actionUrl('assets/generate-transform', ['transformId' => $index->id]);
    }

    /**
     * Returns the CP thumbnail URL for a given asset.
     *
     * @param Asset $asset asset to return a thumb for
     * @param int $width width of the returned thumb
     * @param int|null $height height of the returned thumb (defaults to $width if null)
     * @param bool $generate whether to generate a thumb in none exists yet
     * @param bool $fallbackToIcon whether to return the URL to a generic icon if a thumbnail can't be generated
     * @return string
     * @throws NotSupportedException if the asset can't have a thumbnail, and $fallbackToIcon is `false`
     * @see Asset::getThumbUrl()
     */
    public function getThumbUrl(Asset $asset, int $width, int $height = null, bool $generate = false, bool $fallbackToIcon = true): string
    {
        if ($height === null) {
            $height = $width;
        }

        // Maybe a plugin wants to do something here
        // todo: remove the `size` key in 4.0
        if ($this->hasEventHandlers(self::EVENT_GET_ASSET_THUMB_URL)) {
            $event = new GetAssetThumbUrlEvent([
                'asset' => $asset,
                'width' => $width,
                'height' => $height,
                'size' => max($width, $height),
                'generate' => $generate,
            ]);
            $this->trigger(self::EVENT_GET_ASSET_THUMB_URL, $event);

            // If a plugin set the url, we'll just use that.
            if ($event->url !== null) {
                return $event->url;
            }
        }

        return UrlHelper::actionUrl('assets/thumb', [
            'uid' => $asset->uid,
            'width' => $width,
            'height' => $height,
            'v' => $asset->dateModified->getTimestamp(),
        ]);
    }

    /**
     * Returns the CP thumbnail path for a given asset.
     *
     * @param Asset $asset asset to return a thumb for
     * @param int $width width of the returned thumb
     * @param int|null $height height of the returned thumb (defaults to $width if null)
     * @param bool $generate whether to generate a thumb in none exists yet
     * @param bool $fallbackToIcon whether to return the path to a generic icon if a thumbnail can't be generated
     * @return string|false thumbnail path, or `false` if it doesn't exist and $generate is `false`
     * @throws NotSupportedException if the asset can't have a thumbnail, and $fallbackToIcon is `false`
     * @see getThumbUrl()
     */
    public function getThumbPath(Asset $asset, int $width, int $height = null, bool $generate = true, bool $fallbackToIcon = true)
    {
        // Maybe a plugin wants to do something here
        $event = new AssetThumbEvent([
            'asset' => $asset,
            'width' => $width,
            'height' => $height,
            'generate' => $generate,
        ]);
        $this->trigger(self::EVENT_GET_THUMB_PATH, $event);

        // If a plugin set the url, we'll just use that.
        if ($event->path !== null) {
            return $event->path;
        }

        $ext = $asset->getExtension();

        // If it's not an image, return a generic file extension icon
        if (!Image::canManipulateAsImage($ext)) {
            if (!$fallbackToIcon) {
                throw new NotSupportedException("A thumbnail can't be generated for the asset.");
            }

            return $this->getIconPath($asset);
        }

        if ($height === null) {
            $height = $width;
        }

        // Make the thumb a JPG if the image format isn't safe for web
        $ext = in_array($ext, Image::webSafeFormats(), true) ? $ext : 'jpg';
        $dir = Craft::$app->getPath()->getAssetThumbsPath() . DIRECTORY_SEPARATOR . $asset->id;
        $path = $dir . DIRECTORY_SEPARATOR . "thumb-{$width}x{$height}.{$ext}";

        if (!file_exists($path) || $asset->dateModified->getTimestamp() > filemtime($path)) {
            // Bail if we're not ready to generate it yet
            if (!$generate) {
                return false;
            }

            // Generate it
            FileHelper::createDirectory($dir);
            $imageSource = Craft::$app->getAssetTransforms()->getLocalImageSource($asset);
            $svgSize = max($width, $height);

            // hail Mary
            try {
                Craft::$app->getImages()->loadImage($imageSource, false, $svgSize)
                    ->scaleToFit($width, $height)
                    ->saveAs($path);
            } catch (ImageException $exception) {
                Craft::warning($exception->getMessage());
                return $this->getIconPath($asset);
            }
        }

        return $path;
    }

    /**
     * Returns a generic file extension icon path, that can be used as a fallback
     * for assets that don't have a normal thumbnail.
     *
     * @param Asset $asset
     * @return string
     */
    public function getIconPath(Asset $asset): string
    {
        $ext = $asset->getExtension();
        $path = Craft::$app->getPath()->getAssetsIconsPath() . DIRECTORY_SEPARATOR . strtolower($ext) . '.svg';

        if (file_exists($path)) {
            return $path;
        }

        $svg = file_get_contents(Craft::getAlias('@app/icons/file.svg'));

        $extLength = strlen($ext);
        if ($extLength <= 3) {
            $textSize = '26';
        } else if ($extLength === 4) {
            $textSize = '22';
        } else {
            if ($extLength > 5) {
                $ext = substr($ext, 0, 4) . '…';
            }
            $textSize = '18';
        }

        $textNode = "<text x=\"50\" y=\"73\" text-anchor=\"middle\" font-family=\"sans-serif\" fill=\"#8F98A3\" font-size=\"{$textSize}\">" . strtoupper($ext) . '</text>';
        $svg = str_replace('<!-- EXT -->', $textNode, $svg);

        FileHelper::writeToFile($path, $svg);
        return $path;
    }

    /**
     * Find a replacement for a filename
     *
     * @param string $originalFilename the original filename for which to find a replacement.
     * @param int $folderId THe folder in which to find the replacement
     * @return string If a suitable filename replacement cannot be found.
     * @throws AssetLogicException If a suitable filename replacement cannot be found.
     * @throws InvalidArgumentException If $folderId is invalid
     */
    public function getNameReplacementInFolder(string $originalFilename, int $folderId): string
    {
        $folder = $this->getFolderById($folderId);

        if (!$folder) {
            throw new InvalidArgumentException('Invalid folder ID: ' . $folderId);
        }

        $volume = $folder->getVolume();
        $fileList = $volume->getFileList((string)$folder->path, false);

        // Flip the array for faster lookup
        $existingFiles = [];

        foreach ($fileList as $file) {
            if (mb_strtolower(rtrim($folder->path, '/')) === mb_strtolower($file['dirname'])) {
                $existingFiles[mb_strtolower($file['basename'])] = true;
            }
        }

        // Get a list from DB as well
        $fileList = (new Query())
            ->select(['filename'])
            ->from(['{{%assets}}'])
            ->where(['folderId' => $folderId])
            ->column();

        // Combine the indexed list and the actual file list to make the final potential conflict list.
        foreach ($fileList as $file) {
            $existingFiles[mb_strtolower($file)] = true;
        }

        // Shorthand.
        $canUse = function($filenameToTest) use ($existingFiles) {
            return !isset($existingFiles[mb_strtolower($filenameToTest)]);
        };

        if ($canUse($originalFilename)) {
            return $originalFilename;
        }

        $extension = pathinfo($originalFilename, PATHINFO_EXTENSION);
        $filename = pathinfo($originalFilename, PATHINFO_FILENAME);

        // If the file already ends with something that looks like a timestamp, use that instead.
        if (preg_match('/.*_(\d{6}_\d{6})$/', $filename, $matches)) {
            $base = $filename;
        } else {
            $timestamp = DateTimeHelper::currentUTCDateTime()->format('ymd_His');
            $base = $filename . '_' . $timestamp;
        }

        $newFilename = $base . '.' . $extension;

        if ($canUse($newFilename)) {
            return $newFilename;
        }

        $increment = 0;

        while (++$increment) {
            $newFilename = $base . '_' . $increment . '.' . $extension;

            if ($canUse($newFilename)) {
                break;
            }

            if ($increment === 50) {
                throw new AssetLogicException(Craft::t('app',
                    'Could not find a suitable replacement filename for “{filename}”.',
                    ['filename' => $filename]));
            }
        }

        return $newFilename;
    }

    /**
     * Ensure a folder entry exists in the DB for the full path and return it's id. Depending on the use, it's possible to also ensure a physical folder exists.
     *
     * @param string $fullPath The path to ensure the folder exists at.
     * @param Volume $volume
     * @param bool $justRecord If set to false, will also make sure the physical folder exists on Volume.
     * @return int
     * @throws VolumeException If the volume cannot be found.
     */
    public function ensureFolderByFullPathAndVolume(string $fullPath, Volume $volume, bool $justRecord = true): int
    {
        $parentId = Craft::$app->getVolumes()->ensureTopFolder($volume);
        $folderId = $parentId;

        if ($fullPath) {
            // If we don't have a folder matching these, create a new one
            $parts = explode('/', trim($fullPath, '/'));

            // creep up the folder path
            $path = '';

            while (($part = array_shift($parts)) !== null) {
                $path .= $part . '/';

                $parameters = new FolderCriteria([
                    'path' => $path,
                    'volumeId' => $volume->id
                ]);

                // Create the record for current segment if needed.
                if (($folderModel = $this->findFolder($parameters)) === null) {
                    $folderModel = new VolumeFolder();
                    $folderModel->volumeId = $volume->id;
                    $folderModel->parentId = $parentId;
                    $folderModel->name = $part;
                    $folderModel->path = $path;
                    $this->storeFolderRecord($folderModel);
                }

                // Ensure a physical folder exists, if needed.
                if (!$justRecord) {
                    try {
                        $volume->createDir($path);
                    } catch (VolumeObjectExistsException $exception) {
                        // Already there. Good.
                    }
                }

                // Set the variables for next iteration.
                $folderId = $folderModel->id;
                $parentId = $folderId;
            }
        }

        return $folderId;
    }

    /**
     * Store a folder by model
     *
     * @param VolumeFolder $folder
     */
    public function storeFolderRecord(VolumeFolder $folder)
    {
        if (!$folder->id) {
            $record = new VolumeFolderRecord();
        } else {
            $record = VolumeFolderRecord::findOne(['id' => $folder->id]);
        }

        $record->parentId = $folder->parentId;
        $record->volumeId = $folder->volumeId;
        $record->name = $folder->name;
        $record->path = $folder->path;
        $record->save();

        $folder->id = $record->id;
    }

    /**
     * Return the current user's temporary upload folder.
     *
     * @return VolumeFolder
     */
    public function getCurrentUserTemporaryUploadFolder()
    {
        return $this->getUserTemporaryUploadFolder(Craft::$app->getUser()->getIdentity());
    }

    /**
     * Get the user's temporary upload folder.
     *
     * @param User|null $userModel
     * @return VolumeFolder
     */
    public function getUserTemporaryUploadFolder(User $userModel = null)
    {
        $volumeTopFolder = $this->findFolder([
            'volumeId' => ':empty:',
            'parentId' => ':empty:'
        ]);

        // Unlikely, but would be very awkward if this happened without any contingency plans in place.
        if (!$volumeTopFolder) {
            $volumeTopFolder = new VolumeFolder();
            $tempVolume = new Temp();
            $volumeTopFolder->name = $tempVolume->name;
            $this->storeFolderRecord($volumeTopFolder);
        }

        if ($userModel) {
            $folderName = 'user_' . $userModel->id;
        } else {
            // A little obfuscation never hurt anyone
            $folderName = 'user_' . sha1(Craft::$app->getSession()->id);
        }

        $folder = $this->findFolder([
            'name' => $folderName,
            'parentId' => $volumeTopFolder->id
        ]);

        if (!$folder) {
            $folder = new VolumeFolder();
            $folder->parentId = $volumeTopFolder->id;
            $folder->name = $folderName;
            $folder->path = $folderName . '/';
            $this->storeFolderRecord($folder);
        }

        FileHelper::createDirectory(Craft::$app->getPath()->getTempAssetUploadsPath() . DIRECTORY_SEPARATOR . $folderName);

        /**
         * @var VolumeFolder $folder ;
         */
        return $folder;
    }

    // Private Methods
    // =========================================================================

    /**
     * Returns a DbCommand object prepped for retrieving assets.
     *
     * @return Query
     */
    private function _createFolderQuery(): Query
    {
        return (new Query())
            ->select(['id', 'parentId', 'volumeId', 'name', 'path'])
            ->from(['{{%volumefolders}}']);
    }

    /**
     * Return the folder tree form a list of folders.
     *
     * @param VolumeFolder[] $folders
     * @return array
     */
    private function _getFolderTreeByFolders(array $folders): array
    {
        $tree = [];
        $referenceStore = [];

        foreach ($folders as $folder) {
            // We'll be adding all of the children in this loop, anyway, so we set
            // the children list to an empty array so that folders that have no children don't
            // trigger any queries, when asked for children
            $folder->setChildren([]);
            if ($folder->parentId && isset($referenceStore[$folder->parentId])) {
                $referenceStore[$folder->parentId]->addChild($folder);
            } else {
                $tree[] = $folder;
            }

            $referenceStore[$folder->id] = $folder;
        }

        return $tree;
    }

    /**
     * Applies WHERE conditions to a DbCommand query for folders.
     *
     * @param Query $query
     * @param FolderCriteria $criteria
     */
    private function _applyFolderConditions(Query $query, FolderCriteria $criteria)
    {
        if ($criteria->id) {
            $query->andWhere(Db::parseParam('id', $criteria->id));
        }

        if ($criteria->volumeId) {
            $query->andWhere(Db::parseParam('volumeId', $criteria->volumeId));
        }

        if ($criteria->parentId) {
            $query->andWhere(Db::parseParam('parentId', $criteria->parentId));
        }

        if ($criteria->name) {
            $query->andWhere(Db::parseParam('name', $criteria->name));
        }

        if ($criteria->path !== null) {
            // Does the path have a comma in it?
            if (strpos($criteria->path, ',') !== false) {
                // Escape the comma.
                $query->andWhere(Db::parseParam('path', str_replace(',', '\,', $criteria->path)));
            } else {
                $query->andWhere(Db::parseParam('path', $criteria->path));
            }
        }
    }
}
