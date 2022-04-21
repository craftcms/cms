<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\base\imagetransforms;

use craft\elements\Asset;
use craft\models\ImageTransform;

/**
 * TransformerInterface defines the common interface to be implemented by all image drivers.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.0.0
 */
interface ImageTransformerInterface
{
    /**
     * Returns the URL for an image transform.
     *
     * @param Asset $asset
     * @param ImageTransform $imageTransform
     * @param bool $immediately Whether the image should be transformed immediately
     * @return string The URL for the transform
     */
    public function getTransformUrl(Asset $asset, ImageTransform $imageTransform, bool $immediately): string;

    /**
     * Invalidates all transforms for an asset.
     *
     * @param Asset $asset
     */
    public function invalidateAssetTransforms(Asset $asset): void;
}
