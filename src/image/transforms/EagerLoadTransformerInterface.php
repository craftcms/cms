<?php
declare(strict_types=1);

/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\image\transforms;

use craft\elements\Asset;
use craft\models\ImageTransform;

/**
 * EagerLoadTransformerInterface defines the common interface to be implemented by all image drivers that can eager-load transforms
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.0.0
 */
interface EagerLoadTransformerInterface
{
    /**
     * Returns the URL for an image asset transform.
     *
     * @param Asset[] $assets
     * @param ImageTransform[] $transforms
     */
    public function eagerLoadTransforms(array $assets, array $transforms): void;
}
