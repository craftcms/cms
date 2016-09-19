<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\app\image;

use Craft;
use craft\app\base\Image;
use craft\app\errors\ImageException;
use craft\app\helpers\Image as ImageHelper;
use craft\app\helpers\Io;
use craft\app\helpers\StringHelper;
use Imagine\Exception\NotSupportedException;
use Imagine\Exception\RuntimeException;
use Imagine\Gd\Imagine as GdImagine;
use Imagine\Gd\Image as GdImage;
use Imagine\Image\Box;
use Imagine\Image\AbstractFont as Font;
use Imagine\Image\ImageInterface as Imagine;
use Imagine\Image\Metadata\ExifMetadataReader;
use Imagine\Image\Palette\RGB;
use Imagine\Image\Point;
use Imagine\Imagick\Imagine as ImagickImagine;
use Imagine\Imagick\Image as ImagickImage;
use yii\helpers\FileHelper;

/**
 * Raster class is used for raster image manipulations.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
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
     * @var boolean
     */
    private $_isAnimatedGif = false;

    /**
     * @var integer
     */
    private $_quality = 0;

    /**
     * @var ImagickImage|GdImage
     */
    private $_image;

    /**
     * @var Imagine
     */
    private $_instance;

    /**
     * @var RGB
     */
    private $_palette;

    /**
     * @var Font
     */
    private $_font;

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function __construct($config = [])
    {
        $config = Craft::$app->getConfig();

        $extension = strtolower($config->get('imageDriver'));

        // If it's explicitly set, take their word for it.
        if ($extension === 'gd') {
            $this->_instance = new GdImagine();
        } else {
            if ($extension === 'imagick') {
                $this->_instance = new ImagickImagine();
            } else {
                // Let's try to auto-detect.
                if (Craft::$app->getImages()->getIsGd()) {
                    $this->_instance = new GdImagine();
                } else {
                    $this->_instance = new ImagickImagine();
                }
            }
        }

        $this->_quality = $config->get('defaultImageQuality');

        parent::__construct($config);
    }

    /**
     * @inheritdoc
     */
    public function getWidth()
    {
        return $this->_image->getSize()->getWidth();
    }

    /**
     * @inheritdoc
     */
    public function getHeight()
    {
        return $this->_image->getSize()->getHeight();
    }

    /**
     * @inheritdoc
     */
    public function getExtension()
    {
        return $this->_extension;
    }

    /**
     * @inheritdoc
     */
    public function loadImage($path)
    {
        $imageService = Craft::$app->getImages();

        if (!Io::fileExists($path)) {
            Craft::error('Tried to load an image at '.$path.', but the file does not exist.');
            throw new ImageException(Craft::t('app', 'No file exists at the given path.'));
        }

        if (!$imageService->checkMemoryForImage($path)) {
            throw new ImageException(Craft::t('app',
                'Not enough memory available to perform this image operation.'));
        }

        // Make sure the image says it's an image
        $mimeType = FileHelper::getMimeType($path, null, false);

        if ($mimeType !== null && strncmp($mimeType, 'image/', 6) !== 0) {
            throw new ImageException(Craft::t('app', 'The file “{name}” does not appear to be an image.', ['name' => Io::getFilename($path)]));
        }

        try {
            $this->_image = $this->_instance->open($path);
        } catch (\Exception $exception) {
            throw new ImageException(Craft::t('app', 'The file “{path}” does not appear to be an image.', ['path' => $path]));
        }

        // For Imagick, convert CMYK to RGB, save and re-open.
        if (!Craft::$app->getImages()->getIsGd()
            && method_exists($this->_image->getImagick(), 'getImageColorspace')
            && $this->_image->getImagick()->getImageColorspace() == \Imagick::COLORSPACE_CMYK
            && method_exists($this->_image->getImagick(), 'transformImageColorspace')
        ) {
            $this->_image->getImagick()->transformImageColorspace(\Imagick::COLORSPACE_SRGB);
            $this->_image->save();

            return Craft::$app->getImages()->loadImage($path);
        }

        $this->_imageSourcePath = $path;
        $this->_extension = Io::getExtension($path);

        if ($this->_extension == 'gif') {
            if (!$imageService->getIsGd() && $this->_image->layers()) {
                $this->_isAnimatedGif = true;
            }
        }

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function crop($x1, $x2, $y1, $y2)
    {
        $width = $x2 - $x1;
        $height = $y2 - $y1;

        if ($this->_isAnimatedGif) {

            // Create a new image instance to avoid object references messing up our dimensions.
            $newSize = new Box($width, $height);
            $startingPoint = new Point($x1, $y1);
            $gif = $this->_instance->create($newSize);
            $gif->layers()->remove(0);

            foreach ($this->_image->layers() as $layer) {
                $croppedLayer = $layer->crop($startingPoint, $newSize);
                $gif->layers()->add($croppedLayer);

                // Let's update dateUpdated in case this is going to take awhile.
                if ($index = Craft::$app->getAssetTransforms()->getActiveTransformIndex()) {
                    Craft::$app->getAssetTransforms()->storeTransformIndexData($index);
                }
            }

            $this->_image = $gif;
        } else {
            $this->_image->crop(new Point($x1, $y1), new Box($width, $height));
        }

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function scaleToFit($targetWidth, $targetHeight = null, $scaleIfSmaller = true)
    {
        $this->normalizeDimensions($targetWidth, $targetHeight);

        if ($scaleIfSmaller || $this->getWidth() > $targetWidth || $this->getHeight() > $targetHeight) {
            $factor = max($this->getWidth() / $targetWidth, $this->getHeight() / $targetHeight);
            $this->resize(round($this->getWidth() / $factor), round($this->getHeight() / $factor));
        }

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function scaleAndCrop($targetWidth, $targetHeight = null, $scaleIfSmaller = true, $cropPositions = 'center-center')
    {
        $this->normalizeDimensions($targetWidth, $targetHeight);

        list($verticalPosition, $horizontalPosition) = explode("-", $cropPositions);

        if ($scaleIfSmaller || $this->getWidth() > $targetWidth || $this->getHeight() > $targetHeight) {
            // Scale first.
            $factor = min($this->getWidth() / $targetWidth, $this->getHeight() / $targetHeight);
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
     * @inheritdoc
     */
    public function resize($targetWidth, $targetHeight = null)
    {
        $this->normalizeDimensions($targetWidth, $targetHeight);

        if ($this->_isAnimatedGif) {

            // Create a new image instance to avoid object references messing up our dimensions.
            $newSize = new Box($targetWidth, $targetHeight);
            $gif = $this->_instance->create($newSize);
            $gif->layers()->remove(0);

            foreach ($this->_image->layers() as $layer) {
                $resizedLayer = $layer->resize($newSize, $this->_getResizeFilter());
                $gif->layers()->add($resizedLayer);

                // Let's update dateUpdated in case this is going to take awhile.
                if ($index = Craft::$app->getAssetTransforms()->getActiveTransformIndex()) {
                    Craft::$app->getAssetTransforms()->storeTransformIndexData($index);
                }
            }

            $this->_image = $gif;
        } else {
            if (Craft::$app->getImages()->getIsImagick() && Craft::$app->getConfig()->get('optimizeImageFilesize')) {
                $this->_image->smartResize(new Box($targetWidth,
                    $targetHeight), false, $this->_quality);
            } else {
                $this->_image->resize(new Box($targetWidth,
                    $targetHeight), $this->_getResizeFilter());
            }
        }

        return $this;
    }

    /**
     * Rotates the image by the given degrees.
     *
     * @param integer $degrees
     *
     * @return $this Self reference
     */
    public function rotate($degrees)
    {
        $this->_image->rotate($degrees);

        return $this;
    }

    /**
     * Sets the image quality.
     *
     * @param integer $quality
     *
     * @return $this Self reference
     */
    public function setQuality($quality)
    {
        $this->_quality = $quality;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function saveAs($targetPath, $autoQuality = false)
    {
        $extension = StringHelper::toLowerCase(Io::getExtension($targetPath));

        $options = $this->_getSaveOptions(false, $extension);
        $targetPath = Io::getFolderName($targetPath).Io::getFilename($targetPath,
                false).'.'.Io::getExtension($targetPath);

        try {
            if ($autoQuality && in_array($extension, ['jpeg', 'jpg', 'png'])) {
                clearstatcache();
                $originalSize = Io::getFileSize($this->_imageSourcePath);
                $tempFile = $this->_autoGuessImageQuality($targetPath,
                    $originalSize, $extension, 0, 200);
                Io::move($tempFile, $targetPath, true);
            } else {
                $this->_image->save($targetPath, $options);
            }
        } catch (RuntimeException $e) {
            throw new ImageException(Craft::t('app', 'Failed to save the image.'), $e->getCode(), $e);
        }

        return true;
    }

    /**
     * Loads an image from an SVG string.
     *
     * @param $svgContent
     *
     * @return $this Self reference
     * @throws ImageException if the SVG string cannot be loaded.
     */
    public function loadFromSVG($svgContent)
    {
        try {
            $this->_image = $this->_instance->load($svgContent);
        } catch (RuntimeException $e) {
            try {
                // Invalid SVG. Maybe it's missing its DTD?
                $svgContent = '<?xml version="1.0" encoding="UTF-8" standalone="no"?>'.$svgContent;
                $this->_image = $this->_instance->load($svgContent);
            } catch (RuntimeException $e) {
                throw new ImageException(Craft::t('app', 'Failed to load the SVG string.'), $e->getCode(), $e);
            }
        }

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getIsTransparent()
    {
        if (Craft::$app->getImages()->getIsImagick() && method_exists('Imagick', 'getImageAlphaChannel')) {
            return $this->_image->getImagick()->getImageAlphaChannel();
        }

        return false;
    }

    /**
     * Returns EXIF metadata for a file by its path.
     *
     * @param string $filePath
     *
     * @return array
     */
    public function getExifMetadata($filePath)
    {
        try {
            $exifReader = new ExifMetadataReader();
            $this->_instance->setMetadataReader($exifReader);
            $exif = $this->_instance->open($filePath)->metadata();

            return $exif->toArray();
        } catch (NotSupportedException $exception) {
            Craft::error($exception->getMessage());

            return [];
        }
    }

    /**
     * Sets properties for text drawing on the image.
     *
     * @param string  $fontFile path to the font file on server
     * @param integer $size     font size to use
     * @param string  $color    font color to use in hex format
     *
     * @return void
     */
    public function setFontProperties($fontFile, $size, $color)
    {
        if (empty($this->_palette)) {
            $this->_palette = new RGB();
        }

        $this->_font = $this->_instance->font($fontFile, $size,
            $this->_palette->color($color));
    }

    /**
     * Returns the bounding text box for a text string and an angle
     *
     * @param string  $text
     * @param integer $angle
     *
     * @return \Imagine\Image\BoxInterface
     * @throws ImageException if attempting to create text box with no font properties
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
     * Writes text on an image.
     *
     * @param string  $text
     * @param integer $x
     * @param integer $y
     * @param integer $angle
     *
     * @return void
     * @throws ImageException If attempting to create text box with no font properties et.
     */
    public function writeText($text, $x, $y, $angle = 0)
    {

        if (empty($this->_font)) {
            throw new ImageException(Craft::t('app',
                'No font properties have been set. Call ImageHelper::setFontProperties() first.'));
        }

        $point = new Point($x, $y);

        $this->_image->draw()->text($text, $this->_font, $point, $angle);
    }

    // Private Methods
    // =========================================================================

    /**
     * @param         $tempFileName
     * @param         $originalSize
     * @param         $extension
     * @param         $minQuality
     * @param         $maxQuality
     * @param integer $step
     *
     * @return string the resulting file path
     */
    private function _autoGuessImageQuality($tempFileName, $originalSize, $extension, $minQuality, $maxQuality, $step = 0)
    {
        // Give ourselves some extra time.
        @set_time_limit(30);

        if ($step == 0) {
            $tempFileName = Io::getFolderName($tempFileName).Io::getFilename($tempFileName,
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
        return (Craft::$app->getImages()->getIsGd() ? Imagine::FILTER_UNDEFINED : Imagine::FILTER_LANCZOS);
    }

    /**
     * Returns save options.
     *
     * @param integer|null $quality
     * @param string       $extension
     *
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
                return ['jpeg_quality' => $quality, 'flatten' => true];
            }

            case 'gif': {
                $options = ['animated' => $this->_isAnimatedGif];

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

                $options = [
                    'png_compression_level' => $normalizedQuality,
                    'flatten' => false
                ];
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
                return [];
            }
        }
    }
}
