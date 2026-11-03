<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\web\assets\prismjs;

use craft\web\AssetBundle;
use craft\web\assets\highlight\HighlightAsset;

/**
 * PrismJs asset bundle.
 *
 * Prism is no longer bundled with Craft. This registers a `Prism` shim backed by TanStack Highlight,
 * which supports Prism’s commonly-used APIs, but not grammar customization, hooks, or plugins.
 *
 * @deprecated in 5.12.0. [[HighlightAsset]] should be used instead.
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
    public $depends = [
        HighlightAsset::class,
    ];

    /**
     * @inheritdoc
     */
    public $js = [
        'prism.js',
    ];
}
