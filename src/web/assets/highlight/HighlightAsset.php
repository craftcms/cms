<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\web\assets\highlight;

use craft\web\AssetBundle;
use craft\web\assets\cp\CpAsset;

/**
 * TanStack Highlight asset bundle.
 *
 * Registers a `Craft.highlighter` object, which can be used to syntax-highlight code:
 *
 * ```js
 * const html = Craft.highlighter.highlight(code, {lang: 'json'}).html;
 * ```
 *
 * @since 5.12.0
 */
class HighlightAsset extends AssetBundle
{
    /**
     * @inheritdoc
     */
    public $sourcePath = __DIR__ . '/dist';

    /**
     * @inheritdoc
     */
    public $depends = [
        CpAsset::class,
    ];

    /**
     * @inheritdoc
     */
    public $js = [
        'highlight.js',
    ];

    /**
     * @inheritdoc
     */
    public $css = [
        'highlight.css',
    ];
}
