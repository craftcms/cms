<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\web\assets\plugins;

use craft\web\AssetBundle;
use craft\web\assets\cp\CpAsset;
use craft\web\View;

/**
 * Asset bundle for the Plugins page
 */
class PluginsAsset extends AssetBundle
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
        'css/PluginManager.css',
    ];

    /**
     * @inheritdoc
     */
    public $js = [
        'PluginManager.js',
    ];

    /**
     * @inheritdoc
     */
    public function registerAssetFiles($view): void
    {
        parent::registerAssetFiles($view);

        if ($view instanceof View) {
            $view->registerTranslations('app', [
                '<a>Renew now</a> for another year of updates.',
                'A license key is required.',
                'Action',
                'Documentation',
                'Install',
                'Missing',
                'Plugin trials are not allowed on this domain.',
                'Status',
                'Switch',
                'This license is for the {name} edition.',
                'This license is tied to another Craft install. Visit {accountLink} to detach it, or <a href="{buyUrl}">buy a new license</a>',
                'This license isn’t allowed to run version {version}.',
                'Trial',
                'Your license key is invalid.',
            ]);
        }
    }
}
