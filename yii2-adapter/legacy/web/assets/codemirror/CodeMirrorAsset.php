<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\web\assets\codemirror;

use craft\web\AssetBundle;
use CraftCms\Cms\View\LegacyAssets\InternalAssetRegistry;

/**
 * CodeMirror asset bundle.
 * @deprecated 6.0.0
 */
class CodeMirrorAsset extends AssetBundle
{
    public function registerAssetFiles($view): void
    {
        app(InternalAssetRegistry::class)->register(\CraftCms\Cms\View\LegacyAssets\CodeMirrorAsset::class);
    }
}
