<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\web\assets\cp;

use craft\web\AssetBundle;
use CraftCms\Cms\View\LegacyAssets\InternalAssetRegistry;

/**
 * Asset bundle for the control panel
 * @deprecated 6.0.0
 */
class CpAsset extends AssetBundle
{
    public function registerAssetFiles($view)
    {
        $registry = app(InternalAssetRegistry::class);
        $registry->register(\CraftCms\Cms\View\LegacyAssets\CpAsset::class);
        $registry->flush();
    }
}
