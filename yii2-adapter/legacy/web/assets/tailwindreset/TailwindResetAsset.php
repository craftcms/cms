<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\web\assets\tailwindreset;

use craft\web\AssetBundle;
use CraftCms\Cms\View\LegacyAssets\InternalAssetRegistry;

/**
 * Asset bundle for the Tailwind reset
 * @deprecated 6.0.0
 */
class TailwindResetAsset extends AssetBundle
{
    public function registerAssetFiles($view): void
    {
        app(InternalAssetRegistry::class)->register(\CraftCms\Cms\View\LegacyAssets\TailwindResetAsset::class);
    }
}
