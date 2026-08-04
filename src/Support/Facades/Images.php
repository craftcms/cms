<?php

declare(strict_types=1);

namespace CraftCms\Cms\Support\Facades;

use Illuminate\Support\Facades\Facade;
use Override;

/**
 * @method static void setSupportedImageFormats(string[] $supportedImageFormats)
 * @method static bool getIsGd()
 * @method static bool getIsImagick()
 * @method static bool getIsVips()
 * @method static \CraftCms\Cms\Image\Enums\ImageDriver getDriver()
 * @method static \Intervention\Image\ImageManager getManager()
 * @method static string getVersion()
 * @method static string[] getSupportedImageFormats()
 * @method static string getImageMagickApiVersion()
 * @method static bool getCanUseImagick()
 * @method static bool getSupportsWebP()
 * @method static bool getSupportsAvif()
 * @method static bool getSupportsHeic()
 * @method static bool getCanRasterizeSvg()
 * @method static bool supportsFormat(\Intervention\Image\Format|\Intervention\Image\FileExtension|string $format)
 * @method static bool canDecodeFormat(\Intervention\Image\Format|\Intervention\Image\FileExtension|string $format)
 * @method static \CraftCms\Cms\Image\Image loadImage(string $path, bool $rasterize = false, int $svgSize = 1000)
 * @method static bool checkMemoryForImage(string $filePath, bool $toTheMax = false)
 * @method static void cleanImage(string $filePath)
 * @method static bool rotateImageByExifData(string $filePath)
 * @method static array|null getExifData(string $filePath)
 * @method static bool stripOrientationFromExifData(string $filePath)
 *
 * @see \CraftCms\Cms\Image\Images
 */
class Images extends Facade
{
    #[Override]
    protected static function getFacadeAccessor(): string
    {
        return \CraftCms\Cms\Image\Images::class;
    }
}
