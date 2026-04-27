<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\web\assets\datepickeri18n;

use craft\web\AssetBundle;
use CraftCms\Cms\Support\Facades\I18N;
use CraftCms\Cms\View\LegacyAssets\InternalAssetRegistry;

/**
 * Datepicker I18n asset bundle.
 * @deprecated 6.0.0
 */
class DatepickerI18nAsset extends AssetBundle
{
    public function registerAssetFiles($view)
    {
        app(InternalAssetRegistry::class)->register(\CraftCms\Cms\View\LegacyAssets\DatepickerI18nAsset::class);
    }
}
