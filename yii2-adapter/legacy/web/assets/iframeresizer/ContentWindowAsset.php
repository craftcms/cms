<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\web\assets\iframeresizer;

use craft\web\AssetBundle;
use CraftCms\Cms\View\LegacyAssets\InternalAssetRegistry;

/**
 * Iframe Resizer Content Window asset bundle.
 *
 * This should be included by Live Preview templates.
 *
 * @since 3.5.0
 * @deprecated in 6.0.0
 */
class ContentWindowAsset extends AssetBundle
{
    public function registerAssetFiles($view)
    {
        app(InternalAssetRegistry::class)->register(\CraftCms\Cms\View\LegacyAssets\ContentWindowAsset::class);
    }
}
