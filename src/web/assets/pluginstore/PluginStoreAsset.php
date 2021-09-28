<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\web\assets\pluginstore;

use craft\base\HotReloadAssetBundle;
use craft\web\assets\cp\CpAsset;
use craft\web\assets\vue\VueAsset;

/**
 * Asset bundle for the Plugin Store page
 */
class PluginStoreAsset extends HotReloadAssetBundle
{
    /**
     * @inheritdoc
     */
    public function init(): void
    {
        $this->sourcePath = __DIR__ . '/dist/';

        $this->depends = [
            CpAsset::class,
            VueAsset::class,
        ];

        $this->css = [
            'css/chunk-vendors.css',
            'css/app.css',
        ];

        $this->js = [
            'js/chunk-vendors.js',
            'js/app.js',
        ];

        parent::init();
    }
}
