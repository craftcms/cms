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
 * DeferredTransformerInterface defines the common interface to be implemented by all image drivers that can return
 * a temporary URL for a transformed image that will eventually return the generated transform.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.0.0
 */
interface DeferredTransformerInterface
{
    /**
     * Returns the URL for an image asset transform.
     *
     * @param Asset $asset
     * @param ImageTransform $imageTransform
     * @return string The URL for the transform
     */
    public function getDeferredTransformUrl(Asset $asset, ImageTransform $imageTransform): string;
}
