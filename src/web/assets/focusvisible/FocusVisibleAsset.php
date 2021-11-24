<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\web\assets\focusvisible;

use craft\web\AssetBundle;

/**
 * Focus visible asset bundle.
 */
class FocusVisibleAsset extends AssetBundle
{
    /** @inheritdoc */
    public $sourcePath = __DIR__ . '/dist';

    /** @inheritdoc */
    public $js = [
        'focus-visible.js',
    ];
}
