<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\web\assets;

use Craft;
use yii\web\AssetBundle;
use yii\web\JqueryAsset;

/**
 * Application asset bundle.
 */
class AppAsset extends AssetBundle
{
    /**
     * @inheritdoc
     */
    public function init()
    {
        $this->sourcePath = '@app/resources';
        $this->depends = [
            D3Asset::class,
            ElementResizeDetectorAsset::class,
            GarnishAsset::class,
            JqueryAsset::class,
            JqueryTouchEventsAsset::class,
            PicturefillAsset::class,
            SelectizeAsset::class,
            VelocityAsset::class,
        ];
        $this->css = [
            'css/craft.css',
            'css/charts.css',
        ];

        $useCompressedJs = (bool)Craft::$app->getConfig()->get('useCompressedJs');

        // Figure out which Datepicker i18n script to load
        $language = Craft::$app->language;

        if (in_array($language, ['en-GB', 'fr-CA'], true)) {
            $datepickerLanguage = $language;
        } else {
            $languageId = Craft::$app->getLocale()->getLanguageID();

            if (in_array($languageId, ['ar', 'de', 'fr', 'it', 'ja', 'nb', 'nl', 'nn', 'no'], true)) {
                $datepickerLanguage = $languageId;
            }
        }

        $this->js = [
            'lib/xregexp-all.js',
            'lib/jquery-ui'.($useCompressedJs ? '.min' : '').'.js',
        ];

        /** @noinspection UnSafeIsSetOverArrayInspection - FP */
        if (isset($datepickerLanguage)) {
            $this->js[] = "lib/datepicker-i18n/datepicker-{$datepickerLanguage}.js";
        }

        $this->js = array_merge($this->js, [
            'lib/fileupload/jquery.ui.widget.js',
            'lib/fileupload/jquery.fileupload.js',
            'js/Craft'.($useCompressedJs ? '.min.js' : '.js'),
        ]);

        parent::init();
    }
}
