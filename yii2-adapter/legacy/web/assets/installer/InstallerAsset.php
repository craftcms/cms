<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\web\assets\installer;

use craft\web\AssetBundle;
use CraftCms\Cms\View\LegacyAssets\InternalAssetRegistry;

/**
 * Asset bundle for the Installer
 * @deprecated 6.0.0
 */
class InstallerAsset extends AssetBundle
{
    /**
     * @inheritdoc
     */
    public function registerAssetFiles($view): void
    {
        app(InternalAssetRegistry::class)->register(\CraftCms\Cms\View\LegacyAssets\InstallerAsset::class);
    }
}
