<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\web\assets\vue;

use craft\web\InternalAssetBundle;

/**
 * Vue asset bundle.
 */
class VueAsset extends InternalAssetBundle
{
    /** @inheritdoc */
    public $sourcePath = __DIR__ . '/dist';

    /** @inheritdoc */
    public $js = [
        'vue.js',
    ];
}
