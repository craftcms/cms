<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\web\assets\focalpoint;

use craft\web\AssetBundle;
use CraftCms\Cms\View\LegacyAssets\InternalAssetRegistry;

/**
 * Asset bundle for the Focal Point class.
 * @deprecated 6.0.0
 */
class FocalPointAsset extends AssetBundle
{
    public function registerAssetFiles($view)
    {
        app(InternalAssetRegistry::class)->register(\CraftCms\Cms\View\LegacyAssets\FocalPointAsset::class);
    }
}
