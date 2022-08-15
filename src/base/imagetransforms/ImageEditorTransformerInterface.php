<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\base\imagetransforms;

use craft\elements\Asset;

/**
 * ImageEditorTransformerInterface defines the common interface to be implemented by image drivers that support the Image Editor
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.0.0
 */
interface ImageEditorTransformerInterface
{
    /**
     * Begins an image editing process.
     *
     * @param Asset $asset
     */
    public function startImageEditing(Asset $asset): void;

    /**
     * Flips the image.
     *
     * @param bool $flipX
     * @param bool $flipY
     */
    public function flipImage(bool $flipX, bool $flipY): void;

    /**
     * Scales the image.
     *
     * @param int $width
     * @param int $height
     */
    public function scaleImage(int $width, int $height): void;

    /**
     * Rotates the image.
     *
     * @param float $degrees
     */
    public function rotateImage(float $degrees): void;

    /**
     * Returns the current width of the edited image.
     *
     * @return int $width
     */
    public function getEditedImageWidth(): int;

    /**
     * Returns the current height of the edited image.
     *
     * @return int $height
     */
    public function getEditedImageHeight(): int;

    /**
     * Crops the image.
     *
     * @param int $x
     * @param int $y
     * @param int $width
     * @param int $height
     */
    public function crop(int $x, int $y, int $width, int $height): void;

    /**
     * Completes an image editing process and returns the file location of the resulting image;
     *
     * @return string
     */
    public function finishImageEditing(): string;

    /**
     * Aborts the image editing process and returns the location of a temporary file that was created.
     *
     * @return string
     */
    public function cancelImageEditing(): string;
}
