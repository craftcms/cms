<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
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
    public function init()
    {
        $this->sourcePath = '@bower/jquery-touch-events/src';

        $this->js = [
            'jquery.mobile-events'.$this->dotJs(),
        ];

        parent::init();
    }
}
