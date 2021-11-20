<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\web\assets\axios;

use craft\web\AssetBundle;

/**
 * Vue asset bundle.
 */
class AxiosAsset extends AssetBundle
{
    /** @inheritdoc */
    public $sourcePath = '@lib/axios';

    /** @inheritdoc */
    public $js = [
        'axios.min.js',
    ];
}
