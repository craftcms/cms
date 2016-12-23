<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\services;

use Craft;
use craft\base\Image;
use craft\helpers\App;
use craft\helpers\Image as ImageHelper;
use craft\helpers\StringHelper;
use craft\image\Raster;
use craft\image\Svg;
use yii\base\Component;

/**
 * Service for image operations.
 *
 * An instance of the Images service is globally accessible in Craft via [[Application::images `Craft::$app->getImages()`]].
 *
 * @property boolean $isGd      Whether image manipulations will be performed using GD or not
 * @property boolean $isImagick Whether image manipulations will be performed using Imagick or not
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class Images extends Component
{
    // Constants
    // =========================================================================

    const DRIVER_GD = 'gd';
    const DRIVER_IMAGICK = 'imagick';
    const MINIMUM_IMAGICK_VERSION = '6.2.9';

    // Properties
    // =========================================================================

    /**
     * Image driver.
     *
     * @var string
     */
    private $_driver = '';

    /**
     * Imagick version being used, if any.
     *
     * @var string
     */
    private $_imagickVersion;

    // Public Methods
    // =========================================================================

    /**
     * Decide on the image driver being used.
     *
     * @return void
     */
    public function init()
    {
        if (strtolower(Craft::$app->getConfig()->get('imageDriver')) == 'gd') {
            $this->_driver = self::DRIVER_GD;
        } else if ($this->getIsImagickAtLeast(self::MINIMUM_IMAGICK_VERSION)) {
            $this->_driver = self::DRIVER_IMAGICK;
        } else {
            $this->_driver = self::DRIVER_GD;
        }

        parent::init();
    }

    /**
     * Returns whether image manipulations will be performed using GD or not.
     *
     * @return boolean|null
     */
    public function getIsGd()
    {
        return $this->_driver == self::DRIVER_GD;
    }


    /**
     * Returns whether image manipulations will be performed using Imagick or not.
     *
     * @return boolean
     */
    public function getIsImagick()
    {
        return $this->_driver == self::DRIVER_IMAGICK;
    }

    /**
     * Returns whether Imagick is installed and meets version requirements
     *
     * @param string $requiredVersion version string
     *
     * @return boolean
     */
    public function getIsImagickAtLeast($requiredVersion)
    {
        if (!extension_loaded('imagick')) {
            return false;
        }

        if ($this->_imagickVersion === null) {
            // Taken from Imagick\Imagine() constructor.
            // Imagick::getVersion() is static only since Imagick PECL extension 3.2.0b1, so instantiate it.
            $imagick = new \Imagick();
            /** @noinspection PhpStaticAsDynamicMethodCallInspection */
            $v = $imagick::getVersion();
            list($version,,,,,) = sscanf($v['versionString'], 'ImageMagick %s %04d-%02d-%02d %s %s');

            $this->_imagickVersion = $version;
        }

        return version_compare($requiredVersion, $this->_imagickVersion) <= 0;
    }

    /**
     * Loads an image from a file system path.
     *
     * @param string  $path
     * @param boolean $rasterize Whether the image should be rasterized if it's an SVG
     * @param integer $svgSize   The size SVG should be scaled up to, if rasterized
     *
     * @return Image
     */
    public function loadImage($path, $rasterize = false, $svgSize = 1000)
    {
        if (StringHelper::toLowerCase(pathinfo($path, PATHINFO_EXTENSION)) == 'svg') {
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
     * The code was adapted from http://www.php.net/manual/en/function.imagecreatefromjpeg.php#64155. It will first
     * attempt to do it with available memory. If that fails, Craft will bump the memory to amount defined by the
     * [phpMaxMemoryLimit](http://craftcms.com/docs/config-settings#phpMaxMemoryLimit) config setting, then try again.
     *
     * @param string  $filePath The path to the image file.
     * @param boolean $toTheMax If set to true, will set the PHP memory to the config setting phpMaxMemoryLimit.
     *
     * @return boolean
     */
    public function checkMemoryForImage($filePath, $toTheMax = false)
    {
        if (StringHelper::toLowerCase(pathinfo($filePath, PATHINFO_EXTENSION)) == 'svg') {
            return true;
        }

        if (!function_exists('memory_get_usage')) {
            return false;
        }

        if ($toTheMax) {
            // Turn it up to 11.
            Craft::$app->getConfig()->maxPowerCaptain();
        }

        // Find out how much memory this image is going to need.
        $imageInfo = getimagesize($filePath);
        $K64 = 65536;
        $tweakFactor = 1.7;
        $bits = isset($imageInfo['bits']) ? $imageInfo['bits'] : 8;
        $channels = isset($imageInfo['channels']) ? $imageInfo['channels'] : 4;
        $memoryNeeded = round(($imageInfo[0] * $imageInfo[1] * $bits * $channels / 8 + $K64) * $tweakFactor);

        $memoryLimit = App::phpConfigValueInBytes('memory_limit');

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
     * Cleans an image by it's path, clearing embedded JS and PHP code.
     *
     * @param string $filePath
     *
     * @return boolean
     */
    public function cleanImage($filePath)
    {
        $cleanedByRotation = false;
        $cleanedByStripping = false;

        try {
            if (Craft::$app->getConfig()->get('rotateImagesOnUploadByExifData')) {
                $cleanedByRotation = $this->rotateImageByExifData($filePath);
            }

            $cleanedByStripping = $this->stripOrientationFromExifData($filePath);
        } catch (\Exception $e) {
            Craft::error('Tried to rotate or strip EXIF data from image and failed: '.$e->getMessage());
        }

        // Image has already been cleaned if it had exif/orientation data
        if ($cleanedByRotation || $cleanedByStripping) {
            return true;
        }

        return $this->loadImage($filePath)->saveAs($filePath, true);
    }

    /**
     * Rotate image according to it's EXIF data.
     *
     * @param string $filePath
     *
     * @return boolean
     */
    public function rotateImageByExifData($filePath)
    {
        if (!ImageHelper::canHaveExifData($filePath)) {
            return false;
        }

        // Quick and dirty, if possible
        if (!($this->getIsImagick() && method_exists('Imagick', 'getImageOrientation'))) {
            return false;
        }

        $image = new \Imagick($filePath);
        $orientation = $image->getImageOrientation();

        $degrees = false;

        switch ($orientation) {
            case ImageHelper::EXIF_IFD0_ROTATE_180: {
                $degrees = 180;
                break;
            }
            case ImageHelper::EXIF_IFD0_ROTATE_90: {
                $degrees = 90;
                break;
            }
            case ImageHelper::EXIF_IFD0_ROTATE_270: {
                $degrees = 270;
                break;
            }
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
     *
     * @return array
     */
    public function getExifData($filePath)
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
     *
     * @return boolean
     */
    public function stripOrientationFromExifData($filePath)
    {
        if (!ImageHelper::canHaveExifData($filePath)) {
            return false;
        }

        // Quick and dirty, if possible
        if (!($this->getIsImagick() && method_exists('Imagick', 'setImageOrientation'))) {
            return false;
        }

        $image = new \Imagick($filePath);
        $image->setImageOrientation(\Imagick::ORIENTATION_UNDEFINED);
        $image->writeImages($filePath, true);

        return true;
    }
}
