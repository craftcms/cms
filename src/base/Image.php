<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\base;

use craft\errors\ImageException;
use craft\helpers\Image as ImageHelper;
use craft\image\Svg;
use yii\base\Object;

/**
 * Base Image class.
 *
 * @property bool $isTransparent Whether the image is transparent
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
abstract class Image extends Object
{
    /**
     * Returns the width of the image.
     *
     * @return int
     */
    abstract public function getWidth();

    /**
     * Returns the height of the image.
     *
     * @return int
     */
    abstract public function getHeight();

    /**
     * Returns the file extension.
     *
     * @return string
     */
    abstract public function getExtension();

    /**
     * Loads an image from a file system path.
     *
     * @param string $path
     *
     * @return static|Svg Self reference
     * @throws ImageException if the file cannot be loaded
     */
    abstract public function loadImage($path);

    /**
     * Crops the image to the specified coordinates.
     *
     * @param int $x1
     * @param int $x2
     * @param int $y1
     * @param int $y2
     *
     * @return static Self reference
     */
    abstract public function crop($x1, $x2, $y1, $y2);

    /**
     * Scale the image to fit within the specified size.
     *
     * @param int      $targetWidth
     * @param int|null $targetHeight
     * @param bool     $scaleIfSmaller
     *
     * @return static Self reference
     */
    abstract public function scaleToFit($targetWidth, $targetHeight = null, $scaleIfSmaller = true);

    /**
     * Scale and crop image to exactly fit the specified size.
     *
     * @param int      $targetWidth
     * @param int|null $targetHeight
     * @param bool     $scaleIfSmaller
     * @param string   $cropPositions
     *
     * @return static Self reference
     */
    abstract public function scaleAndCrop($targetWidth, $targetHeight = null, $scaleIfSmaller = true, $cropPositions = 'center-center');

    /**
     * Resizes the image.
     *
     * @param int      $targetWidth  The target width
     * @param int|null $targetHeight The target height. Defaults to $targetWidth if omitted, creating a square.
     *
     * @return static Self reference
     */
    abstract public function resize($targetWidth, $targetHeight = null);

    /**
     * Saves the image to the target path.
     *
     * @param string $targetPath
     * @param bool   $autoQuality
     *
     * @return bool
     * @throws ImageException if the image cannot be saved.
     */
    abstract public function saveAs($targetPath, $autoQuality = false);

    /**
     * Returns whether the image is transparent.
     *
     * @return bool
     */
    abstract public function getIsTransparent();

    // Protected Methods
    // =========================================================================

    /**
     * Normalizes the given dimensions.  If width or height is set to 'AUTO', we calculate the missing dimension.
     *
     * @param int|string|null $width
     * @param int|string|null $height
     */
    protected function normalizeDimensions(&$width, &$height)
    {
        // See if $width is in "XxY" format
        if (preg_match('/^([\d]+|AUTO)x([\d]+|AUTO)/', $width, $matches)) {
            $width = $matches[1] !== 'AUTO' ? (int)$matches[1] : null;
            $height = $matches[2] !== 'AUTO' ? (int)$matches[2] : null;
        }

        if (!$height || !$width) {
            list($width, $height) = ImageHelper::calculateMissingDimension($width, $height, $this->getWidth(), $this->getHeight());
        }
    }
}
