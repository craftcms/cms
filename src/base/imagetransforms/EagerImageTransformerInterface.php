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
 * EagerLoadTransformerInterface defines the common interface to be implemented by all image drivers that can eager-load transforms.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.0.0
 */
interface EagerImageTransformerInterface
{
    /**
     * Eager-loads the given transforms for the given assets.
     *
     * @param ImageTransform[] $transforms
     * @param Asset[] $assets
     */
    public function eagerLoadTransforms(array $transforms, array $assets): void;

    /**
     * Eager-loads all the transforms for the given assets.
     *
     * @param Asset[] $assets
     * @since 5.9.0
     */
    public function eagerLoadAllTransforms(array $assets): void;
}
