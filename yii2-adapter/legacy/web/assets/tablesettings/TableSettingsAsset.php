<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\web\assets\tablesettings;

use craft\web\AssetBundle;
use craft\web\assets\cp\CpAsset;

/**
 * Asset bundle for Table field settings
 */
class TableSettingsAsset extends AssetBundle
{
    /**
     * @inheritdoc
     */
    public $sourcePath = '@assetBundles/tablesettings/dist';

    /**
     * @inheritdoc
     */
    public $depends = [
        CpAsset::class,
    ];

    /**
     * @inheritdoc
     */
    public $js = [
        'TableFieldSettings.js',
    ];
}
