<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\web\assets\timepicker;

use Craft;
use yii\web\AssetBundle;

/**
 * Timepicker asset bundle.
 */
class TimepickerAsset extends AssetBundle
{
    /**
     * @inheritdoc
     */
    public function init()
    {
        $this->sourcePath = '@bower/timepicker';

        if (Craft::$app->getConfig()->get('useCompressedJs')) {
            $this->js[] = 'jquery.timepicker.min.js';
        } else {
            $this->js[] = 'jquery.timepicker.js';
        }

        parent::init();
    }
}
