<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

declare(strict_types=1);

namespace craft\web\assets\uploads;

use craft\web\AssetBundle;
use CraftCms\Cms\View\LegacyAssets\InternalAssetRegistry;

/**
 * Defines `Craft.Uploads.FileUpload`, which uploads files through {@see \CraftCms\Cms\Filesystem\Uploads upload sessions},
 * and uploads files chosen in `<input type="file" data-upload-session>` inputs.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 5.13.0
 */
class UploadsAsset extends AssetBundle
{
    public function registerAssetFiles($view)
    {
        app(InternalAssetRegistry::class)->register(\CraftCms\Yii2Adapter\View\LegacyAssets\UploadsAsset::class);
    }
}
