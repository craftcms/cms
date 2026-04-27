<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\web\assets\prismjs;

use craft\web\AssetBundle;
use CraftCms\Cms\View\LegacyAssets\InternalAssetRegistry;

/**
 * PrismJs asset bundle.
 *
 * Prism JS files are manually added to the `dist` directory.
 * @deprecated 6.0.0
 */
class PrismJsAsset extends AssetBundle
{
    public function registerAssetFiles($view)
    {
        app(InternalAssetRegistry::class)->register(\CraftCms\Cms\View\LegacyAssets\PrismJsAsset::class);
    }
}
