<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\app\web\assets;

use Craft;
use yii\web\AssetBundle;

/**
 * Application asset bundle.
 */
class AppAsset extends AssetBundle
{
    /**
     * @inheritdoc
     */
    public $sourcePath = '@app/resources';

    /**
     * @inheritdoc
     */
    public $depends = [
        \yii\web\JqueryAsset::class,
    ];

    /**
     * @inheritdoc
     */
    public $css = [
        'css/craft.css',
        'lib/selectize/selectize.css',
    ];

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();

        $useCompressedJs = (bool)Craft::$app->getConfig()->get('useCompressedJs');

        // Figure out which Datepicker i18n script to load
        $language = Craft::$app->language;

        if (in_array($language, ['en-GB', 'fr-CA'])) {
            $datepickerLanguage = $language;
        } else {
            $languageId = Craft::$app->getLocale()->getLanguageID();

            if (in_array($languageId,
                ['ar', 'de', 'fr', 'it', 'ja', 'nb', 'nl', 'nn', 'no'])) {
                $datepickerLanguage = $languageId;
            }
        }

        $this->js = [
            'lib/xregexp-all.js',
            'lib/jquery-ui'.($useCompressedJs ? '.min' : '').'.js',
        ];

        if (isset($datepickerLanguage)) {
            $this->js[] = "lib/datepicker-i18n/datepicker-{$datepickerLanguage}.js";
        }

        // Figure out which D3 i18n script to load

        if(in_array($language, ['ca-ES', 'de-CH', 'de-DE', 'en-CA', 'en-GB', 'en-US', 'es-ES', 'fi-FI', 'fr-CA', 'fr-FR', 'he-IL', 'hu-HU', 'it-IT', 'ja-JP', 'ko-KR', 'nl-NL', 'pl-PL', 'pt-BR', 'ru-RU', 'sv-SE', 'zh-CN']))
        {
            $d3Language = $language;
        }
        else
        {
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

            if(array_key_exists($languageId, $d3LanguageIds))
            {
                $d3Language = $d3LanguageIds[$languageId];
            }
            else
            {
                $d3Language = 'en-US';
            }
        }

        $this->js[] = "lib/d3-i18n/{$d3Language}.js";

        $this->js = array_merge($this->js, [
            'lib/velocity'.($useCompressedJs ? '.min' : '').'.js',
            'lib/selectize/selectize'.($useCompressedJs ? '.min' : '').'.js',
            'lib/fileupload/jquery.ui.widget.js',
            'lib/jquery.mobile-events'.($useCompressedJs ? '.min' : '').'.js',
            'lib/fileupload/jquery.fileupload.js',
            'lib/picturefill'.($useCompressedJs ? '.min' : '').'.js',
            'lib/element-resize-detector'.($useCompressedJs ? '.min' : '').'.js',
            'lib/garnish'.($useCompressedJs ? '.min' : '').'.js',
            'lib/d3/d3'.($useCompressedJs ? '.min' : '').'.js',
            'js/'.($useCompressedJs ? 'compressed/' : '').'Craft.js',
        ]);
    }
}
