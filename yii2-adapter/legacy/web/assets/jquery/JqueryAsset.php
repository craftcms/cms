<?php

declare(strict_types=1);
namespace craft\web\assets\jquery;

use craft\web\AssetBundle;
use CraftCms\Cms\View\LegacyAssets\InternalAssetRegistry;

/** @deprecated 6.0.0 */
class JqueryAsset extends AssetBundle
{
    public function registerAssetFiles($view)
    {
        app(InternalAssetRegistry::class)->register(\CraftCms\Cms\View\LegacyAssets\JqueryAsset::class);
    }
}
