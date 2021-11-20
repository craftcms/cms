<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\web\assets\timepicker;

use yii\web\AssetBundle;
use yii\web\JqueryAsset;

/**
 * Timepicker asset bundle.
 */
class TimepickerAsset extends AssetBundle
{
    /**
     * @inheritdoc
     */
    public function init(): void
    {
        $this->sourcePath = __DIR__ . '/dist';

        $this->depends = [
            JqueryAsset::class,
        ];

        $this->js = [
            'jquery.timepicker.js',
        ];

        parent::init();
    }
}
