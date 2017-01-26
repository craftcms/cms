<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
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

            if (in_array($languageId, ['ar', 'de', 'fr', 'it', 'ja', 'nb', 'nl', 'nn', 'no'], true)) {
                $datepickerLanguage = $languageId;
            }
        }

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
