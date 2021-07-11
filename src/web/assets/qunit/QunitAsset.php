<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\web\assets\qunit;

use craft\web\AssetBundle;

/**
 * Qunit asset bundle.
 */
class QunitAsset extends AssetBundle
{
    /**
     * @inheritdoc
     */
    public $sourcePath = __DIR__ . '/dist';

    /**
     * @inheritdoc
     */
    public $js = [
        'qunit.js',
    ];

    /**
     * @inheritdoc
     */
    public $css = [
        'css/qunit.css',
    ];
}
