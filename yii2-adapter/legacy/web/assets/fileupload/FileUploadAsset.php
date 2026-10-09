<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

declare(strict_types=1);

namespace craft\web\assets\fileupload;

use craft\web\AssetBundle;
use CraftCms\Cms\View\LegacyAssets\InternalAssetRegistry;

/**
 * File Upload asset bundle.
 * @deprecated 6.0.0
 */
class FileUploadAsset extends AssetBundle
{
    public function registerAssetFiles($view)
    {
        app(InternalAssetRegistry::class)->register(\CraftCms\Yii2Adapter\View\LegacyAssets\FileUploadAsset::class);
    }
}
