<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\web\assets\timepicker;

use craft\web\AssetBundle;
use yii\web\JqueryAsset;

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
        $this->sourcePath = '@lib/timepicker';

        $this->depends = [
            JqueryAsset::class,
        ];

        $this->js = [
            'jquery.timepicker.js',
        ];

        parent::init();
    }
}
