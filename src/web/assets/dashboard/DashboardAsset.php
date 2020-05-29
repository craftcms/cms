<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\web\assets\dashboard;

use craft\web\AssetBundle;
use craft\web\assets\cp\CpAsset;
use craft\web\View;

/**
 * Asset bundle for the Dashboard
 */
class DashboardAsset extends AssetBundle
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
        'dashboard.css',
    ];

    /**
     * @inheritdoc
     */
    public $js = [
        'Dashboard.min.js',
    ];

    /**
     * @inheritdoc
     */
    public function registerAssetFiles($view)
    {
        parent::registerAssetFiles($view);

        if ($view instanceof View) {
            $view->registerTranslations('app', [
                '{num, number} {num, plural, =1{column} other{columns}}',
                '{type} Settings',
                'Widget saved.',
                'Couldn’t save widget.',
                'You don’t have any widgets yet.',
            ]);
        }
    }
}
