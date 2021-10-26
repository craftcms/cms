<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\web\assets\focusvisible;

use craft\web\AssetBundle;

/**
 * Focus Visible asset bundle
 *
 * @since 3.7.12
 */
class FocusVisibleAsset extends AssetBundle
{
    /**
     * @inheritdoc
     */
    public $sourcePath = '@lib/focus-visible';

    /**
     * @inheritdoc
     */
    public $js = [
        'focus-visible.js',
    ];
}
