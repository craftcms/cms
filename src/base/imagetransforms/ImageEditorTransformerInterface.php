<?php

declare(strict_types=1);
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\base\imagetransforms;

use craft\elements\Asset;
use craft\models\ImageTransform;

/**
 * ImageEditorTransformerInterface defines the common interface to be implemented by all image drivers that support image editor
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.0.0
 */
interface ImageEditorTransformerInterface
{
    /**
     * Start editing an image by providing an Asset.
     *
     * @param Asset $asset
     */
    public function startImageEditing(Asset $asset): void;

    /**
     * Flip the image
     *
     * @param bool $flipX
     * @param bool $flipY
     */
    public function flipImage(bool $flipX, bool $flipY): void;

    /**
     * Scale the image
     *
     * @param int $width
     * @param int $height
     */
    public function scaleImage(int $width, int $height): void;

    /**
     * Rotate the image.
     *
     * @param float $degrees
     */
    public function rotateImage(float $degrees): void;

    /**
     * Get the current width of the edited image.
     *
     * @return int $width
     */
    public function getEditedImageWidth(): int;

    /**
     * Get the current height of the edited image.
     *
     * @return int $height
     */
    public function getEditedImageHeight(): int;

    /**
     * @param int $x
     * @param int $y
     * @param int $width
     * @param int $height
     */
    public function crop(int $x, int $y, int $width, int $height): void;

    /**
     * Finish editing the image and return the file location of the contents;
     *
     * @return string
     */
    public function finishImageEditing(): string;

    /**
     * Cancel the image editing and return the location for a temporary file that was created.
     *
     * @return string
     */
    public function cancelImageEditing(): string;
}
