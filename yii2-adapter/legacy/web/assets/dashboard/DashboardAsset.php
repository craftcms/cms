<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\web\assets\dashboard;

use craft\web\InternalAssetBundle;
use craft\web\assets\cp\CpAsset;

/**
 * Asset bundle for the Dashboard
 */
class DashboardAsset extends InternalAssetBundle
{
    /**
     * @inheritdoc
     */
    public $sourcePath = __DIR__ . '/dist';

    /**
     * @inheritdoc
     */
    public $depends = [
        CpAsset::class,
    ];

    /**
     * @inheritdoc
     */
    public $css = [
        'css/Dashboard.css',
    ];

    /**
     * @inheritdoc
     */
    public $js = [
        'Dashboard.js',
    ];
}
