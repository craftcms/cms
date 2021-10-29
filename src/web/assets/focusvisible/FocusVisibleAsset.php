<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\web\assets\axios;

use craft\web\AssetBundle;

/**
 * Focus visible asset bundle.
 */
class FocusVisibleAsset extends AssetBundle
{
    /** @inheritdoc */
    public $sourcePath = '@lib/focus-visible';

    /** @inheritdoc */
    public $js = [
        'focus-visible.js',
    ];
}
