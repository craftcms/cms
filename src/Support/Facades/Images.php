<?php

declare(strict_types=1);

namespace CraftCms\Cms\Support\Facades;

use CraftCms\Cms\Image\Image;
use Illuminate\Support\Facades\Facade;
use Override;

/**
 * @method static bool getIsGd()
 * @method static bool getIsImagick()
 * @method static string getVersion()
 * @method static string[] getSupportedImageFormats()
 * @method static void setSupportedImageFormats(array $supportedImageFormats)
 * @method static string getImageMagickApiVersion()
 * @method static bool getCanUseImagick()
 * @method static bool getSupportsWebP()
 * @method static bool getSupportsAvif()
 * @method static bool getSupportsHeic()
 * @method static Image loadImage(string $path, bool $rasterize = false, int $svgSize = 1000)
 * @method static bool checkMemoryForImage(string $filePath, bool $toTheMax = false)
 * @method static void cleanImage(string $filePath)
 * @method static bool rotateImageByExifData(string $filePath)
 * @method static array|null getExifData(string $filePath)
 * @method static bool stripOrientationFromExifData(string $filePath)
 *
 * @see \CraftCms\Cms\Image\Images
 */
final class Images extends Facade
{
    #[Override]
    protected static function getFacadeAccessor(): string
    {
        return \CraftCms\Cms\Image\Images::class;
    }
}
