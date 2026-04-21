<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\web\assets\axios;

use craft\web\AssetBundle;
use CraftCms\Cms\View\LegacyAssets\InternalAssetRegistry;

/**
 * Vue asset bundle.
 * @deprecated 6.0.0
 */
class AxiosAsset extends AssetBundle
{
    public function registerAssetFiles($view): void
    {
        app(InternalAssetRegistry::class)->register(\CraftCms\Cms\View\LegacyAssets\AxiosAsset::class);
    }
}
