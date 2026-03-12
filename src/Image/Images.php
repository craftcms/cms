<?php

declare(strict_types=1);

namespace CraftCms\Cms\Image;

use craft\helpers\FileHelper;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Image\Enums\ExifOrientation;
use CraftCms\Cms\Image\Enums\ImageDriver;
use CraftCms\Cms\Support\PHP;
use enshrined\svgSanitize\Sanitizer;
use Illuminate\Container\Attributes\Singleton;
use Illuminate\Support\Facades\Log;
use Imagick;
use Imagine\Gd\Imagine as GdImagine;
use Imagine\Image\Format;
use Imagine\Imagick\Imagine as ImagickImagine;
use Throwable;
use yii\base\Exception;

use function CraftCms\Cms\maxPowerCaptain;

#[Singleton]
class Images
{
    public const string MINIMUM_IMAGICK_VERSION = '6.2.9';

    /** @var string[] */
    private array $supportedImageFormats = ['jpg', 'jpeg', 'gif', 'png'];

    private ImageDriver $driver;

    private ?string $imagickVersion = null;

    public function __construct()
    {
        if (strtolower((string) Cms::config()->imageDriver) === ImageDriver::Gd->value) {
            $this->driver = ImageDriver::Gd;

            return;
        }

        $this->driver = $this->getCanUseImagick()
            ? ImageDriver::Imagick
            : ImageDriver::Gd;
    }

    /**
     * @param  string[]  $supportedImageFormats
     */
    public function setSupportedImageFormats(array $supportedImageFormats): void
    {
        $this->supportedImageFormats = $supportedImageFormats;
    }

    public function getIsGd(): bool
    {
        return $this->driver === ImageDriver::Gd;
    }

    public function getIsImagick(): bool
    {
        return $this->driver === ImageDriver::Imagick;
    }

    public function getVersion(): string
    {
        if ($this->getIsGd()) {
            return PHP::extensionVersion('gd');
        }

        $version = PHP::extensionVersion('imagick');

        try {
            $version .= ' (ImageMagick '.$this->getImageMagickApiVersion().')';
        } catch (Throwable) {
        }

        return $version;
    }

    /**
     * @return string[]
     */
    public function getSupportedImageFormats(): array
    {
        $supportedFormats = $this->supportedImageFormats;

        if ($this->getSupportsWebP()) {
            $supportedFormats[] = Format::ID_WEBP;
        }

        if ($this->getSupportsAvif()) {
            $supportedFormats[] = Format::ID_AVIF;
        }

        if ($this->getSupportsHeic()) {
            $supportedFormats[] = Format::ID_HEIC;
        }

        return $supportedFormats;
    }

    /**
     * @throws Exception if the Imagick extension isn’t installed
     */
    public function getImageMagickApiVersion(): string
    {
        if (isset($this->imagickVersion)) {
            return $this->imagickVersion;
        }

        if (! extension_loaded('imagick')) {
            throw new Exception('The Imagick extension isn’t loaded.');
        }

        $versionString = Imagick::getVersion()['versionString'];
        [$this->imagickVersion] = sscanf($versionString, 'ImageMagick %s %04d-%02d-%02d %s %s');

        return $this->imagickVersion;
    }

    public function getCanUseImagick(): bool
    {
        if (! extension_loaded('imagick')) {
            return false;
        }

        // https://github.com/craftcms/cms/issues/5435
        if (empty(Imagick::queryFormats())) {
            return false;
        }

        return version_compare($this->getImageMagickApiVersion(), self::MINIMUM_IMAGICK_VERSION) !== -1;
    }

    public function getSupportsWebP(): bool
    {
        $info = $this->getIsImagick() ? ImagickImagine::getDriverInfo() : GdImagine::getDriverInfo();

        return $info->isFormatSupported(Format::ID_WEBP);
    }

    public function getSupportsAvif(): bool
    {
        $info = $this->getIsImagick() ? ImagickImagine::getDriverInfo() : GdImagine::getDriverInfo();

        return $info->isFormatSupported(Format::ID_AVIF);
    }

    public function getSupportsHeic(): bool
    {
        $info = $this->getIsImagick() ? ImagickImagine::getDriverInfo() : GdImagine::getDriverInfo();

        return $info->isFormatSupported(Format::ID_HEIC);
    }

    public function loadImage(string $path, bool $rasterize = false, int $svgSize = 1000): Image
    {
        if (FileHelper::isSvg($path)) {
            $image = new Svg;
            $image->loadImage($path);

            if ($rasterize) {
                $image->scaleToFit($svgSize, $svgSize);
                $svgString = $image->getSvgString();
                $image = new Raster;
                $image->loadFromSVG($svgString);
            }
        } else {
            $image = new Raster;
            $image->loadImage($path);
        }

        return $image;
    }

    public function checkMemoryForImage(string $filePath, bool $toTheMax = false): bool
    {
        if (FileHelper::isSvg($filePath)) {
            return true;
        }

        if (! function_exists('memory_get_usage')) {
            return false;
        }

        if ($toTheMax) {
            maxPowerCaptain();
        }

        if (! filesize($filePath)) {
            return true;
        }

        $imageInfo = getimagesize($filePath);

        if (! is_array($imageInfo)) {
            Log::info('Could not determine image information for '.$filePath);

            return true;
        }

        $bits = $imageInfo['bits'] ?? 8;
        $channels = $imageInfo['channels'] ?? 4;
        $memoryNeeded = round(($imageInfo[0] * $imageInfo[1] * $bits * $channels / 8 + 65536) * 1.7);

        $memoryLimit = PHP::sizeToBytes(ini_get('memory_limit'));

        if ($memoryLimit == -1 || memory_get_usage() + $memoryNeeded < $memoryLimit) {
            return true;
        }

        if (! $toTheMax) {
            return $this->checkMemoryForImage($filePath, true);
        }

        return false;
    }

    /**
     * @throws Exception if $filePath is a malformed SVG image
     */
    public function cleanImage(string $filePath): void
    {
        $cleanedByRotation = false;
        $cleanedByStripping = false;

        if (FileHelper::isSvg($filePath)) {
            if (! Cms::config()->sanitizeSvgUploads) {
                return;
            }

            $sanitizer = new Sanitizer;
            $sanitizer->setAllowedAttrs(new SvgAllowedAttributes);
            $svgContents = file_get_contents($filePath);
            $svgContents = $sanitizer->sanitize($svgContents);

            if (! $svgContents) {
                throw new Exception('There was a problem sanitizing the SVG file contents, likely due to malformed XML.');
            }

            file_put_contents($filePath, $svgContents);

            return;
        }

        if (FileHelper::isGif($filePath) && ! Cms::config()->transformGifs) {
            return;
        }

        try {
            if (Cms::config()->rotateImagesOnUploadByExifData) {
                $cleanedByRotation = $this->rotateImageByExifData($filePath);
            }

            $cleanedByStripping = $this->stripOrientationFromExifData($filePath);
        } catch (Throwable $e) {
            Log::error('Tried to rotate or strip EXIF data from image and failed: '.$e->getMessage(), [__METHOD__]);
        }

        if ($cleanedByRotation || $cleanedByStripping) {
            return;
        }

        $this->loadImage($filePath)->saveAs($filePath, true);
    }

    public function rotateImageByExifData(string $filePath): bool
    {
        if (! ImageHelper::canHaveExifData($filePath)) {
            return false;
        }

        if (! $this->getIsImagick()) {
            return false;
        }

        $image = new Imagick($filePath);
        $orientation = ExifOrientation::tryFrom($image->getImageOrientation());
        if ($orientation === null) {
            return false;
        }

        $degrees = match ($orientation) {
            ExifOrientation::Rotate180, ExifOrientation::Rotate180Mirrored => 180,
            ExifOrientation::Rotate90, ExifOrientation::Rotate90Mirrored => 90,
            ExifOrientation::Rotate270, ExifOrientation::Rotate270Mirrored => 270,
            default => 0,
        };

        $mirrored = in_array($orientation, [
            ExifOrientation::Rotate0Mirrored,
            ExifOrientation::Rotate180Mirrored,
            ExifOrientation::Rotate90Mirrored,
            ExifOrientation::Rotate270Mirrored,
        ], true);

        if ($degrees === 0 && ! $mirrored) {
            return false;
        }

        $image = $this->loadImage($filePath);
        if (! $image instanceof Raster) {
            return false;
        }

        if ($degrees !== 0) {
            $image->rotate($degrees);
        }

        if ($mirrored) {
            $image->flipHorizontally();
        }

        return $image->saveAs($filePath, true);
    }

    public function getExifData(string $filePath): ?array
    {
        if (! ImageHelper::canHaveExifData($filePath)) {
            return null;
        }

        $image = new Raster;

        return $image->getExifMetadata($filePath);
    }

    public function stripOrientationFromExifData(string $filePath): bool
    {
        if (! ImageHelper::canHaveExifData($filePath)) {
            return false;
        }

        if (! $this->getIsImagick()) {
            return false;
        }

        $image = new Imagick($filePath);
        $image->setImageOrientation(Imagick::ORIENTATION_UNDEFINED);
        ImageHelper::cleanExifDataFromImagickImage($image);
        $image->writeImages($filePath, true);

        return true;
    }
}
