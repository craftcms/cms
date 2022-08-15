<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\web\assets\upgrade;

use craft\web\AssetBundle;
use craft\web\assets\cp\CpAsset;
use craft\web\View;

/**
 * Asset bundle for the Upgrade utility
 *
 * @since 3.7.40
 */
class UpgradeAsset extends AssetBundle
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
        'css/UpgradeUtility.css',
    ];

    /**
     * @inheritdoc
     */
    public $js = [
        'UpgradeUtility.js',
    ];

    /**
     * @inheritdoc
     */
    public function registerAssetFiles($view)
    {
        parent::registerAssetFiles($view);

        if ($view instanceof View) {
            $view->registerTranslations('app', [
                'All plugins must be compatible with Craft {version} before you can upgrade.',
                'No plugins are installed.',
                'Not installed',
                'Not ready',
                'Plugin',
                'Plugins',
                'Ready to upgrade?',
                'Ready',
                'Requires PHP {version}',
                'Status',
                'The developer recommends using <a href="{url}">{name}</a> instead.',
                'Unable to fetch upgrade info at this time.',
                'Unknown',
                'View the <a>upgrade guide</a>',
            ]);
        }
    }
}
