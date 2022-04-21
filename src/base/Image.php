<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\base;

use craft\errors\ImageException;
use craft\helpers\Image as ImageHelper;
use yii\base\BaseObject;

/**
 * Base Image class.
 *
 * @property bool $isTransparent Whether the image is transparent
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
abstract class Image extends BaseObject
{
    /**
     * @var ?callable Heartbeat function to call, if needed.
     */
    private $_heartBeatCallback = null;

    /**
     * Returns the width of the image.
     *
     * @return int
     */
    abstract public function getWidth(): int;

    /**
     * Returns the height of the image.
     *
     * @return int
     */
    abstract public function getHeight(): int;

    /**
     * Returns the file extension.
     *
     * @return string
     */
    abstract public function getExtension(): string;

    /**
     * Loads an image from a file system path.
     *
     * @param string $path
     * @return static Self reference
     * @throws ImageException if the file cannot be loaded
     */
    abstract public function loadImage(string $path): self;

    /**
     * Crops the image to the specified coordinates.
     *
     * @param int $x1
     * @param int $x2
     * @param int $y1
     * @param int $y2
     * @return static Self reference
     */
    abstract public function crop(int $x1, int $x2, int $y1, int $y2): self;

    /**
     * Scale the image to fit within the specified size.
     *
     * @param int|null $targetWidth
     * @param int|null $targetHeight
     * @param bool $scaleIfSmaller
     * @return static Self reference
     */
    abstract public function scaleToFit(?int $targetWidth, ?int $targetHeight, bool $scaleIfSmaller = true): self;

    /**
     * Scale and crop image to exactly fit the specified size.
     *
     * @param int|null $targetWidth
     * @param int|null $targetHeight
     * @param bool $scaleIfSmaller
     * @param string|string[] $cropPosition
     * @return static Self reference
     */
    abstract public function scaleAndCrop(?int $targetWidth, ?int $targetHeight, bool $scaleIfSmaller = true, array|string $cropPosition = 'center-center'): self;

    /**
     * Resizes the image.
     *
     * @param int|null $targetWidth The target width
     * @param int|null $targetHeight The target height. Defaults to $targetWidth if omitted, creating a square.
     * @return static Self reference
     */
    abstract public function resize(?int $targetWidth, ?int $targetHeight): self;

    /**
     * Saves the image to the target path.
     *
     * @param string $targetPath
     * @param bool $autoQuality
     * @return bool
     * @throws ImageException if the image cannot be saved.
     */
    abstract public function saveAs(string $targetPath, bool $autoQuality = false): bool;

    /**
     * Returns whether the image is transparent.
     *
     * @return bool
     */
    abstract public function getIsTransparent(): bool;

    /**
     * Normalizes the given dimensions. If width or height is set to 'AUTO', we calculate the missing dimension.
     *
     * @param int|string|null $width
     * @param int|string|null $height
     */
    protected function normalizeDimensions(int|string|null &$width, int|string|null &$height): void
    {
        // See if $width is in "XxY" format
        if (preg_match('/^([\d]+|AUTO)x([\d]+|AUTO)/', (string)$width, $matches)) {
            $width = $matches[1] !== 'AUTO' ? (int)$matches[1] : null;
            $height = $matches[2] !== 'AUTO' ? (int)$matches[2] : null;
        }

        if (!$height || !$width) {
            [$width, $height] = ImageHelper::calculateMissingDimension($width, $height, $this->getWidth(), $this->getHeight());
        }
    }

    /**
     * Sets the heartbeat callback.
     *
     * @param callable|null $method
     * @since 4.0.0
     */
    public function setHeartbeatCallback(?callable $method = null): void
    {
        $this->_heartBeatCallback = $method;
    }

    /**
     * Let everyone back home know we're ok.
     *
     * @since 4.0.0
     */
    public function heartbeat(): void
    {
        if (is_callable($this->_heartBeatCallback)) {
            call_user_func($this->_heartBeatCallback);
        }
    }
}
