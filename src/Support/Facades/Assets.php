<?php

declare(strict_types=1);

namespace CraftCms\Cms\Support\Facades;

use Illuminate\Support\Facades\Facade;
use Override;

/**
 * @method static \CraftCms\Cms\Asset\Elements\Asset|null getAssetById(int $assetId, ?int $siteId = null)
 * @method static int getTotalAssets(mixed $criteria = null)
 * @method static void replaceAssetFile(\CraftCms\Cms\Asset\Elements\Asset $asset, string $pathOnServer, string $filename, ?string $mimeType = null)
 * @method static bool moveAsset(\CraftCms\Cms\Asset\Elements\Asset $asset, \CraftCms\Cms\Asset\Data\VolumeFolder $folder, string $filename = '')
 * @method static string|null getThumbUrl(\CraftCms\Cms\Asset\Elements\Asset $asset, int $width, ?int $height = null, bool $iconFallback = true)
 * @method static string getImagePreviewUrl(\CraftCms\Cms\Asset\Elements\Asset $asset, int $maxWidth, int $maxHeight)
 * @method static string getNameReplacementInFolder(string $originalFilename, int $folderId)
 * @method static \craft\base\AssetPreviewHandlerInterface|null getAssetPreviewHandler(\CraftCms\Cms\Asset\Elements\Asset $asset)
 * @method static \CraftCms\Cms\Filesystem\Contracts\FsInterface getTempAssetUploadFs()
 * @method static \Illuminate\Filesystem\FilesystemAdapter getTempAssetUploadDisk()
 * @method static \CraftCms\Cms\Asset\Data\VolumeFolder getUserTemporaryUploadFolder(?\CraftCms\Cms\User\Elements\User $user = null)
 * @method static \craft\elements\db\AssetQuery createTempAssetQuery()
 * @method static void reset()
 *
 * @see \CraftCms\Cms\Asset\Assets
 */
final class Assets extends Facade
{
    #[Override]
    protected static function getFacadeAccessor(): string
    {
        return \CraftCms\Cms\Asset\Assets::class;
    }
}
