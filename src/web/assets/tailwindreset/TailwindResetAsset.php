<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\web\assets\tailwindreset;

use craft\web\AssetBundle;

/**
 * Asset bundle for the Tailwind reset
 */
class TailwindResetAsset extends AssetBundle
{
    /**
     * @inheritdoc
     */
    public $sourcePath = __DIR__ . '/dist';

    /**
     * @inheritdoc
     */
    public $css = [
        'css/tailwind_reset.css',
    ];

    /**
     * @inheritdoc
     */
    public $js = [
        'tailwind_reset.js',
    ];
}
