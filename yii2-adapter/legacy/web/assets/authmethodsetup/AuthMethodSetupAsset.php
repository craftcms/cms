<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\web\assets\authmethodsetup;

use craft\web\AssetBundle;
use CraftCms\Cms\View\LegacyAssets\InternalAssetRegistry;

/**
 * Authentication method setup asset bundle.
 *
 * @since 5.0.0
 * @deprecated 6.0.0
 */
class AuthMethodSetupAsset extends AssetBundle
{
    public function registerAssetFiles($view): void
    {
        app(InternalAssetRegistry::class)->register(\CraftCms\Cms\View\LegacyAssets\AuthMethodSetupAsset::class);
    }
}
