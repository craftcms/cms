<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\web\assets\cp;

use craft\web\AssetBundle;
use craft\web\assets\d3\D3Asset;
use craft\web\assets\datepickeri18n\DatepickerI18nAsset;
use craft\web\assets\elementresizedetector\ElementResizeDetectorAsset;
use craft\web\assets\fileupload\FileUploadAsset;
use craft\web\assets\garnish\GarnishAsset;
use craft\web\assets\jquerypayment\JqueryPaymentAsset;
use craft\web\assets\jquerytouchevents\JqueryTouchEventsAsset;
use craft\web\assets\jqueryui\JqueryUiAsset;
use craft\web\assets\picturefill\PicturefillAsset;
use craft\web\assets\selectize\SelectizeAsset;
use craft\web\assets\velocity\VelocityAsset;
use craft\web\assets\xregexp\XregexpAsset;
use yii\web\JqueryAsset;

/**
 * Asset bundle for the Control Panel
 */
class CpAsset extends AssetBundle
{
    /**
     * @inheritdoc
     */
    public function init()
    {
        $this->sourcePath = __DIR__.'/dist';

        $this->depends = [
            D3Asset::class,
            ElementResizeDetectorAsset::class,
            GarnishAsset::class,
            JqueryAsset::class,
            JqueryTouchEventsAsset::class,
            JqueryUiAsset::class,
            JqueryPaymentAsset::class,
            DatepickerI18nAsset::class,
            PicturefillAsset::class,
            SelectizeAsset::class,
            VelocityAsset::class,
            FileUploadAsset::class,
            XregexpAsset::class,
        ];

        $this->css = [
            'css/craft.css',
            'css/charts.css',
        ];

        $this->js[] = 'js/Craft'.$this->dotJs();

        parent::init();
    }
}
