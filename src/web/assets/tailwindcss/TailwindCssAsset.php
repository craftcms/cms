<?php

/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\web\assets\tailwindcss;

use craft\web\AssetBundle;

/**
 * Asset bundle for Tailwind CSS
 */
class TailwindCssAsset extends AssetBundle
{
    /**
     * @inheritdoc
     */
    public $sourcePath = __DIR__ . '/dist';

    /**
     * @inheritdoc
     */
    public $css = [
        'TailwindCss.css',
    ];
}
