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
    public function init(): void
    {
        // Figure out which Datepicker i18n script to load
        $languageId = Craft::$app->getLocale()->getLanguageID();

        $languages = [
            Craft::$app->language,
            $languageId,
        ];

        $fallbacks = [
            'cy' => 'cy-GB',
            'zh' => 'zh-CN',
        ];

        if (isset($fallbacks[$languageId])) {
            $languages[] = $fallbacks[$languageId];
        }

        $sourcePath = __DIR__ . '/dist';

        foreach ($languages as $language) {
            $filename = "datepicker-$language.js";
            if (file_exists("$sourcePath/$filename")) {
                $this->sourcePath = $sourcePath;

                $this->depends = [
                    JqueryUiAsset::class,
                ];

                $this->js = [
                    $filename,
                ];

                break;
            }
        }

        parent::init();
    }
}
