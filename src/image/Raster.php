<?php
namespace craft\app\image;

use Craft;
use craft\app\base\Image;
use craft\app\errors\ImageException;
use craft\app\helpers\Image as ImageHelper;
use craft\app\helpers\Io;
use craft\app\helpers\StringHelper;

/**
 * Raster class is used for raster image manipulations.
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   http://craftcms.com/license Craft License Agreement
 * @see       http://craftcms.com
 * @package   craft.app.etc.io
 * @since     3.0
 */
class Raster extends Image
{
    // Properties
    // =========================================================================

    /**
     * @var string
     */
    private $_imageSourcePath;

    /**
     * @var string
     */
    private $_extension;

    /**
     * @var bool
     */
    private $_isAnimatedGif = false;

    /**
     * @var int
     */
    private $_quality = 0;

    /**
     * @var \Imagine\Image\ImageInterface
     */
    private $_image;

    /**
     * @var \Imagine\Image\ImagineInterface
     */
    private $_instance;

    /**
     * @var \Imagine\Image\Palette\RGB
     */
    private $_palette;

    /**
     * @var \Imagine\Image\AbstractFont
     */
    private $_font;

    // Public Methods
    // =========================================================================

    /**
     * @return Image
     */
    public function __construct()
    {
        $config = Craft::$app->getConfig();

        $extension = StringHelper::toLowerCase($config->get('imageDriver'));

        // If it's explicitly set, take their word for it.
        if ($extension === 'gd') {
            $this->_instance = new \Imagine\Gd\Imagine();
        } else {
            if ($extension === 'imagick') {
                $this->_instance = new \Imagine\Imagick\Imagine();
            } else {
                // Let's try to auto-detect.
                if (Craft::$app->getImages()->isGd()) {
                    $this->_instance = new \Imagine\Gd\Imagine();
                } else {
                    $this->_instance = new \Imagine\Imagick\Imagine();
                }
            }
        }

        $this->_quality = $config->get('defaultImageQuality');
    }

    /**
     * @return int
     */
    public function getWidth()
    {
        return $this->_image->getSize()->getWidth();

    }

    /**
     * @return int
     */
    public function getHeight()
    {
        return $this->_image->getSize()->getHeight();
    }

    /**
     * @return string
     */
    public function getExtension()
    {
        return $this->_extension;
    }

    /**
     * Loads an image from a file system path.
     *
     * @param string $path
     *
     * @throws ImageException
     * @return Image
     */
    public function loadImage($path)
    {
        $imageService = Craft::$app->getImages();

        if (!Io::fileExists($path)) {
            throw new ImageException(Craft::t('app',
                'No file exists at the path “{path}”', array('path' => $path)));
        }

        if (!$imageService->checkMemoryForImage($path)) {
            throw new ImageException(Craft::t('app',
                'Not enough memory available to perform this image operation.'));
        }

        $extension = Io::getExtension($path);

        try {
            $this->_image = $this->_instance->open($path);
        } catch (\Exception $exception) {
            throw new ImageException(Craft::t('app',
                'The file “{path}” does not appear to be an image.',
                array('path' => $path)));
        }

        $this->_extension = $extension;
        $this->_imageSourcePath = $path;

        if ($this->_extension == 'gif') {
            if (!$imageService->isGd() && $this->_image->layers()) {
                $this->_isAnimatedGif = true;
            }
        }

        $this->_resizeHeight = $this->getHeight();
        $this->_resizeWidth = $this->getWidth();

        return $this;
    }

    /**
     * Crops the image to the specified coordinates.
     *
     * @param int $x1
     * @param int $x2
     * @param int $y1
     * @param int $y2
     *
     * @return Image
     */
    public function crop($x1, $x2, $y1, $y2)
    {
        $width = $x2 - $x1;
        $height = $y2 - $y1;

        if ($this->_isAnimatedGif) {

            // Create a new image instance to avoid object references messing up our dimensions.
            $newSize = new \Imagine\Image\Box($width, $height);
            $startingPoint = new \Imagine\Image\Point($x1, $y1);
            $gif = $this->_instance->create($newSize);
            $gif->layers()->remove(0);

            foreach ($this->_image->layers() as $layer) {
                $croppedLayer = $layer->crop($startingPoint, $newSize);
                $gif->layers()->add($croppedLayer);
            }

            $this->_image = $gif;
        } else {
            $this->_image->crop(new \Imagine\Image\Point($x1, $y1),
                new \Imagine\Image\Box($width, $height));
        }

        return $this;
    }

    /**
     * Scale the image to fit within the specified size.
     *
     * @param int $targetWidth
     * @param int|null $targetHeight
     * @param bool $scaleIfSmaller
     *
     * @return Image
     */
    public function scaleToFit(
        $targetWidth,
        $targetHeight = null,
        $scaleIfSmaller = true
    ) {
        $this->normalizeDimensions($targetWidth, $targetHeight);

        if ($scaleIfSmaller || $this->getWidth() > $targetWidth || $this->getHeight() > $targetHeight) {
            $factor = max($this->getWidth() / $targetWidth,
                $this->getHeight() / $targetHeight);
            $this->resize(round($this->getWidth() / $factor),
                round($this->getHeight() / $factor));
        }

        return $this;
    }

    /**
     * Scale and crop image to exactly fit the specified size.
     *
     * @param int $targetWidth
     * @param int|null $targetHeight
     * @param bool $scaleIfSmaller
     * @param string $cropPositions
     *
     * @return Image
     */
    public function scaleAndCrop(
        $targetWidth,
        $targetHeight = null,
        $scaleIfSmaller = true,
        $cropPositions = 'center-center'
    ) {
        $this->normalizeDimensions($targetWidth, $targetHeight);

        list($verticalPosition, $horizontalPosition) = explode("-",
            $cropPositions);

        if ($scaleIfSmaller || $this->getWidth() > $targetWidth || $this->getHeight() > $targetHeight) {
            // Scale first.
            $factor = min($this->getWidth() / $targetWidth,
                $this->getHeight() / $targetHeight);
            $newHeight = round($this->getHeight() / $factor);
            $newWidth = round($this->getWidth() / $factor);


            $this->resize($newWidth, $newHeight);

            // Now crop.
            if ($newWidth - $targetWidth > 0) {
                switch ($horizontalPosition) {
                    case 'left': {
                        $x1 = 0;
                        $x2 = $x1 + $targetWidth;
                        break;
                    }
                    case 'right': {
                        $x2 = $newWidth;
                        $x1 = $newWidth - $targetWidth;
                        break;
                    }
                    default: {
                        $x1 = round(($newWidth - $targetWidth) / 2);
                        $x2 = $x1 + $targetWidth;
                        break;
                    }
                }

                $y1 = 0;
                $y2 = $y1 + $targetHeight;
            } elseif ($newHeight - $targetHeight > 0) {
                switch ($verticalPosition) {
                    case 'top': {
                        $y1 = 0;
                        $y2 = $y1 + $targetHeight;
                        break;
                    }
                    case 'bottom': {
                        $y2 = $newHeight;
                        $y1 = $newHeight - $targetHeight;
                        break;
                    }
                    default: {
                        $y1 = round(($newHeight - $targetHeight) / 2);
                        $y2 = $y1 + $targetHeight;
                        break;
                    }
                }

                $x1 = 0;
                $x2 = $x1 + $targetWidth;
            } else {
                $x1 = round(($newWidth - $targetWidth) / 2);
                $x2 = $x1 + $targetWidth;
                $y1 = round(($newHeight - $targetHeight) / 2);
                $y2 = $y1 + $targetHeight;
            }

            $this->crop($x1, $x2, $y1, $y2);
        }

        return $this;
    }

    /**
     * Re-sizes the image. If $height is not specified, it will default to $width, creating a square.
     *
     * @param int $targetWidth
     * @param int|null $targetHeight
     *
     * @return Image
     */
    public function resize($targetWidth, $targetHeight = null)
    {
        $this->normalizeDimensions($targetWidth, $targetHeight);

        if ($this->_isAnimatedGif) {

            // Create a new image instance to avoid object references messing up our dimensions.
            $newSize = new \Imagine\Image\Box($targetWidth, $targetHeight);
            $gif = $this->_instance->create($newSize);
            $gif->layers()->remove(0);

            foreach ($this->_image->layers() as $layer) {
                $resizedLayer = $layer->resize($newSize,
                    $this->_getResizeFilter());
                $gif->layers()->add($resizedLayer);
            }

            $this->_image = $gif;
        } else {
            if (Craft::$app->getImages()->isImagick() && Craft::$app->getConfig()->get('optimizeImageFilesize')) {
                $this->_image->smartResize(new \Imagine\Image\Box($targetWidth,
                    $targetHeight), false, $this->_quality);
            } else {
                $this->_image->resize(new \Imagine\Image\Box($targetWidth,
                    $targetHeight), $this->_getResizeFilter());
            }
        }

        return $this;
    }

    /**
     * Rotate an image by degrees.
     *
     * @param int $degrees
     *
     * @return Image
     */
    public function rotate($degrees)
    {
        $this->_image->rotate($degrees);

        return $this;
    }

    /**
     * Set image quality.
     *
     * @param int $quality
     *
     * @return Image
     */
    public function setQuality($quality)
    {
        $this->_quality = $quality;
        return $this;
    }

    /**
     * Saves the image to the target path.
     *
     * @param string $targetPath
     *
     * @throws ImageException If Imagine threw an exception.
     * @return null
     */
    public function saveAs($targetPath, $autoQuality = false)
    {
        $extension = StringHelper::toLowerCase(Io::getExtension($targetPath));

        $options = $this->_getSaveOptions(false, $extension);
        $targetPath = Io::getFolderName($targetPath).Io::getFileName($targetPath,
                false).'.'.Io::getExtension($targetPath);

        try {
            if ($autoQuality && in_array($extension,
                    array('jpeg', 'jpg', 'png'))
            ) {
                clearstatcache();
                $originalSize = Io::getFileSize($this->_imageSourcePath);
                $tempFile = $this->_autoGuessImageQuality($targetPath,
                    $originalSize, $extension, 0, 200);
                Io::move($tempFile, $targetPath, true);
            } else {
                $this->_image->save($targetPath, $options);
            }
        } catch (\Imagine\Exception\RuntimeException $e) {
            throw new ImageException(Craft::t('app', 'Failed to save the image.'), $e->getCode(), $e);
        }
        return true;
    }

    /**
     * Load an image from an SVG string.
     *
     * @param $svgContent
     *
     * @throws ImageException If the SVG string cannot be loaded.
     * @return Image
     */
    public function loadFromSVG($svgContent)
    {
        try {
            $this->_image = $this->_instance->load($svgContent);
        } catch (\Imagine\Exception\RuntimeException $e) {
            try
            {
                // Invalid SVG. Maybe it's missing its DTD?
                $svgContent = '<?xml version="1.0" encoding="UTF-8" standalone="no"?>'.$svgContent;
                $this->_image = $this->_instance->load($svgContent);
            } catch (\Imagine\Exception\RuntimeException $e) {
                throw new ImageException(Craft::t('app', 'Failed to load the SVG string.'), $e->getCode(), $e);
            }
        }

        return $this;
    }

    /**
     * Returns true if Imagick is installed and says that the image is transparent.
     *
     * @return bool
     */
    public function isTransparent()
    {
        if (Craft::$app->getImages()->isImagick() && method_exists("Imagick",
                "getImageAlphaChannel")
        ) {
            return $this->_image->getImagick()->getImageAlphaChannel();
        }

        return false;
    }

    /**
     * Return EXIF metadata for a file by it's path
     *
     * @param $filePath
     *
     * @return array
     */
    public function getExifMetadata($filePath)
    {
        try {
            $exifReader = new \Imagine\Image\Metadata\ExifMetadataReader();
            $this->_instance->setMetadataReader($exifReader);
            $exif = $this->_instance->open($filePath)->metadata();

            return $exif->toArray();
        } catch (\Imagine\Exception\NotSupportedException $exception) {
            Craft::error($exception->getMessage());

            return array();
        }
    }

    /**
     * Set properties for text drawing on the image.
     *
     * @param $fontFile string path to the font file on server
     * @param $size     int    font size to use
     * @param $color    string font color to use in hex format
     *
     * @return null
     */
    public function setFontProperties($fontFile, $size, $color)
    {
        if (empty($this->_palette)) {
            $this->_palette = new \Imagine\Image\Palette\RGB();
        }

        $this->_font = $this->_instance->font($fontFile, $size,
            $this->_palette->color($color));
    }

    /**
     * Get the bounding text box for a text string and an angle
     *
     * @param $text
     * @param int $angle
     *
     * @throws ImageException If attempting to create text box with no font properties et.
     * @return \Imagine\Image\BoxInterface
     */
    public function getTextBox($text, $angle = 0)
    {
        if (empty($this->_font)) {
            throw new ImageException(Craft::t('app',
                'No font properties have been set. Call Raster::setFontProperties() first.'));
        }

        return $this->_font->box($text, $angle);
    }

    /**
     * Write text on an image
     *
     * @param     $text
     * @param     $x
     * @param     $y
     * @param int $angle
     *
     * @throws ImageException If attempting to create text box with no font properties et.
     * @return null
     */
    public function writeText($text, $x, $y, $angle = 0)
    {

        if (empty($this->_font)) {
            throw new ImageException(Craft::t('app',
                'No font properties have been set. Call ImageHelper::setFontProperties() first.'));
        }

        $point = new \Imagine\Image\Point($x, $y);

        $this->_image->draw()->text($text, $this->_font, $point, $angle);
    }

    // Private Methods
    // =========================================================================

    /**
     * @param     $tempFileName
     * @param     $originalSize
     * @param     $extension
     * @param     $minQuality
     * @param     $maxQuality
     * @param int $step
     *
     * @return string $path the resulting file path
     */
    private function _autoGuessImageQuality(
        $tempFileName,
        $originalSize,
        $extension,
        $minQuality,
        $maxQuality,
        $step = 0
    ) {
        // Give ourselves some extra time.
        @set_time_limit(30);

        if ($step == 0) {
            $tempFileName = Io::getFolderName($tempFileName).Io::getFileName($tempFileName,
                    false).'-temp.'.$extension;
        }

        // Find our target quality by splitting the min and max qualities
        $midQuality = (int)ceil($minQuality + (($maxQuality - $minQuality) / 2));

        // Set the min and max acceptable ranges. .10 means anything between 90% and 110% of the original file size is acceptable.
        $acceptableRange = .10;

        clearstatcache();

        // Generate a new temp image and get it's file size.
        $this->_image->save($tempFileName,
            $this->_getSaveOptions($midQuality, $extension));
        $newFileSize = Io::getFileSize($tempFileName);

        // If we're on step 10 OR we're within our acceptable range threshold OR midQuality = maxQuality (1 == 1),
        // let's use the current image.
        if ($step == 10 || abs(1 - $originalSize / $newFileSize) < $acceptableRange || $midQuality == $maxQuality) {
            clearstatcache();

            // Generate one last time.
            $this->_image->save($tempFileName,
                $this->_getSaveOptions($midQuality));
            return $tempFileName;
        }

        $step++;

        if ($newFileSize > $originalSize) {
            return $this->_autoGuessImageQuality($tempFileName, $originalSize,
                $extension, $minQuality, $midQuality, $step);
        } // Too much.
        else {
            return $this->_autoGuessImageQuality($tempFileName, $originalSize,
                $extension, $midQuality, $maxQuality, $step);
        }
    }

    /**
     * @return mixed
     */
    private function _getResizeFilter()
    {
        return (Craft::$app->getImages()->isGd() ? \Imagine\Image\ImageInterface::FILTER_UNDEFINED : \Imagine\Image\ImageInterface::FILTER_LANCZOS);
    }

    /**
     * Get save options.
     *
     * @param int|null $quality
     * @param string $extension
     * @return array
     */
    private function _getSaveOptions($quality = null, $extension = null)
    {
        // Because it's possible for someone to set the quality to 0.
        $quality = ($quality === null || $quality === false ? $this->_quality : $quality);
        $extension = (!$extension ? $this->getExtension() : $extension);

        switch ($extension) {
            case 'jpeg':
            case 'jpg': {
                return array('jpeg_quality' => $quality, 'flatten' => true);
            }

            case 'gif': {
                $options = array('animated' => $this->_isAnimatedGif);

                if ($this->_isAnimatedGif) {
                    // Imagine library does not provide this value and arbitrarily divides it by 10, when assigning,
                    // so we have to improvise a little
                    $options['animated.delay'] = $this->_image->getImagick()->getImageDelay() * 10;
                }

                return $options;
            }

            case 'png': {
                // Valid PNG quality settings are 0-9, so normalize and flip, because we're talking about compression
                // levels, not quality, like jpg and gif.
                $normalizedQuality = round(($quality * 9) / 100);
                $normalizedQuality = 9 - $normalizedQuality;

                if ($normalizedQuality < 0) {
                    $normalizedQuality = 0;
                }

                if ($normalizedQuality > 9) {
                    $normalizedQuality = 9;
                }

                $options = array(
                    'png_compression_level' => $normalizedQuality,
                    'flatten' => false
                );
                $pngInfo = ImageHelper::getPngImageInfo($this->_imageSourcePath);

                // Even though a 2 channel PNG is valid (Grayscale with alpha channel), Imagick doesn't recognize it as
                // a valid format: http://www.imagemagick.org/script/formats.php
                // So 2 channel PNGs get converted to 4 channel.

                if (is_array($pngInfo) && isset($pngInfo['channels']) && $pngInfo['channels'] !== 2) {
                    $format = 'png'.(8 * $pngInfo['channels']);
                } else {
                    $format = 'png32';
                }

                $options['png_format'] = $format;

                return $options;
            }

            default: {
                return array();
            }
        }
    }
}
