<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\web\assets\jquerytouchevents;

use craft\web\AssetBundle;

/**
 * JqueryTouchEvents asset bundle.
 */
class JqueryTouchEventsAsset extends AssetBundle
{
    /**
     * @inheritdoc
     */
    public $sourcePath = '@lib/jquery-touch-events';

    /**
     * @inheritdoc
     */
    public $js = [
        'jquery.mobile-events.js',
    ];
}
