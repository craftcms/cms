<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\web\assets\jquerytouchevents;

use Craft;
use yii\web\AssetBundle;

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

        if (Craft::$app->getConfig()->get('useCompressedJs')) {
            $this->js[] = 'jquery.mobile-events.min.js';
        } else {
            $this->js[] = 'jquery.mobile-events.js';
        }

        parent::init();
    }
}
