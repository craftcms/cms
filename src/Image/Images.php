<?php

declare(strict_types=1);

namespace CraftCms\Cms\Image;

use CraftCms\Cms\Cms;
use CraftCms\Cms\Config\GeneralConfig;
use CraftCms\Cms\Image\Enums\ExifOrientation;
use CraftCms\Cms\Image\Enums\ImageDriver;
use CraftCms\Cms\Support\File;
use CraftCms\Cms\Support\PHP;
use enshrined\svgSanitize\Sanitizer;
use Exception;
use Illuminate\Container\Attributes\Singleton;
use Illuminate\Support\Facades\Log;
use Imagick;
use Intervention\Image\Drivers\Gd\Driver as GdDriver;
use Intervention\Image\Drivers\Imagick\Driver as ImagickDriver;
use Intervention\Image\Drivers\Vips\Driver as VipsDriver;
use Intervention\Image\Exceptions\MissingDependencyException;
use Intervention\Image\FileExtension;
use Intervention\Image\Format;
use Intervention\Image\ImageManager;
use Jcupitt\Vips\Image as VipsImage;
use Throwable;

use function CraftCms\Cms\maxPowerCaptain;

#[Singleton]
class Images
{
    /** @var string[] */
    private array $supportedImageFormats = ['jpg', 'jpeg', 'gif', 'png'];

    private ImageDriver $driver;

    private ImageManager $manager;

    private ?string $imagickVersion = null;

    private ?bool $canRasterizeSvg = null;

    /** @var array<string, bool> */
    private array $encodingSupport = [];

    public function __construct()
    {
        $configuredDriver = strtolower((string) Cms::config()->imageDriver);

        if ($configuredDriver !== GeneralConfig::IMAGE_DRIVER_AUTO) {
            $this->driver = ImageDriver::from($configuredDriver);
            $this->manager = $this->createManager($this->driver);
            $this->manager->driver->checkHealth();

            return;
        }

        foreach ([ImageDriver::Imagick, ImageDriver::Vips, ImageDriver::Gd] as $driver) {
            try {
                $manager = $this->createManager($driver);
                $manager->driver->checkHealth();
            } catch (Throwable) {
                continue;
            }

            $this->driver = $driver;
            $this->manager = $manager;

            return;
        }

        throw new MissingDependencyException('No supported image driver is available.');
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

    public function getIsVips(): bool
    {
        return $this->driver === ImageDriver::Vips;
    }

    public function getDriver(): ImageDriver
    {
        return $this->driver;
    }

    public function getManager(): ImageManager
    {
        return $this->manager;
    }

    public function getVersion(): string
    {
        return $this->manager->driver->version();
    }

    /**
     * @return string[]
     */
    public function getSupportedImageFormats(): array
    {
        $additionalFormats = array_filter(
            FileExtension::cases(),
            fn (FileExtension $extension) => ! in_array($extension->format(), [Format::JPEG, Format::GIF, Format::PNG], true)
                && $this->canDecodeFormat($extension),
        );

        return array_values(array_unique([
            ...$this->supportedImageFormats,
            ...array_map(fn (FileExtension $extension) => $extension->value, $additionalFormats),
        ]));
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
        try {
            $manager = $this->createManager(ImageDriver::Imagick);
            $manager->driver->checkHealth();
        } catch (Throwable) {
            return false;
        }

        return $manager->driver->supports(Format::JPEG);
    }

    public function getSupportsWebP(): bool
    {
        return $this->supportsFormat(Format::WEBP);
    }

    public function getSupportsAvif(): bool
    {
        return $this->supportsFormat(Format::AVIF);
    }

    public function getSupportsHeic(): bool
    {
        return $this->supportsFormat(Format::HEIC);
    }

    public function getCanRasterizeSvg(): bool
    {
        if ($this->canRasterizeSvg !== null) {
            return $this->canRasterizeSvg;
        }

        try {
            $this->manager->decodeBinary('<svg xmlns="http://www.w3.org/2000/svg" width="1" height="1"><rect width="1" height="1"/></svg>');
        } catch (Throwable) {
            return $this->canRasterizeSvg = false;
        }

        return $this->canRasterizeSvg = true;
    }

    public function supportsFormat(string|Format|FileExtension $format): bool
    {
        try {
            $format = Format::create($format);
            if (isset($this->encodingSupport[$format->name])) {
                return $this->encodingSupport[$format->name];
            }

            $encoded = $this->manager->createImage(1, 1)->encode($format->encoder());

            return $this->encodingSupport[$format->name] = $encoded->size() > 0;
        } catch (Throwable) {
            return false;
        }
    }

    public function canDecodeFormat(string|Format|FileExtension $format): bool
    {
        try {
            return $this->manager->driver->supports($format);
        } catch (Throwable) {
            return false;
        }
    }

    private function createManager(ImageDriver $driver): ImageManager
    {
        if ($driver === ImageDriver::Vips && ! class_exists(VipsDriver::class)) {
            throw new MissingDependencyException('The intervention/image-driver-vips package must be installed to use the Vips image driver.');
        }

        $driverClass = match ($driver) {
            ImageDriver::Gd => GdDriver::class,
            ImageDriver::Imagick => ImagickDriver::class,
            ImageDriver::Vips => VipsDriver::class,
        };

        return new ImageManager(
            $driverClass,
            autoOrientation: false,
            decodeAnimation: true,
            backgroundColor: 'ffffff',
            strip: false,
        );
    }

    public function loadImage(string $path, bool $rasterize = false, int $svgSize = 1000): Image
    {
        if (File::isSvg($path)) {
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
        if (File::isSvg($filePath)) {
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

        if (File::isSvg($filePath)) {
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

        if (File::isGif($filePath) && ! Cms::config()->transformGifs) {
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

        $orientation = ExifOrientation::tryFrom((int) ($this->getExifData($filePath)['ifd0.Orientation'] ?? 0));
        if ($orientation === null) {
            return false;
        }

        if ($orientation === ExifOrientation::Rotate0) {
            return false;
        }

        $image = $this->loadImage($filePath);
        if (! $image instanceof Raster) {
            return false;
        }

        return $image->orient()->saveAs($filePath, true);
    }

    /** @return array<string,mixed>|null */
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

        if ($this->getIsImagick()) {
            $image = new Imagick($filePath);
            $image->setImageOrientation(Imagick::ORIENTATION_UNDEFINED);
            ImageHelper::cleanExifDataFromImagickImage($image);
            $image->writeImages($filePath, true);

            return true;
        }

        $image = $this->loadImage($filePath);
        if (! $image instanceof Raster) {
            return false;
        }

        $native = $image->getInterventionImage()->core()->native();
        if ($native instanceof VipsImage && $native->getType('orientation') !== 0) {
            $native->remove('orientation');
        }

        $image->saveAs($filePath, true);

        return true;
    }
}
