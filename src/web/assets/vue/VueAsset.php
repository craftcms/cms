<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\web\assets\vue;

use craft\web\AssetBundle;

/**
 * Vue asset bundle.
 */
class VueAsset extends AssetBundle
{
    /** @inheritdoc */
    public $sourcePath = '@lib/vue';

    /** @inheritdoc */
    public $js = [
        'vue.js',
    ];
}
