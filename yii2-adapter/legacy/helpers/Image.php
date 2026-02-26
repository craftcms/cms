<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\helpers;

use CraftCms\Cms\Image\Enums\ExifOrientation;
use CraftCms\Cms\Image\ImageHelper;
use Imagick;

/**
 * @deprecated 6.0.0 use {@see ImageHelper} instead.
 */
class Image
{
    public const EXIF_IFD0_ROTATE_0 = ExifOrientation::Rotate0->value;
    public const EXIF_IFD0_ROTATE_0_MIRRORED = ExifOrientation::Rotate0Mirrored->value;
    public const EXIF_IFD0_ROTATE_180 = ExifOrientation::Rotate180->value;
    public const EXIF_IFD0_ROTATE_180_MIRRORED = ExifOrientation::Rotate180Mirrored->value;
    public const EXIF_IFD0_ROTATE_90_MIRRORED = ExifOrientation::Rotate90Mirrored->value;
    public const EXIF_IFD0_ROTATE_90 = ExifOrientation::Rotate90->value;
    public const EXIF_IFD0_ROTATE_270_MIRRORED = ExifOrientation::Rotate270Mirrored->value;
    public const EXIF_IFD0_ROTATE_270 = ExifOrientation::Rotate270->value;

    public static function calculateMissingDimension(float|int|null $targetWidth, float|int|null $targetHeight, float|int $sourceWidth, float|int $sourceHeight): array
    {
        return ImageHelper::calculateMissingDimension($targetWidth, $targetHeight, $sourceWidth, $sourceHeight);
    }

    public static function targetDimensions(
        int $sourceWidth,
        int $sourceHeight,
        ?int $transformWidth,
        ?int $transformHeight,
        string $mode = 'crop',
        ?bool $upscale = null,
    ): array {
        return ImageHelper::targetDimensions($sourceWidth, $sourceHeight, $transformWidth, $transformHeight, $mode, $upscale);
    }

    public static function canManipulateAsImage(string $extension): bool
    {
        return ImageHelper::canManipulateAsImage($extension);
    }

    public static function webSafeFormats(): array
    {
        return ImageHelper::webSafeFormats();
    }

    public static function isWebSafe(string $extension): bool
    {
        return ImageHelper::isWebSafe($extension);
    }

    public static function pngImageInfo(string $file): array|false
    {
        return ImageHelper::pngImageInfo($file);
    }

    public static function canHaveExifData(string $filePath): bool
    {
        return ImageHelper::canHaveExifData($filePath);
    }

    public static function cleanImageByPath(string $imagePath): void
    {
        ImageHelper::cleanImageByPath($imagePath);
    }

    public static function imageSize(string $filePath): array
    {
        return ImageHelper::imageSize($filePath);
    }

    public static function imageSizeByStream($stream): array|false
    {
        return ImageHelper::imageSizeByStream($stream);
    }

    public static function parseSvgSize(string $svg): array
    {
        return ImageHelper::parseSvgSize($svg);
    }

    public static function cleanExifDataFromImagickImage(Imagick $imagick): void
    {
        ImageHelper::cleanExifDataFromImagickImage($imagick);
    }
}
