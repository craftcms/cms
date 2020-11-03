<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\web\assets\iframeresizer;

use craft\web\AssetBundle;

/**
 * Iframe Resizer asset bundle.
 *
 * @since 3.5.0
 */
class IframeResizerAsset extends AssetBundle
{
    /**
     * @inheritdoc
     */
    public $sourcePath = '@lib/iframe-resizer';

    /**
     * @inheritdoc
     */
    public $js = [
        'iframeResizer.js',
    ];
}
