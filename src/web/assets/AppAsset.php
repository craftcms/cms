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

        // Figure out which D3 i18n script to load

        if (in_array($language, ['ca-ES', 'de-CH', 'de-DE', 'en-CA', 'en-GB', 'en-US', 'es-ES', 'fi-FI', 'fr-CA', 'fr-FR', 'he-IL', 'hu-HU', 'it-IT', 'ja-JP', 'ko-KR', 'nl-NL', 'pl-PL', 'pt-BR', 'ru-RU', 'sv-SE', 'zh-CN'], true)) {
            $d3Language = $language;
        } else {
            $languageId = Craft::$app->getLocale()->getLanguageID();

            $d3LanguageIds = [
                'ca' => 'ca-ES',
                'de' => 'de-DE',
                'en' => 'en-US',
                'es' => 'es-ES',
                'fi' => 'fi-FI',
                'fr' => 'fr-FR',
                'he' => 'he-IL',
                'hu' => 'hu-HU',
                'it' => 'it-IT',
                'ja' => 'ja-JP',
                'ko' => 'ko-KR',
                'nl' => 'nl-NL',
                'pl' => 'pl-PL',
                'pt' => 'pt-BR',
                'ru' => 'ru-RU',
                'sv' => 'sv-SE',
                'zh' => 'zh-CN',
            ];

            if (array_key_exists($languageId, $d3LanguageIds)) {
                $d3Language = $d3LanguageIds[$languageId];
            } else {
                $d3Language = 'en-US';
            }
        }

        $this->js[] = "lib/d3-i18n/{$d3Language}.js";

        $this->js = array_merge($this->js, [
            'lib/fileupload/jquery.ui.widget.js',
            'lib/fileupload/jquery.fileupload.js',
            'js/'.($useCompressedJs ? 'compressed/' : '').'Craft.js',
        ]);

        parent::init();
    }
}
