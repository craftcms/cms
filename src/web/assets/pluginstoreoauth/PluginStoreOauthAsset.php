<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\web\assets\pluginstoreoauth;

use craft\web\assets\cp\CpAsset;
use yii\web\AssetBundle;

/**
 * Asset bundle for the Plugin Store page
 */
class PluginStoreOauthAsset extends AssetBundle
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
        'css/PluginStoreOauthCallback.css',
    ];

    /**
     * @inheritdoc
     */
    public $js = [
        'parseFragmentString.min.js',
        'PluginStoreOauthCallback.min.js',
    ];
}
