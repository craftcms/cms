<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\web\assets\timepicker;

use craft\web\InternalAssetBundle;
use craft\web\assets\jquery\JqueryAsset;

/**
 * Timepicker asset bundle.
 */
class TimepickerAsset extends InternalAssetBundle
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
