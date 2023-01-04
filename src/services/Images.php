<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\services;

use Craft;
use craft\base\Image;
use craft\helpers\App;
use craft\helpers\ConfigHelper;
use craft\helpers\FileHelper;
use craft\helpers\Image as ImageHelper;
use craft\image\Raster;
use craft\image\Svg;
use craft\image\SvgAllowedAttributes;
use enshrined\svgSanitize\Sanitizer;
use Imagine\Gd\Imagine as GdImagine;
use Imagine\Image\Format;
use Imagine\Imagick\Imagick;
use Imagine\Imagick\Imagine as ImagickImagine;
use Throwable;
use yii\base\Component;
use yii\base\Exception;

/**
 * Images service.
 *
 * An instance of the service is available via [[\craft\base\ApplicationTrait::getImages()|`Craft::$app->images`]].
 *
 * @property bool $isGd Whether image manipulations will be performed using GD or not
 * @property bool $isImagick Whether image manipulations will be performed using Imagick or not
 * @property array $supportedImageFormats A list of all supported image formats
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class Images extends Component
{
    public const DRIVER_GD = 'gd';
    public const DRIVER_IMAGICK = 'imagick';
    public const MINIMUM_IMAGICK_VERSION = '6.2.9';

    /**
     * @var array Image formats that can be manipulated.
     */
    public array $supportedImageFormats = ['jpg', 'jpeg', 'gif', 'png'];

    /**
     * @var string Image driver.
     */
    private string $_driver = '';

    /**
     * @var string|null Imagick version being used, if any.
     */
    private ?string $_imagickVersion = null;

    /**
     * Decide on the image driver being used.
     */
    public function init(): void
    {
        if (strtolower(Craft::$app->getConfig()->getGeneral()->imageDriver) === 'gd') {
            $this->_driver = self::DRIVER_GD;
        } elseif ($this->getCanUseImagick()) {
            $this->_driver = self::DRIVER_IMAGICK;
        } else {
            $this->_driver = self::DRIVER_GD;
        }

        parent::init();
    }

    /**
     * Returns whether image manipulations will be performed using GD or not.
     *
     * @return bool
     */
    public function getIsGd(): bool
    {
        return $this->_driver === self::DRIVER_GD;
    }

    /**
     * Returns whether image manipulations will be performed using Imagick or not.
     *
     * @return bool
     */
    public function getIsImagick(): bool
    {
        return $this->_driver === self::DRIVER_IMAGICK;
    }

    /**
     * Returns the version of the image driver.
     *
     * @return string
     */
    public function getVersion(): string
    {
        if ($this->getIsGd()) {
            return App::extensionVersion('gd');
        }

        $version = App::extensionVersion('imagick');
        try {
            $version .= ' (ImageMagick ' . $this->getImageMagickApiVersion() . ')';
        } catch (Throwable) {
        }
        return $version;
    }

    /**
     * Returns a list of all supported image formats.
     *
     * @return array
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
     * Returns the installed ImageMagick API version.
     *
     * @return string
     * @throws Exception if the Imagick extension isn’t installed
     */
    public function getImageMagickApiVersion(): string
    {
        if (isset($this->_imagickVersion)) {
            return $this->_imagickVersion;
        }

        if (!extension_loaded('imagick')) {
            throw new Exception('The Imagick extension isn’t loaded.');
        }

        // Taken from Imagick\Imagine() constructor.
        // Imagick::getVersion() is static only since Imagick PECL extension 3.2.0b1, so instantiate it.
        $versionString = \Imagick::getVersion()['versionString'];
        [$this->_imagickVersion] = sscanf($versionString, 'ImageMagick %s %04d-%02d-%02d %s %s');

        return $this->_imagickVersion;
    }

    /**
     * Returns whether Imagick is installed and meets version requirements
     *
     * @return bool
     */
    public function getCanUseImagick(): bool
    {
        if (!extension_loaded('imagick')) {
            return false;
        }

        // https://github.com/craftcms/cms/issues/5435
        if (empty(\Imagick::queryFormats())) {
            return false;
        }

        // Make sure it meets the minimum API version requirement
        if (version_compare($this->getImageMagickApiVersion(), self::MINIMUM_IMAGICK_VERSION) === -1) {
            return false;
        }

        return true;
    }

    /**
     * Returns whether the WebP image format is supported.
     *
     * @return bool
     */
    public function getSupportsWebP(): bool
    {
        $info = $this->getIsImagick() ? ImagickImagine::getDriverInfo() : GdImagine::getDriverInfo();
        return $info->isFormatSupported(Format::ID_WEBP);
    }

    /**
     * Returns whether the AVIF image format is supported.
     *
     * @return bool
     */
    public function getSupportsAvif(): bool
    {
        $info = $this->getIsImagick() ? ImagickImagine::getDriverInfo() : GdImagine::getDriverInfo();
        return $info->isFormatSupported(Format::ID_AVIF);
    }

    /**
     * Returns whether the HEIC/HEIF image format is supported.
     *
     * @return bool
     * @since 4.3.6
     */
    public function getSupportsHeic(): bool
    {
        $info = $this->getIsImagick() ? ImagickImagine::getDriverInfo() : GdImagine::getDriverInfo();
        return $info->isFormatSupported(Format::ID_HEIC);
    }

    /**
     * Loads an image from a file system path.
     *
     * @param string $path
     * @param bool $rasterize Whether the image should be rasterized if it's an SVG
     * @param int $svgSize The size SVG should be scaled up to, if rasterized
     * @return Image
     */
    public function loadImage(string $path, bool $rasterize = false, int $svgSize = 1000): Image
    {
        if (FileHelper::isSvg($path)) {
            $image = new Svg();
            $image->loadImage($path);

            if ($rasterize) {
                $image->scaleToFit($svgSize, $svgSize);
                $svgString = $image->getSvgString();
                $image = new Raster();
                $image->loadFromSVG($svgString);
            }
        } else {
            $image = new Raster();
            $image->loadImage($path);
        }

        return $image;
    }

    /**
     * Determines if there is enough memory to process this image.
     *
     * The code was adapted from http://www.php.net/manual/en/function.imagecreatefromjpeg.php#64155.
     * It will first attempt to do it with available memory. If that fails,
     * Craft will bump the memory to amount defined by the
     * <config4:phpMaxMemoryLimit> config setting, then try again.
     *
     * @param string $filePath The path to the image file.
     * @param bool $toTheMax If set to true, will set the PHP memory to the config setting phpMaxMemoryLimit.
     * @return bool
     */
    public function checkMemoryForImage(string $filePath, bool $toTheMax = false): bool
    {
        if (FileHelper::isSvg($filePath)) {
            return true;
        }

        if (!function_exists('memory_get_usage')) {
            return false;
        }

        if ($toTheMax) {
            // Turn it up to 11.
            App::maxPowerCaptain();
        }

        // If the file is 0bytes, we probably have enough memory
        if (!filesize($filePath)) {
            return true;
        }

        // Find out how much memory this image is going to need.
        $imageInfo = getimagesize($filePath);

        // If we can't find out the imagesize, chances are, we won't be able to anything about it.
        if (!is_array($imageInfo)) {
            Craft::warning('Could not determine image information for ' . $filePath);
            return true;
        }

        $K64 = 65536;
        $tweakFactor = 1.7;
        $bits = $imageInfo['bits'] ?? 8;
        $channels = $imageInfo['channels'] ?? 4;
        $memoryNeeded = round(($imageInfo[0] * $imageInfo[1] * $bits * $channels / 8 + $K64) * $tweakFactor);

        $memoryLimit = ConfigHelper::sizeInBytes(ini_get('memory_limit'));

        if ($memoryLimit == -1 || memory_get_usage() + $memoryNeeded < $memoryLimit) {
            return true;
        }

        if (!$toTheMax) {
            return $this->checkMemoryForImage($filePath, true);
        }

        // Oh well, we tried.
        return false;
    }

    /**
     * Cleans an image by its path, clearing embedded potentially malicious embedded code.
     *
     * @param string $filePath
     * @throws Exception if $filePath is a malformed SVG image
     */
    public function cleanImage(string $filePath): void
    {
        $cleanedByRotation = false;
        $cleanedByStripping = false;

        // Special case for SVG files.
        if (FileHelper::isSvg($filePath)) {
            if (!Craft::$app->getConfig()->getGeneral()->sanitizeSvgUploads) {
                return;
            }

            $sanitizer = new Sanitizer();
            $sanitizer->setAllowedAttrs(new SvgAllowedAttributes());
            $svgContents = file_get_contents($filePath);
            $svgContents = $sanitizer->sanitize($svgContents);

            if (!$svgContents) {
                throw new Exception('There was a problem sanitizing the SVG file contents, likely due to malformed XML.');
            }

            file_put_contents($filePath, $svgContents);
            return;
        }

        if (FileHelper::isGif($filePath) && !Craft::$app->getConfig()->getGeneral()->transformGifs) {
            return;
        }

        try {
            if (Craft::$app->getConfig()->getGeneral()->rotateImagesOnUploadByExifData) {
                $cleanedByRotation = $this->rotateImageByExifData($filePath);
            }

            $cleanedByStripping = $this->stripOrientationFromExifData($filePath);
        } catch (Throwable $e) {
            Craft::error('Tried to rotate or strip EXIF data from image and failed: ' . $e->getMessage(), __METHOD__);
        }

        // Image has already been cleaned if it had exif/orientation data
        if ($cleanedByRotation || $cleanedByStripping) {
            return;
        }

        $this->loadImage($filePath)->saveAs($filePath, true);
    }

    /**
     * Rotate image according to it's EXIF data.
     *
     * @param string $filePath
     * @return bool
     */
    public function rotateImageByExifData(string $filePath): bool
    {
        if (!ImageHelper::canHaveExifData($filePath)) {
            return false;
        }

        // Quick and dirty, if possible
        if (!($this->getIsImagick() && method_exists(\Imagick::class, 'getImageOrientation'))) {
            return false;
        }

        $image = new \Imagick($filePath);
        $orientation = $image->getImageOrientation();

        $degrees = false;

        switch ($orientation) {
            case ImageHelper::EXIF_IFD0_ROTATE_180:
                $degrees = 180;
                break;
            case ImageHelper::EXIF_IFD0_ROTATE_90:
                $degrees = 90;
                break;
            case ImageHelper::EXIF_IFD0_ROTATE_270:
                $degrees = 270;
                break;
        }

        if ($degrees === false) {
            return false;
        }

        /** @var Raster $image */
        $image = $this->loadImage($filePath);
        $image->rotate($degrees);

        return $image->saveAs($filePath, true);
    }

    /**
     * Get EXIF metadata for a file by it's path.
     *
     * @param string $filePath
     * @return array|null
     */
    public function getExifData(string $filePath): ?array
    {
        if (!ImageHelper::canHaveExifData($filePath)) {
            return null;
        }

        $image = new Raster();

        return $image->getExifMetadata($filePath);
    }

    /**
     * Strip orientation from EXIF data for an image at a path.
     *
     * @param string $filePath
     * @return bool
     */
    public function stripOrientationFromExifData(string $filePath): bool
    {
        if (!ImageHelper::canHaveExifData($filePath)) {
            return false;
        }

        // Quick and dirty, if possible
        if (!($this->getIsImagick() && method_exists(\Imagick::class, 'setImageOrientation'))) {
            return false;
        }

        $image = new \Imagick($filePath);
        $image->setImageOrientation(\Imagick::ORIENTATION_UNDEFINED);
        ImageHelper::cleanExifDataFromImagickImage($image);
        $image->writeImages($filePath, true);

        return true;
    }
}
