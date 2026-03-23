<?php

/**
 * @link https://craftcms.com/
 *
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\helpers;

use craft\base\Event as YiiEvent;
use craft\events\RegisterAssetFileKindsEvent;
use craft\events\SetAssetFilenameEvent;
use craft\helpers\ImageTransforms as TransformHelper;
use CraftCms\Cms\Asset\AssetsHelper;
use CraftCms\Cms\Asset\Data\VolumeFolder;
use CraftCms\Cms\Asset\Elements\Asset;
use CraftCms\Cms\Asset\Events\RegisterFileKinds;
use CraftCms\Cms\Asset\Events\SetAssetFilename;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Shared\Enums\TimePeriod;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\Facades\Assets as AssetsFacade;
use CraftCms\Cms\Support\Facades\Images;
use CraftCms\Cms\Support\Facades\Path;
use CraftCms\Cms\Support\File;
use CraftCms\Cms\Support\URL;
use DateTime;
use Exception;
use Illuminate\Filesystem\LocalFilesystemAdapter;
use Illuminate\Support\Facades\Event;
use InvalidArgumentException;

/**
 * Class Assets
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 *
 * @since 3.0.0
 * @deprecated 6.0.0 use {@see AssetsHelper} instead
 */
class Assets extends AssetsHelper
{
    /**
     * @event SetAssetFilenameEvent The event that is triggered when defining an asset’s filename.
     */
    public const EVENT_SET_FILENAME = 'setFilename';

    /**
     * @event RegisterAssetFileKindsEvent The event that is triggered when registering asset file kinds.
     */
    public const EVENT_REGISTER_FILE_KINDS = 'registerFileKinds';

    /**
     * Get appendix for a URL based on its Source caching settings.
     *
     * @param  DateTime|null  $dateUpdated  last datetime the target of the url was updated, if known
     *
     * @deprecated in 4.0.0. [[generateUrl()]] should be used instead.
     */
    public static function urlAppendix(Asset $asset, ?DateTime $dateUpdated = null): string
    {
        if (!Cms::config()->revAssetUrls) {
            return '';
        }

        $revParams = self::revParams($asset, $dateUpdated);

        return sprintf('?%s', URL::buildQuery($revParams));
    }

    /**
     * Sorts a folder tree by the volume sort order.
     *
     * @param  VolumeFolder[]  $tree  array passed by reference of the sortable folders.
     *
     * @param-out VolumeFolder[] $tree
     *
     * @deprecated in 4.4.0
     */
    public static function sortFolderTree(array &$tree): void
    {
        /** @var VolumeFolder[] $sorted */
        $sorted = Arr::sort($tree, fn($folder) => $folder->getVolume()->sortOrder);

        $tree = $sorted;
    }

    /**
     * Returns the URL to an asset icon for a given extension.
     *
     * @deprecated in 4.5.0
     */
    public static function iconUrl(string $extension): string
    {
        if (!preg_match('/^\w+$/', $extension)) {
            throw new InvalidArgumentException("$extension isn’t a valid file extension.");
        }

        return URL::actionUrl('assets/icon', [
            'extension' => $extension,
        ]);
    }

    /**
     * Returns the file path to an asset icon for a given extension.
     *
     * @deprecated in 4.5.0. [[iconSvg()]] or [[Asset::getThumbSvg()]] should be used instead.
     */
    public static function iconPath(string $extension): string
    {
        if (!preg_match('/^\w+$/', $extension)) {
            throw new InvalidArgumentException("$extension isn’t a valid file extension.");
        }

        $path = Path::assetsIcons(strtolower($extension));

        if (file_exists($path)) {
            return $path;
        }

        $svg = static::iconSvg($extension);

        File::writeToFile($path, $svg);

        return $path;
    }

    /**
     * Get a list of available periods for Cache duration settings.
     */
    public static function periodList(): array
    {
        return [
            TimePeriod::Seconds->value => TimePeriod::Seconds->label(),
            TimePeriod::Minutes->value => TimePeriod::Minutes->label(),
            TimePeriod::Hours->value => TimePeriod::Hours->label(),
            TimePeriod::Days->value => TimePeriod::Days->label(),
            TimePeriod::Weeks->value => TimePeriod::Weeks->label(),
            TimePeriod::Months->value => TimePeriod::Months->label(),
            TimePeriod::Years->value => TimePeriod::Years->label(),
        ];
    }

    /**
     * Return an image path to use in the Image Editor for an asset by its ID and size.
     *
     * @throws Exception in case of failure
     */
    public static function getImageEditorSource(int $assetId, int $size): string|false
    {
        $asset = AssetsFacade::getAssetById($assetId);

        if (!$asset || !Image::canManipulateAsImage($asset->getExtension())) {
            return false;
        }

        $volume = $asset->getVolume();

        $imagePath = Path::imageEditorSources();
        $assetSourcesDirectory = $imagePath . '/' . $assetId;
        $targetSizedPath = $assetSourcesDirectory . '/' . $size;
        $targetFilePath = $targetSizedPath . '/' . $assetId . '.' . $asset->getExtension();
        File::ensureDirectoryExists($targetSizedPath);

        // You never know.
        if (is_file($targetFilePath)) {
            return $targetFilePath;
        }

        // Maybe we have larger sources available we can use.
        if (File::makeDirectory($assetSourcesDirectory)) {
            $handle = opendir($assetSourcesDirectory);

            if ($handle === false) {
                throw new Exception("Unable to open directory: $assetSourcesDirectory");
            }

            while (($subDir = readdir($handle)) !== false) {
                if ($subDir === '.') {
                    continue;
                }
                if ($subDir === '..') {
                    continue;
                }
                $existingSize = $subDir;
                $existingAsset = $assetSourcesDirectory . DIRECTORY_SEPARATOR . $subDir . '/' . $assetId . '.' . $asset->getExtension();
                if ($existingSize >= $size && is_file($existingAsset)) {
                    Images::loadImage($existingAsset)
                        ->scaleToFit($size, $size, false)
                        ->saveAs($targetFilePath);

                    return $targetFilePath;
                }
            }
            closedir($handle);
        }

        // No existing resources we could use.

        // For remote files, check if maxCachedImageSizes setting would work for us.
        $maxCachedSize = Cms::config()->maxCachedCloudImageSize;
        $isLocalFs = $volume->sourceDisk() instanceof LocalFilesystemAdapter;

        if (!$isLocalFs && $maxCachedSize > $size) {
            // For remote sources we get a transform source, if maxCachedImageSizes is not smaller than that.
            $localSource = TransformHelper::getLocalImageSource($asset);
            Images::loadImage($localSource)->scaleToFit($size, $size, false)->saveAs($targetFilePath);
        } else {
            // For local source or if cached versions are smaller or not allowed, get a copy, size it and delete afterwards
            $localSource = $asset->getCopyOfFile();
            Images::loadImage($localSource)->scaleToFit($size, $size, false)->saveAs($targetFilePath);
            File::delete($localSource);
        }

        return $targetFilePath;
    }

    public static function registerEvents(): void
    {
        Event::listen(function(SetAssetFilename $event) {
            if (YiiEvent::hasHandlers(self::class, self::EVENT_SET_FILENAME)) {
                $yiiEvent = new SetAssetFilenameEvent([
                    'filename' => $event->filename,
                    'originalFilename' => $event->originalBaseName,
                    'extension' => $event->extension,
                ]);
                YiiEvent::trigger(self::class, self::EVENT_SET_FILENAME, $yiiEvent);
                $event->filename = $yiiEvent->filename;
                $event->extension = $yiiEvent->extension;
            }
        });

        Event::listen(function(RegisterFileKinds $event) {
            if (YiiEvent::hasHandlers(self::class, self::EVENT_REGISTER_FILE_KINDS)) {
                $yiiEvent = new RegisterAssetFileKindsEvent(['fileKinds' => $event->fileKinds]);
                YiiEvent::trigger(self::class, self::EVENT_REGISTER_FILE_KINDS, $yiiEvent);
                $event->fileKinds = $yiiEvent->fileKinds;
            }
        });
    }
}
