<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\web\assets\prismjs;

use craft\web\AssetBundle;

/**
 * PrismJs asset bundle.
 */
class PrismJsAsset extends AssetBundle
{
    /**
     * @inheritdoc
     */
    public $sourcePath = __DIR__ . '/dist';

    /**
     * @inheritdoc
     */
    public $js = [
        'prism.min.js',
    ];

    /**
     * @inheritdoc
     */
    public $css = [
        'css/prism.css',
    ];
}
