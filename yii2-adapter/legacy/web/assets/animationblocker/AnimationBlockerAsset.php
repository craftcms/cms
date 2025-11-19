<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\web\assets\animationblocker;

use craft\web\AssetBundle;

/**
 * Asset bundle for the Animation Blocker class.
 */
class AnimationBlockerAsset extends AssetBundle
{
    /**
     * @inheritdoc
     */
    public $sourcePath = __DIR__ . '/dist';

    /**
     * @inheritdoc
     */
    public $js = [
        'AnimationBlocker.js',
    ];
}
