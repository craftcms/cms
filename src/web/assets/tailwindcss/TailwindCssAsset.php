<?php

/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\web\assets\tailwindcss;

use craft\base\HotReloadAssetBundle;

/**
 * Asset bundle for Tailwind CSS
 */
class TailwindCssAsset extends HotReloadAssetBundle
{
    /**
     * @inheritdoc
     */
    public $sourcePath = __DIR__ . '/dist';

    /**
     * @inheritdoc
     */
    public $css = [
        'css/TailwindCss.css',
    ];

    /**
     * @inheritdoc
     */
    public $js = [
        'TailwindCss.min.js',
    ];
}
