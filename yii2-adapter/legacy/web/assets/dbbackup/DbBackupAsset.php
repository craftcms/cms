<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\web\assets\dbbackup;

use craft\web\AssetBundle;
use CraftCms\Cms\View\LegacyAssets\InternalAssetRegistry;

/**
 * Asset bundle for the DB Backup utility
 * @deprecated 6.0.0
 */
class DbBackupAsset extends AssetBundle
{
    public function registerAssetFiles($view)
    {
        app(InternalAssetRegistry::class)->register(\CraftCms\Cms\View\LegacyAssets\DbBackupAsset::class);
    }
}
