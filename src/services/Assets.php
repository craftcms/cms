<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\services;

use Craft;
use craft\assetpreviews\Image as ImagePreview;
use craft\assetpreviews\Pdf;
use craft\assetpreviews\Text;
use craft\assetpreviews\Video;
use craft\base\AssetPreviewHandlerInterface;
use craft\db\Query;
use craft\db\Table;
use craft\elements\Asset;
use craft\elements\db\AssetQuery;
use craft\elements\User;
use craft\errors\AssetException;
use craft\errors\AssetOperationException;
use craft\errors\FsException;
use craft\errors\FsObjectExistsException;
use craft\errors\FsObjectNotFoundException;
use craft\errors\VolumeException;
use craft\events\AssetPreviewEvent;
use craft\events\DefineAssetThumbUrlEvent;
use craft\events\ReplaceAssetEvent;
use craft\fs\Temp;
use craft\helpers\Assets as AssetsHelper;
use craft\helpers\DateTimeHelper;
use craft\helpers\Db;
use craft\helpers\FileHelper;
use craft\helpers\Image;
use craft\helpers\Json;
use craft\helpers\StringHelper;
use craft\imagetransforms\FallbackTransformer;
use craft\models\FolderCriteria;
use craft\models\ImageTransform;
use craft\models\Volume;
use craft\models\VolumeFolder;
use craft\records\VolumeFolder as VolumeFolderRecord;
use yii\base\Component;
use yii\base\Exception;
use yii\base\InvalidArgumentException;
use yii\base\InvalidConfigException;
use yii\base\NotSupportedException;
use yii\db\Expression;

/**
 * Assets service.
 *
 * An instance of the service is available via [[\craft\base\ApplicationTrait::getAssets()|`Craft::$app->assets`]].
 *
 * @property-read VolumeFolder $currentUserTemporaryUploadFolder
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class Assets extends Component
{
    /**
     * @event ReplaceAssetEvent The event that is triggered before an asset is replaced.
     */
    public const EVENT_BEFORE_REPLACE_ASSET = 'beforeReplaceFile';

    /**
     * @event ReplaceAssetEvent The event that is triggered after an asset is replaced.
     */
    public const EVENT_AFTER_REPLACE_ASSET = 'afterReplaceFile';

    /**
     * @event DefineAssetThumbUrlEvent The event that is triggered when a thumbnail is being requested for an asset.
     * @see getThumbUrl()
     * @since 4.0.0
     */
    public const EVENT_DEFINE_THUMB_URL = 'defineThumbUrl';

    /**
     * @event AssetPreviewEvent The event that is triggered when determining the preview handler for an asset.
     * @since 3.4.0
     */
    public const EVENT_REGISTER_PREVIEW_HANDLER = 'registerPreviewHandler';

    /**
     * @var array
     */
    private array $_foldersById = [];

    /**
     * @var array
     */
    private array $_foldersByUid = [];

    /**
     * @var VolumeFolder[]
     * @see getUserTemporaryUploadFolder
     */
    private $_userTempFolders = [];

    /**
     * Returns a file by its ID.
     *
     * @param int $assetId
     * @param int|null $siteId
     * @return Asset|null
     */
    public function getAssetById(int $assetId, ?int $siteId = null): ?Asset
    {
        return Craft::$app->getElements()->getElementById($assetId, Asset::class, $siteId);
    }

    /**
     * Gets the total number of assets that match a given criteria.
     *
     * @param mixed $criteria
     * @return int
     */
    public function getTotalAssets(mixed $criteria = null): int
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
     * Replace an asset's file.
     *
     * @param Asset $asset
     * @param string $pathOnServer
     * @param string $filename
     */
    public function replaceAssetFile(Asset $asset, string $pathOnServer, string $filename): void
    {
        // Fire a 'beforeReplaceFile' event
        if ($this->hasEventHandlers(self::EVENT_BEFORE_REPLACE_ASSET)) {
            $event = new ReplaceAssetEvent([
                'asset' => $asset,
                'replaceWith' => $pathOnServer,
                'filename' => $filename,
            ]);
            $this->trigger(self::EVENT_BEFORE_REPLACE_ASSET, $event);
            $filename = $event->filename;
        }

        $asset->tempFilePath = $pathOnServer;
        $asset->newFilename = $filename;
        $asset->uploaderId = Craft::$app->getUser()->getId();
        $asset->avoidFilenameConflicts = true;
        $asset->setScenario(Asset::SCENARIO_REPLACE);
        Craft::$app->getElements()->saveElement($asset);

        // Fire an 'afterReplaceFile' event
        if ($this->hasEventHandlers(self::EVENT_AFTER_REPLACE_ASSET)) {
            $this->trigger(self::EVENT_AFTER_REPLACE_ASSET, new ReplaceAssetEvent([
                'asset' => $asset,
                'filename' => $filename,
            ]));
        }
    }

    /**
     * Move or rename an asset.
     *
     * @param Asset $asset The asset whose file should be renamed
     * @param VolumeFolder $folder The volume folder to move the asset to.
     * @param string $filename The new filename
     * @return bool Whether the asset was renamed successfully
     */
    public function moveAsset(Asset $asset, VolumeFolder $folder, string $filename = ''): bool
    {
        $folderChanging = $asset->folderId != $folder->id;
        $filenameChanging = $filename !== '' && $filename !== $asset->getFilename();

        if (!$folderChanging && !$filenameChanging) {
            return true;
        }

        if ($folderChanging) {
            $asset->newFolderId = $folder->id;
        }

        if ($filenameChanging) {
            $asset->newFilename = $filename;
            $asset->setScenario(Asset::SCENARIO_FILEOPS);
        } else {
            $asset->setScenario(Asset::SCENARIO_MOVE);
        }

        return Craft::$app->getElements()->saveElement($asset);
    }

    /**
     * Save a volume folder.
     *
     * @param VolumeFolder $folder
     * @throws FsObjectExistsException if a folder already exists with such a name
     * @throws FsException if unable to create the directory on volume
     * @throws AssetException if invalid folder provided
     */
    public function createFolder(VolumeFolder $folder): void
    {
        $parent = $folder->getParent();

        if (!$parent) {
            throw new AssetException('Folder ' . $folder->id . ' doesn’t have a parent.');
        }

        $existingFolder = $this->findFolder([
            'parentId' => $folder->parentId,
            'name' => $folder->name,
        ]);

        if ($existingFolder && (!$folder->id || $folder->id !== $existingFolder->id)) {
            throw new FsObjectExistsException(Craft::t('app',
                'A folder with the name “{folderName}” already exists in the volume.',
                ['folderName' => $folder->name]));
        }

        $volume = $parent->getVolume();
        $path = rtrim($folder->path, '/');

        $volume->createDirectory($path);

        $this->storeFolderRecord($folder);
    }

    /**
     * Renames a folder by its ID.
     *
     * @param int $folderId
     * @param string $newName
     * @return string The new folder name after cleaning it.
     * @throws AssetOperationException If the folder to be renamed can't be found or trying to rename the top folder.
     * @throws FsObjectExistsException
     * @throws FsObjectNotFoundException
     */
    public function renameFolderById(int $folderId, string $newName): string
    {
        $newName = AssetsHelper::prepareAssetName($newName, false);
        $folder = $this->getFolderById($folderId);

        if (!$folder) {
            throw new AssetOperationException(Craft::t('app', 'No folder exists with the ID “{id}”', [
                'id' => $folderId,
            ]));
        }

        if (!$folder->parentId) {
            throw new AssetOperationException(Craft::t('app', 'It’s not possible to rename the top folder of a Volume.'));
        }

        $conflictingFolder = $this->findFolder([
            'parentId' => $folder->parentId,
            'name' => $newName,
        ]);

        if ($conflictingFolder) {
            throw new FsObjectExistsException(Craft::t('app', 'A folder with the name “{folderName}” already exists in the folder.', [
                'folderName' => $newName,
            ]));
        }

        $parentFolderPath = dirname($folder->path);
        $newFolderPath = (($parentFolderPath && $parentFolderPath !== '.') ? $parentFolderPath . '/' : '') . $newName . '/';

        $volume = $folder->getVolume();

        $volume->renameDirectory(rtrim($folder->path, '/'), $newName);
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
     * @param int|array $folderIds
     * @param bool $deleteDir Should the volume directory be deleted along the record, if applicable. Defaults to true.
     * @throws InvalidConfigException if the volume cannot be fetched from folder.
     */
    public function deleteFoldersByIds(int|array $folderIds, bool $deleteDir = true): void
    {
        $folders = [];

        foreach ((array)$folderIds as $folderId) {
            $folder = $this->getFolderById((int)$folderId);
            $folders[] = $folder;

            if ($folder && $deleteDir) {
                $volume = $folder->getVolume();
                try {
                    $volume->deleteDirectory($folder->path);
                } catch (VolumeException $exception) {
                    Craft::$app->getErrorHandler()->logException($exception);
                    // Carry on.
                }
            }
        }

        /** @var Asset[] $assets */
        $assets = Asset::find()->folderId($folderIds)->all();

        $elementService = Craft::$app->getElements();

        foreach ($assets as $asset) {
            $asset->keepFileOnDelete = !$deleteDir;
            $elementService->deleteElement($asset, true);
        }

        foreach ($folders as $folder) {
            $descendants = $this->getAllDescendantFolders($folder);
            usort($descendants, static fn($a, $b) => substr_count($a->path, '/') < substr_count($b->path, '/'));

            foreach ($descendants as $descendant) {
                VolumeFolderRecord::deleteAll(['id' => $descendant->id]);
            }
            VolumeFolderRecord::deleteAll(['id' => $folder->id]);
        }
    }

    /**
     * Returns a list of hierarchical folders for the given volume IDs, indexed by volume ID.
     *
     * @param array $volumeIds
     * @param array $additionalCriteria additional criteria for filtering the tree
     * @return array
     * @deprecated in 4.4.0d
     */
    public function getFolderTreeByVolumeIds(array $volumeIds, array $additionalCriteria = []): array
    {
        static $volumeFolders = [];

        $tree = [];

        // Get the tree for each source
        foreach ($volumeIds as $volumeId) {
            // Add additional criteria but prevent overriding volumeId and order.
            $criteria = array_merge($additionalCriteria, [
                'volumeId' => $volumeId,
                'order' => [new Expression('[[path]] IS NULL DESC'), 'path' => SORT_ASC],
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
     * Returns the folder tree for assets by a folder ID.
     *
     * @param int $folderId
     * @return array
     * @deprecated in 4.4.0
     */
    public function getFolderTreeByFolderId(int $folderId): array
    {
        if (($parentFolder = $this->getFolderById($folderId)) === null) {
            return [];
        }

        $childFolders = $this->getAllDescendantFolders($parentFolder);

        return $this->_getFolderTreeByFolders([$parentFolder] + $childFolders);
    }

    /**
     * Returns a folder by its ID.
     *
     * @param int $folderId
     * @return VolumeFolder|null
     */
    public function getFolderById(int $folderId): ?VolumeFolder
    {
        if (isset($this->_foldersById) && array_key_exists($folderId, $this->_foldersById)) {
            return $this->_foldersById[$folderId];
        }

        $result = $this->createFolderQuery()
            ->where(['id' => $folderId])
            ->one();

        if (!$result) {
            return $this->_foldersById[$folderId] = null;
        }

        return $this->_foldersById[$folderId] = new VolumeFolder($result);
    }

    /**
     * Returns a folder by its UID.
     *
     * @param string $folderUid
     * @return VolumeFolder|null
     */
    public function getFolderByUid(string $folderUid): ?VolumeFolder
    {
        if (isset($this->_foldersByUid) && array_key_exists($folderUid, $this->_foldersByUid)) {
            return $this->_foldersByUid[$folderUid];
        }

        $result = $this->createFolderQuery()
            ->where(['uid' => $folderUid])
            ->one();

        if (!$result) {
            return $this->_foldersByUid[$folderUid] = null;
        }

        return $this->_foldersByUid[$folderUid] = new VolumeFolder($result);
    }

    /**
     * Finds folders that match a given criteria.
     *
     * @param mixed $criteria
     * @return VolumeFolder[]
     */
    public function findFolders(mixed $criteria = []): array
    {
        if (!$criteria instanceof FolderCriteria) {
            $criteria = new FolderCriteria($criteria);
        }

        $query = $this->createFolderQuery();

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
     * @param bool $withParent Whether the parent folder should be included in the results
     * @return VolumeFolder[]
     */
    public function getAllDescendantFolders(VolumeFolder $parentFolder, string $orderBy = 'path', bool $withParent = true): array
    {
        $query = $this->createFolderQuery()
            ->where([
                'and',
                ['volumeId' => $parentFolder->volumeId],
                ['not', ['parentId' => null]],
            ]);

        if ($parentFolder->path !== null) {
            $query->andWhere(['like', 'path', Db::escapeForLike($parentFolder->path) . '%', false]);
        }

        if ($orderBy) {
            $query->orderBy($orderBy);
        }

        if (!$withParent) {
            $query->andWhere(['not', ['id' => $parentFolder->id]]);
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
    public function findFolder(mixed $criteria = []): ?VolumeFolder
    {
        if (!$criteria instanceof FolderCriteria) {
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
    public function getRootFolderByVolumeId(int $volumeId): ?VolumeFolder
    {
        return $this->findFolder([
            'volumeId' => $volumeId,
            'parentId' => ':empty:',
        ]);
    }

    /**
     * Gets the total number of folders that match a given criteria.
     *
     * @param mixed $criteria
     * @return int
     */
    public function getTotalFolders(mixed $criteria): int
    {
        if (!$criteria instanceof FolderCriteria) {
            $criteria = new FolderCriteria($criteria);
        }

        $query = (new Query())
            ->from([Table::VOLUMEFOLDERS]);

        $this->_applyFolderConditions($query, $criteria);

        return (int)$query->count('[[id]]');
    }

    /**
     * Returns whether any folders exist which match a given criteria.
     *
     * @param mixed $criteria
     * @return bool
     * @since 4.4.0
     */
    public function foldersExist($criteria = null): bool
    {
        if (!($criteria instanceof FolderCriteria)) {
            $criteria = new FolderCriteria($criteria);
        }

        $query = (new Query())
            ->from([Table::VOLUMEFOLDERS]);

        $this->_applyFolderConditions($query, $criteria);

        return $query->exists();
    }

    // File and folder managing
    // -------------------------------------------------------------------------

    /**
     * Returns the URL for an asset, possibly with a given transform applied.
     *
     * @param Asset $asset
     * @param ImageTransform|string|array|null $transform
     * @return string|null
     * @throws InvalidConfigException
     * @deprecated in 4.0.0. [[Asset::getUrl()]] should be used instead.
     */
    public function getAssetUrl(Asset $asset, mixed $transform = null): ?string
    {
        return $asset->getUrl($transform);
    }

    /**
     * Returns the control panel thumbnail URL for a given asset.
     *
     * @param Asset $asset asset to return a thumb for
     * @param int $width width of the returned thumb
     * @param int|null $height height of the returned thumb (defaults to $width if null)
     * @return string
     */
    public function getThumbUrl(Asset $asset, int $width, ?int $height = null): string
    {
        if ($height === null) {
            $height = $width;
        }

        // Maybe a plugin wants to do something here
        if ($this->hasEventHandlers(self::EVENT_DEFINE_THUMB_URL)) {
            $event = new DefineAssetThumbUrlEvent([
                'asset' => $asset,
                'width' => $width,
                'height' => $height,
            ]);
            $this->trigger(self::EVENT_DEFINE_THUMB_URL, $event);

            // If a plugin set the url, we'll just use that.
            if ($event->url !== null) {
                return $event->url;
            }
        }

        // If it’s not an image, return a generic file extension icon
        $extension = $asset->getExtension();
        if (!Image::canManipulateAsImage($extension)) {
            return AssetsHelper::iconUrl($extension);
        }

        $transform = new ImageTransform([
            'width' => $width,
            'height' => $height,
            'mode' => 'crop',
        ]);

        $url = $asset->getUrl($transform);

        if (!$url) {
            // Try again with the fallback transformer
            $transform->setTransformer(FallbackTransformer::class);
            $url = $asset->getUrl($transform);
        }

        if ($url === null) {
            return AssetsHelper::iconUrl($extension);
        }

        return AssetsHelper::revUrl($url, $asset, fsOnly: true);
    }

    /**
     * Returns an image asset’s URL, scaled to fit within a max width and height.
     *
     * @param Asset $asset
     * @param int $maxWidth
     * @param int $maxHeight
     * @return string
     * @since 4.0.0
     * @throws NotSupportedException if the asset’s volume doesn’t have a filesystem with public URLs
     */
    public function getImagePreviewUrl(Asset $asset, int $maxWidth, int $maxHeight): string
    {
        $isWebSafe = Image::isWebSafe($asset->getExtension());
        $originalWidth = (int)$asset->getWidth();
        $originalHeight = (int)$asset->getHeight();
        [$width, $height] = AssetsHelper::scaledDimensions((int)$asset->getWidth(), (int)$asset->getHeight(), $maxWidth, $maxHeight);

        if (
            !$isWebSafe ||
            !$asset->getVolume()->getFs()->hasUrls ||
            $originalWidth > $width ||
            $originalHeight > $height
        ) {
            $transform = new ImageTransform([
                'width' => $width,
                'height' => $height,
                'mode' => 'crop',
            ]);
        } else {
            $transform = null;
        }

        $url = $asset->getUrl($transform, true);

        if (!$url) {
            throw new NotSupportedException('A preview URL couldn’t be generated for the asset.');
        }

        return AssetsHelper::revUrl($url, $asset, fsOnly: true);
    }

    /**
     * Returns a generic file extension icon path, that can be used as a fallback
     * for assets that don't have a normal thumbnail.
     *
     * @param Asset $asset
     * @return string
     * @deprecated in 4.0.0. [[AssetsHelper::iconPath()]] should be used instead.
     */
    public function getIconPath(Asset $asset): string
    {
        return AssetsHelper::iconPath($asset->getExtension());
    }

    /**
     * Find a replacement for a filename
     *
     * @param string $originalFilename the original filename for which to find a replacement.
     * @param int $folderId The folder in which to find the replacement
     * @return string If a suitable filename replacement cannot be found.
     * @throws AssetOperationException If a suitable filename replacement cannot be found.
     * @throws InvalidConfigException
     * @throws VolumeException
     */
    public function getNameReplacementInFolder(string $originalFilename, int $folderId): string
    {
        $folder = $this->getFolderById($folderId);

        if (!$folder) {
            throw new InvalidArgumentException('Invalid folder ID: ' . $folderId);
        }

        $volume = $folder->getVolume();

        // A potentially conflicting filename is one that shares the same stem and extension

        // Check for potentially conflicting files in index.
        $extension = pathinfo($originalFilename, PATHINFO_EXTENSION);

        $buildFilename = function(string $name, string $suffix = '') use ($extension) {
            $maxLength = 255 - strlen($suffix);
            if ($extension !== '') {
                $maxLength -= strlen($extension) + 1;
            }
            if (strlen($name) > $maxLength) {
                $name = substr($name, 0, $maxLength);
            }
            return $name . $suffix;
        };

        $baseFileName = $buildFilename(pathinfo($originalFilename, PATHINFO_FILENAME));

        $dbFileList = (new Query())
            ->select(['assets.filename'])
            ->from(['assets' => Table::ASSETS])
            ->innerJoin(['elements' => Table::ELEMENTS], '[[elements.id]] = [[assets.id]]')
            ->where([
                'assets.folderId' => $folderId,
                'elements.dateDeleted' => null,
            ])
            ->andWhere(['like', 'assets.filename', $baseFileName . '%.' . $extension, false])
            ->column();

        $potentialConflicts = [];

        foreach ($dbFileList as $filename) {
            $potentialConflicts[StringHelper::toLowerCase($filename)] = true;
        }

        // Check whether a filename we'd want to use does not exist
        $canUse = static function($filenameToTest) use ($potentialConflicts, $volume, $folder) {
            return !isset($potentialConflicts[mb_strtolower($filenameToTest)]) && !$volume->fileExists($folder->path . $filenameToTest);
        };

        if ($canUse($originalFilename)) {
            return $originalFilename;
        }

        // If the file already ends with something that looks like a timestamp, use that instead.
        if (preg_match('/.*_\d{4}-\d{2}-\d{2}-\d{6}$/', $baseFileName, $matches)) {
            $base = $baseFileName;
        } else {
            $timestamp = DateTimeHelper::currentUTCDateTime()->format('Y-m-d-His');
            $base = $buildFilename($baseFileName, '_' . $timestamp);
        }

        // Append a random string at the end too, to avoid race-conditions
        $base = $buildFilename($base, sprintf('_%s', StringHelper::randomString(4)));

        $increment = 0;

        while (true) {
            // Add the increment (if > 0) and keep the full filename w/ increment & extension from going over 255 chars
            $suffix = $increment ? "_$increment" : '';
            $newFilename = $buildFilename($base, $suffix) . ($extension !== '' ? ".$extension" : '');

            if ($canUse($newFilename)) {
                break;
            }

            if ($increment === 50) {
                throw new AssetOperationException(Craft::t('app', 'Could not find a suitable replacement filename for “{filename}”.', [
                    'filename' => $originalFilename,
                ]));
            }

            $increment++;
        }

        return $newFilename;
    }

    /**
     * Ensures a folder entry exists in the DB for the full path and return its ID. Depending on the use, it's possible to also ensure a physical folder exists.
     *
     * @param string $fullPath The path to ensure the folder exists at.
     * @param Volume $volume
     * @param bool $justRecord If set to false, will also make sure the physical folder exists on the volume.
     * @return VolumeFolder
     * @throws VolumeException if something went catastrophically wrong creating the folder.
     */
    public function ensureFolderByFullPathAndVolume(string $fullPath, Volume $volume, bool $justRecord = true): VolumeFolder
    {
        $parentFolder = Craft::$app->getVolumes()->ensureTopFolder($volume);
        $folderModel = $parentFolder;
        $parentId = $parentFolder->id;

        if ($fullPath !== '') {
            // If we don't have a folder matching these, create a new one
            $parts = preg_split('/\\\\|\//', trim($fullPath, '/\\'));

            // creep up the folder path
            $path = '';

            while (($part = array_shift($parts)) !== null) {
                $path .= $part . '/';

                $parameters = new FolderCriteria([
                    'path' => $path,
                    'volumeId' => $volume->id,
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
                    $volume->createDirectory($path);
                }

                // Set the variables for next iteration.
                $folderId = $folderModel->id;
                $parentId = $folderId;
            }
        }

        return $folderModel;
    }

    /**
     * Store a folder by model
     *
     * @param VolumeFolder $folder
     */
    public function storeFolderRecord(VolumeFolder $folder): void
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
        $folder->uid = $record->uid;
    }

    /**
     * Returns the temporary volume and subpath, if set.
     *
     * @return array
     * @phpstan-return array{Volume|null,string|null}
     * @throws InvalidConfigException If the temp volume is invalid
     * @since 3.7.39
     */
    public function getTempVolumeAndSubpath(): array
    {
        $assetSettings = Craft::$app->getProjectConfig()->get('assets');
        if (empty($assetSettings['tempVolumeUid'])) {
            return [null, null];
        }

        $volume = Craft::$app->getVolumes()->getVolumeByUid($assetSettings['tempVolumeUid']);

        if (!$volume) {
            throw new InvalidConfigException("The Temp Uploads Location is set to an invalid volume UID: {$assetSettings['tempVolumeUid']}");
        }

        /** @var string|null $subpath */
        $subpath = ($assetSettings['tempSubpath'] ?? null) ?: null;
        return [$volume, $subpath];
    }

    /**
     * Creates an asset query that is configured to return assets in the temporary upload location.
     *
     * @return AssetQuery
     * @throws InvalidConfigException If the temp volume is invalid
     * @since 3.7.39
     */
    public function createTempAssetQuery(): AssetQuery
    {
        /** @var Volume|null $volume */
        /** @var string|null $subpath */
        [$volume, $subpath] = $this->getTempVolumeAndSubpath();
        $query = Asset::find();
        if ($volume) {
            $query->volumeId($volume->id);
            if ($subpath) {
                $query->folderPath("$subpath/*");
            }
        } else {
            $query->volumeId(':empty:');
        }
        return $query;
    }

    /**
     * Returns the given user’s temporary upload folder.
     *
     * If no user is provided, the currently-logged in user will be used (if there is one), or a folder named after
     * the current session ID.
     *
     * @param User|null $user
     * @return VolumeFolder
     * @throws VolumeException If no correct volume provided.
     */
    public function getUserTemporaryUploadFolder(?User $user = null): VolumeFolder
    {
        if ($user === null) {
            // Default to the logged-in user, if there is one
            $user = Craft::$app->getUser()->getIdentity();
        }

        $cacheKey = $user->id ?? '__GUEST__';

        if (isset($this->_userTempFolders[$cacheKey])) {
            return $this->_userTempFolders[$cacheKey];
        }

        if ($user) {
            $folderName = 'user_' . $user->id;
        } elseif (Craft::$app->getRequest()->getIsConsoleRequest()) {
            // For console requests, just make up a folder name.
            $folderName = 'temp_' . sha1((string)time());
        } else {
            // A little obfuscation never hurt anyone
            $folderName = 'user_' . sha1(Craft::$app->getSession()->id);
        }

        // Is there a designated temp uploads volume?
        try {
            /** @var Volume|null $tempVolume */
            /** @var string|null $tempSubpath */
            [$tempVolume, $tempSubpath] = $this->getTempVolumeAndSubpath();
        } catch (InvalidConfigException $e) {
            throw new VolumeException($e->getMessage(), 0, $e);
        }

        if ($tempVolume) {
            $path = ($tempSubpath ? "$tempSubpath/" : '') . $folderName;
            return $this->_userTempFolders[$cacheKey] = $this->ensureFolderByFullPathAndVolume($path, $tempVolume);
        }

        $volumeTopFolder = $this->findFolder([
            'volumeId' => ':empty:',
            'parentId' => ':empty:',
        ]);

        // Unlikely, but would be very awkward if this happened without any contingency plans in place.
        if (!$volumeTopFolder) {
            $volumeTopFolder = new VolumeFolder();
            $tempVolume = new Temp();
            $volumeTopFolder->name = $tempVolume->name;
            $this->storeFolderRecord($volumeTopFolder);
        }

        $folder = $this->findFolder([
            'name' => $folderName,
            'parentId' => $volumeTopFolder->id,
        ]);

        if (!$folder) {
            $folder = new VolumeFolder();
            $folder->parentId = $volumeTopFolder->id;
            $folder->name = $folderName;
            $folder->path = $folderName . '/';
            $this->storeFolderRecord($folder);
        }

        try {
            FileHelper::createDirectory(Craft::$app->getPath()->getTempAssetUploadsPath() . DIRECTORY_SEPARATOR . $folderName);
        } catch (Exception) {
            throw new VolumeException('Unable to create directory for temporary volume.');
        }

        return $this->_userTempFolders[$cacheKey] = $folder;
    }

    /**
     * Returns the asset preview handler for a given asset, or `null` if the asset is not previewable.
     *
     * @param Asset $asset
     * @return AssetPreviewHandlerInterface|null
     * @since 3.4.0
     */
    public function getAssetPreviewHandler(Asset $asset): ?AssetPreviewHandlerInterface
    {
        // Give plugins a chance to register their own preview handlers
        if ($this->hasEventHandlers(self::EVENT_REGISTER_PREVIEW_HANDLER)) {
            $event = new AssetPreviewEvent(['asset' => $asset]);
            $this->trigger(self::EVENT_REGISTER_PREVIEW_HANDLER, $event);
            if ($event->previewHandler instanceof AssetPreviewHandlerInterface) {
                return $event->previewHandler;
            }
        }

        // These are our default preview handlers if one is not supplied
        return match ($asset->kind) {
            Asset::KIND_IMAGE => new ImagePreview($asset),
            Asset::KIND_PDF => new Pdf($asset),
            Asset::KIND_VIDEO => new Video($asset),
            Asset::KIND_HTML, Asset::KIND_JAVASCRIPT, Asset::KIND_JSON, Asset::KIND_PHP, Asset::KIND_TEXT, Asset::KIND_XML => new Text($asset),
            default => null,
        };
    }

    /**
     * Returns a DbCommand object prepped for retrieving assets.
     *
     * @return Query
     * @since 4.4.0
     */
    public function createFolderQuery(): Query
    {
        return (new Query())
            ->select(['id', 'parentId', 'volumeId', 'name', 'path', 'uid'])
            ->from([Table::VOLUMEFOLDERS]);
    }

    /**
     * Arranges the given array of folders hierarchically.
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
    private function _applyFolderConditions(Query $query, FolderCriteria $criteria): void
    {
        if ($criteria->id) {
            $query->andWhere(Db::parseNumericParam('id', $criteria->id));
        }

        if ($criteria->volumeId) {
            $query->andWhere(Db::parseNumericParam('volumeId', $criteria->volumeId));
        }

        if ($criteria->parentId) {
            $query->andWhere(Db::parseNumericParam('parentId', $criteria->parentId));
        }

        if ($criteria->name) {
            $query->andWhere(Db::parseParam('name', $criteria->name));
        }

        if ($criteria->uid) {
            $query->andWhere(Db::parseParam('uid', $criteria->uid));
        }

        if ($criteria->path !== null) {
            // Does the path have a comma in it?
            if (str_contains($criteria->path, ',')) {
                // Escape the comma.
                $query->andWhere(Db::parseParam('path', str_replace(',', '\,', $criteria->path)));
            } else {
                $query->andWhere(Db::parseParam('path', $criteria->path));
            }
        }
    }
}
