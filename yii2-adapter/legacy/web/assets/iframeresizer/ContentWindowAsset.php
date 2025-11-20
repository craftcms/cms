<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\web\assets\iframeresizer;

use craft\web\AssetBundle;

/**
 * Iframe Resizer Content Window asset bundle.
 *
 * This should be included by Live Preview templates.
 *
 * @since 3.5.0
 */
class ContentWindowAsset extends AssetBundle
{
    /**
     * @inheritdoc
     */
    public $sourcePath = __DIR__ . '/dist';

    /**
     * @inheritdoc
     */
    public $js = [
        'iframeResizer.contentWindow.js',
    ];
}
