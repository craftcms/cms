<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\web\assets\jquerytouchevents;

use craft\web\InternalAssetBundle;

/**
 * JqueryTouchEvents asset bundle.
 */
class JqueryTouchEventsAsset extends InternalAssetBundle
{
    /**
     * @inheritdoc
     */
    public $sourcePath = __DIR__ . '/dist';

    /**
     * @inheritdoc
     */
    public $js = [
        'jquery.mobile-events.js',
    ];
}
