<?php

/**
 * @link https://craftcms.com/
 *
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\services;

use Craft;
use craft\db\Query;
use craft\db\Table;
use craft\events\AssetPreviewEvent;

use craft\events\DefineAssetThumbUrlEvent;
use craft\events\ReplaceAssetEvent;
use CraftCms\Cms\Asset\Assets as AssetsService;
use CraftCms\Cms\Asset\Contracts\AssetPreviewHandlerInterface;
use CraftCms\Cms\Asset\Data\Volume;
use CraftCms\Cms\Asset\Data\VolumeFolder;
use CraftCms\Cms\Asset\Elements\Asset;
use CraftCms\Cms\Asset\Events\AssetReplaced;
use CraftCms\Cms\Asset\Events\AssetReplacing;
use CraftCms\Cms\Asset\Events\PreviewHandlerResolving;
use CraftCms\Cms\Asset\Events\ThumbUrlResolving;
use CraftCms\Cms\Asset\Folders;
use CraftCms\Cms\Element\Queries\AssetQuery;
use CraftCms\Cms\Filesystem\Contracts\FsInterface;
use CraftCms\Cms\Support\Json;
use CraftCms\Cms\User\Elements\User;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Event as EventFacade;
use yii\base\Component;
use yii\db\Expression;

/**
 * Assets service.
 *
 * An instance of the service is available via [[\craft\base\ApplicationTrait::getAssets()|`Craft::$app->getAssets()`]].
 *
 * @property-read VolumeFolder $currentUserTemporaryUploadFolder
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 *
 * @since 3.0.0
 * @deprecated in 6.0.0. Use {@see \CraftCms\Cms\Asset\Assets} and {@see \CraftCms\Cms\Asset\Folders} instead.
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
     *
     * @see getThumbUrl()
     * @since 4.0.0
     */
    public const EVENT_DEFINE_THUMB_URL = 'defineThumbUrl';

    /**
     * @event AssetPreviewEvent The event that is triggered when determining the preview handler for an asset.
     *
     * @since 3.4.0
     */
    public const EVENT_REGISTER_PREVIEW_HANDLER = 'registerPreviewHandler';

    public function getAssetById(int $assetId, ?int $siteId = null): ?Asset
    {
        return $this->assetsService()->getAssetById($assetId, $siteId);
    }

    public function getTotalAssets(mixed $criteria = null): int
    {
        if ($criteria instanceof AssetQuery) {
            return $criteria->count();
        }

        return $this->assetsService()->getTotalAssets($criteria);
    }

    public function replaceAssetFile(Asset $asset, string $pathOnServer, string $filename, ?string $mimeType = null): void
    {
        $this->assetsService()->replaceAssetFile($asset, $pathOnServer, $filename, $mimeType);
    }

    public function moveAsset(Asset $asset, VolumeFolder $folder, string $filename = ''): bool
    {
        return $this->assetsService()->moveAsset($asset, $folder, $filename);
    }

    public function createFolder(VolumeFolder $folder): void
    {
        $this->foldersService()->createFolder($folder);
    }

    public function renameFolderById(int $folderId, string $newName): string
    {
        return $this->foldersService()->renameFolderById($folderId, $newName);
    }

    public function deleteFoldersByIds(int|array $folderIds, bool $deleteDir = true): void
    {
        $this->foldersService()->deleteFoldersByIds($folderIds, $deleteDir);
    }

    /**
     * @deprecated in 4.4.0
     */
    public function getFolderTreeByVolumeIds(array $volumeIds, array $additionalCriteria = []): array
    {
        static $volumeFolders = [];

        $tree = [];

        foreach ($volumeIds as $volumeId) {
            $criteria = array_merge($additionalCriteria, [
                'volumeId' => $volumeId,
                'order' => [new Expression('[[path]] IS NULL DESC'), 'path' => SORT_ASC],
            ]);
            $cacheKey = md5(Json::encode($criteria));

            if (empty($volumeFolders[$cacheKey])) {
                $folders = $this->findFolders($criteria);

                if (empty($folders)) {
                    continue;
                }

                $subtree = $this->foldersService()->getAllDescendantFolders(
                    reset($folders),
                    asTree: true,
                );
                $volumeFolders[$cacheKey] = reset($subtree);
            }

            $tree[$volumeId] = $volumeFolders[$cacheKey];
        }

        \craft\helpers\Assets::sortFolderTree($tree);

        return $tree;
    }

    /**
     * @deprecated in 4.4.0
     */
    public function getFolderTreeByFolderId(int $folderId): array
    {
        if (($parentFolder = $this->getFolderById($folderId)) === null) {
            return [];
        }

        return $this->foldersService()->getAllDescendantFolders($parentFolder, asTree: true);
    }

    public function getFolderById(int $folderId): ?VolumeFolder
    {
        return $this->foldersService()->getFolderById($folderId);
    }

    public function getFolderByUid(string $folderUid): ?VolumeFolder
    {
        return $this->foldersService()->getFolderByUid($folderUid);
    }

    /**
     * @return VolumeFolder[]
     */
    public function findFolders(mixed $criteria = []): array
    {
        return $this->foldersService()->findFolders($criteria)->all();
    }

    /**
     * @return array<int, VolumeFolder>
     */
    public function getAllDescendantFolders(
        VolumeFolder $parentFolder,
        string $orderBy = 'path',
        bool $withParent = true,
        bool $asTree = false,
    ): array {
        return $this->foldersService()->getAllDescendantFolders($parentFolder, $orderBy, $withParent, $asTree);
    }

    public function findFolder(mixed $criteria = []): ?VolumeFolder
    {
        return $this->foldersService()->findFolder($criteria);
    }

    public function getRootFolderByVolumeId(int $volumeId): ?VolumeFolder
    {
        return $this->foldersService()->getRootFolderByVolumeId($volumeId);
    }

    public function getTotalFolders(mixed $criteria): int
    {
        return $this->foldersService()->getTotalFolders($criteria);
    }

    public function foldersExist($criteria = null): bool
    {
        return $this->foldersService()->foldersExist($criteria);
    }

    /**
     * @deprecated in 4.0.0. [[Asset::getUrl()]] should be used instead.
     */
    public function getAssetUrl(Asset $asset, mixed $transform = null): ?string
    {
        return $asset->getUrl($transform);
    }

    public function getThumbUrl(Asset $asset, int $width, ?int $height = null, $iconFallback = true): ?string
    {
        return $this->assetsService()->getThumbUrl($asset, $width, $height, $iconFallback);
    }

    public function getImagePreviewUrl(Asset $asset, int $maxWidth, int $maxHeight): string
    {
        return $this->assetsService()->getImagePreviewUrl($asset, $maxWidth, $maxHeight);
    }

    /**
     * @deprecated in 4.0.0. [[AssetsHelper::iconSvg()]] or [[Asset::getThumbSvg()]] should be used instead.
     */
    public function getIconPath(Asset $asset): string
    {
        return \craft\helpers\Assets::iconPath($asset->getExtension());
    }

    public function getNameReplacementInFolder(string $originalFilename, int $folderId): string
    {
        return $this->assetsService()->getNameReplacementInFolder($originalFilename, $folderId);
    }

    public function ensureFolderByFullPathAndVolume(string $fullPath, Volume $volume, bool $justRecord = true): VolumeFolder
    {
        return $this->foldersService()->ensureFolderByFullPathAndVolume($fullPath, $volume, $justRecord);
    }

    public function storeFolderRecord(VolumeFolder $folder): void
    {
        $this->foldersService()->storeFolderModel($folder);
    }

    public function getTempAssetUploadFs(): FsInterface
    {
        return $this->assetsService()->getTempAssetUploadFs();
    }

    public function getTempAssetUploadDisk(): FilesystemAdapter
    {
        return $this->assetsService()->getTempAssetUploadDisk();
    }

    public function createTempAssetQuery(): AssetQuery
    {
        $query = new AssetQuery();
        $query->volumeId(':empty:');

        return $query;
    }

    public function getUserTemporaryUploadFolder(?User $user = null): VolumeFolder
    {
        return $this->assetsService()->getUserTemporaryUploadFolder($user);
    }

    public function getAssetPreviewHandler(Asset $asset): ?AssetPreviewHandlerInterface
    {
        return $this->assetsService()->getAssetPreviewHandler($asset);
    }

    /**
     * @since 4.4.0
     */
    public function createFolderQuery(): Query
    {
        return (new Query())
            ->select(['id', 'parentId', 'volumeId', 'name', 'path', 'uid'])
            ->from([Table::VOLUMEFOLDERS]);
    }

    public static function registerEvents(): void
    {
        EventFacade::listen(AssetReplacing::class, function(AssetReplacing $event) {
            if (!Craft::$app->getAssets()->hasEventHandlers(self::EVENT_BEFORE_REPLACE_ASSET)) {
                return;
            }

            $yiiEvent = new ReplaceAssetEvent([
                'asset' => $event->asset,
                'replaceWith' => $event->replaceWith,
                'filename' => $event->filename,
            ]);
            Craft::$app->getAssets()->trigger(self::EVENT_BEFORE_REPLACE_ASSET, $yiiEvent);
            $event->filename = $yiiEvent->filename;
        });

        EventFacade::listen(AssetReplaced::class, function(AssetReplaced $event) {
            if (!Craft::$app->getAssets()->hasEventHandlers(self::EVENT_AFTER_REPLACE_ASSET)) {
                return;
            }

            Craft::$app->getAssets()->trigger(self::EVENT_AFTER_REPLACE_ASSET, new ReplaceAssetEvent([
                'asset' => $event->asset,
                'filename' => $event->filename,
            ]));
        });

        EventFacade::listen(ThumbUrlResolving::class, function(ThumbUrlResolving $event) {
            if (!Craft::$app->getAssets()->hasEventHandlers(self::EVENT_DEFINE_THUMB_URL)) {
                return;
            }

            $yiiEvent = new DefineAssetThumbUrlEvent([
                'asset' => $event->asset,
                'width' => $event->width,
                'height' => $event->height,
            ]);
            Craft::$app->getAssets()->trigger(self::EVENT_DEFINE_THUMB_URL, $yiiEvent);

            if ($yiiEvent->url !== null) {
                $event->url = $yiiEvent->url;
            }
        });

        EventFacade::listen(PreviewHandlerResolving::class, function(PreviewHandlerResolving $event) {
            if (!Craft::$app->getAssets()->hasEventHandlers(self::EVENT_REGISTER_PREVIEW_HANDLER)) {
                return;
            }

            $yiiEvent = new AssetPreviewEvent(['asset' => $event->asset]);
            Craft::$app->getAssets()->trigger(self::EVENT_REGISTER_PREVIEW_HANDLER, $yiiEvent);

            if ($yiiEvent->previewHandler instanceof AssetPreviewHandlerInterface) {
                $event->previewHandler = $yiiEvent->previewHandler;
            }
        });
    }

    private function assetsService(): AssetsService
    {
        return app(AssetsService::class);
    }

    private function foldersService(): Folders
    {
        return app(Folders::class);
    }
}
