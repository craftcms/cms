<?php

declare(strict_types=1);

namespace CraftCms\Yii2Adapter\Mixins;

use Closure;
use CraftCms\Cms\Asset\Elements\Asset;
use CraftCms\Cms\Support\Facades\Deprecator;
use CraftCms\Yii2Adapter\Asset\LegacyAssetTransforms;

/**
 * Keeps Craft 5's `setTransform()` working on native assets, such as the ones returned by Assets fields.
 */
class AssetMixin
{
    public function setTransform(): Closure
    {
        return function(mixed $transform): Asset {
            Deprecator::log('Asset-setTransform', 'Calling `setTransform()` on an asset is deprecated, and the transform only applies to its URL. Use `asset.transform({...}).url` instead.');

            return app(LegacyAssetTransforms::class)->setTransform($this, $transform);
        };
    }
}
