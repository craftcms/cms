<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\web\assets\axios;

use craft\web\InternalAssetBundle;

/**
 * Vue asset bundle.
 */
class AxiosAsset extends InternalAssetBundle
{
    /** @inheritdoc */
    public $sourcePath = __DIR__ . '/dist';

    /** @inheritdoc */
    public $js = [
        'axios.js',
    ];
}
