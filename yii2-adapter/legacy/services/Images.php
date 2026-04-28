<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\services;

use CraftCms\Cms\Image\Enums\ImageDriver;
use CraftCms\Cms\Image\Image;
use CraftCms\Cms\Image\Images as ImagesService;
use yii\base\Component;
use yii\base\Exception;

/**
 * Images service.
 *
 * An instance of the service is available via [[\craft\base\ApplicationTrait::getImages()|`Craft::$app->getImages()`]].
 *
 * @property bool $isGd Whether image manipulations will be performed using GD or not
 * @property bool $isImagick Whether image manipulations will be performed using Imagick or not
 * @property array $supportedImageFormats A list of all supported image formats
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 * @deprecated 6.0.0 use {@see ImagesService} instead.
 */
class Images extends Component
{
    public const DRIVER_GD = ImageDriver::Gd->value;
    public const DRIVER_IMAGICK = ImageDriver::Imagick->value;
    public const MINIMUM_IMAGICK_VERSION = ImagesService::MINIMUM_IMAGICK_VERSION;

    public function __serialize(): array
    {
        return get_object_vars($this);
    }

    /**
     * @param string[] $supportedImageFormats
     */
    public function setSupportedImageFormats(array $supportedImageFormats): void
    {
        $this->service()->setSupportedImageFormats($supportedImageFormats);
    }

    public function getIsGd(): bool
    {
        return $this->service()->getIsGd();
    }

    public function getIsImagick(): bool
    {
        return $this->service()->getIsImagick();
    }

    public function getVersion(): string
    {
        return $this->service()->getVersion();
    }

    /**
     * @return string[]
     */
    public function getSupportedImageFormats(): array
    {
        return $this->service()->getSupportedImageFormats();
    }

    /**
     * @throws Exception if the Imagick extension isn’t installed
     */
    public function getImageMagickApiVersion(): string
    {
        return $this->service()->getImageMagickApiVersion();
    }

    public function getCanUseImagick(): bool
    {
        return $this->service()->getCanUseImagick();
    }

    public function getSupportsWebP(): bool
    {
        return $this->service()->getSupportsWebP();
    }

    public function getSupportsAvif(): bool
    {
        return $this->service()->getSupportsAvif();
    }

    public function getSupportsHeic(): bool
    {
        return $this->service()->getSupportsHeic();
    }

    public function loadImage(string $path, bool $rasterize = false, int $svgSize = 1000): Image
    {
        return $this->service()->loadImage($path, $rasterize, $svgSize);
    }

    public function checkMemoryForImage(string $filePath, bool $toTheMax = false): bool
    {
        return $this->service()->checkMemoryForImage($filePath, $toTheMax);
    }

    /**
     * @throws Exception if $filePath is a malformed SVG image
     */
    public function cleanImage(string $filePath): void
    {
        $this->service()->cleanImage($filePath);
    }

    public function rotateImageByExifData(string $filePath): bool
    {
        return $this->service()->rotateImageByExifData($filePath);
    }

    public function getExifData(string $filePath): ?array
    {
        return $this->service()->getExifData($filePath);
    }

    public function stripOrientationFromExifData(string $filePath): bool
    {
        return $this->service()->stripOrientationFromExifData($filePath);
    }

    private function service(): ImagesService
    {
        return app(ImagesService::class);
    }
}
