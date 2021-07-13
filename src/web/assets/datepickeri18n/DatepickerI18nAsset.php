<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\web\assets\datepickeri18n;

use Craft;
use craft\web\AssetBundle;
use craft\web\assets\jqueryui\JqueryUiAsset;

/**
 * Datepicker I18n asset bundle.
 */
class DatepickerI18nAsset extends AssetBundle
{
    /**
     * @inheritdoc
     */
    public function init()
    {
        // Figure out which Datepicker i18n script to load
        $language = Craft::$app->language;

        if (in_array($language, ['en-GB', 'fr-CA'], true)) {
            $datepickerLanguage = $language;
        } else {
            $languageId = Craft::$app->getLocale()->getLanguageID();

            if (in_array($languageId, ['ar', 'cs', 'da', 'de', 'fr', 'he', 'hu', 'it', 'ja', 'ko', 'nb', 'nl', 'nn', 'no', 'pl', 'pt', 'ru', 'sk', 'sv', 'tr', 'zh'], true)) {
                $datepickerLanguage = $languageId;
            }
        }

        /** @noinspection UnSafeIsSetOverArrayInspection */
        if (isset($datepickerLanguage)) {
            $this->sourcePath = '@lib/datepicker-i18n';

            $this->depends = [
                JqueryUiAsset::class,
            ];

            $this->js = [
                "datepicker-{$datepickerLanguage}.js",
            ];
        }

        parent::init();
    }
}
