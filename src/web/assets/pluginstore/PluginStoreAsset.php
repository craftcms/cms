<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\web\assets\pluginstore;

use craft\vue\Asset;
use craft\web\AssetBundle;
use craft\web\assets\cp\CpAsset;
use craft\web\assets\vueresource\VueResourceAsset;

/**
 * Asset bundle for the Plugin Store page
 */
class PluginStoreAsset extends AssetBundle
{
    /**
     * @inheritdoc
     */
    public function init()
    {
        $this->sourcePath = __DIR__.'/dist';

        $this->depends = [
            CpAsset::class,
            Asset::class,
            VueResourceAsset::class,
        ];

        $this->js = [
            'parseFragmentString'.$this->dotJs(),
            'PluginStoreOauthCallback'.$this->dotJs(),
        ];

        $this->css = [
            'pluginstore.css',
            'pluginstore-oauth-callback.css',
        ];

        parent::init();
    }
}
