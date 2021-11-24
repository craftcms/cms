<?php
declare(strict_types=1);

/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\base;

use craft\db\Query;
use craft\elements\Asset;
use craft\models\ImageTransform;
use craft\models\ImageTransformIndex;

/**
 * ImageTransformDriverInterface defines the common interface to be implemented by all image drivers.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.0.0
 */
interface ImageTransformDriverInterface
{
    /**
     * Returns the URL for an image asset transform.
     *
     * @return string The URL for the transform
     */
    public function getTransformUrl(Asset $asset, ImageTransformIndex $transformIndexModel): string;

    /**
     * Invalidate a created transform by asset and a transform index.
     *
     * @param ImageTransformIndex $transformIndex
     */
    public function invalidateTransform(Asset $asset, ImageTransformIndex $transformIndex): void;
}
