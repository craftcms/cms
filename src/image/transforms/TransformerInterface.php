<?php
declare(strict_types = 1);

/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\image\transforms;

use craft\elements\Asset;
use craft\models\ImageTransform;

/**
 * TransformerInterface defines the common interface to be implemented by all image drivers.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.0.0
 */
interface TransformerInterface
{
    /**
     * Returns the URL for an image asset transform.
     *
     * @param Asset $asset
     * @param ImageTransform $imageTransform
     * @return string The URL for the transform
     */
    public function getTransformUrl(Asset $asset, ImageTransform $imageTransform): string;

    /**
     * Invalidate a created transform by asset and a transform index.
     *
     * @param Asset $asset
     */
    public function invalidateAssetTransforms(Asset $asset): void;
}
