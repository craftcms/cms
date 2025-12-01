<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\web\assets\codemirror;

use craft\web\AssetBundle;

/**
 * CodeMirror asset bundle.
 */
class CodeMirrorAsset extends AssetBundle
{
    /** @inheritdoc */
    public $sourcePath = __DIR__ . '/dist';

    /** @inheritdoc */
    public $js = [
        'codemirror.js',
        'javascript.js',
    ];

    /** @inheritdoc */
    public $css = [
        'codemirror.css',
    ];
}
