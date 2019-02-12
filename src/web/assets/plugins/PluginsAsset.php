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
    public function init()
    {
        $this->sourcePath = __DIR__ . '/dist';

        $this->depends = [
            CpAsset::class,
        ];

        $this->css = [
            'plugins.css',
        ];

        $this->js = [
            'PluginManager.js',
        ];

        parent::init();
    }

    /**
     * @inheritdoc
     */
    public function registerAssetFiles($view)
    {
        parent::registerAssetFiles($view);

        if ($view instanceof View) {
            $view->registerTranslations('app', [
                'A license key is required.',
                'Action',
                'Documentation',
                'Install',
                'Missing',
                'Status',
                'Switch',
                'This license is for the {name} edition.',
                'This license is tied to another Craft install. Visit {url} to resolve.',
                'This license isn’t allowed to run version {version}.',
                'Trial',
                'Your license key is invalid.',
            ]);
        }
    }
}
