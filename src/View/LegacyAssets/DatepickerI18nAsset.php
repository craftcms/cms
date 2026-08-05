<?php

declare(strict_types=1);

namespace CraftCms\Cms\View\LegacyAssets;

use CraftCms\Cms\Support\CmsAssets;
use CraftCms\Cms\Translation\I18N;
use CraftCms\Cms\View\HtmlStack;

use function CraftCms\Cms\craftAsset;

/**
 * @deprecated
 *
 * @internal
 */
class DatepickerI18nAsset implements LegacyAssetInterface
{
    public array $depends = [];

    /** @var list<string> */
    private array $js = [];

    public function __construct(I18N $i18N)
    {
        // Figure out which Datepicker i18n script to load
        $languageId = $i18N->getLocale()->getLanguageID();

        $languages = [
            app()->getLocale(),
            $languageId,
        ];

        $fallbacks = [
            'cy' => 'cy-GB',
            'zh' => 'zh-CN',
        ];

        if (isset($fallbacks[$languageId])) {
            $languages[] = $fallbacks[$languageId];
        }

        $sourcePath = CmsAssets::resourcesPath('legacy/datepickeri18n/dist');

        foreach ($languages as $language) {
            $filename = "datepicker-$language.js";

            if (file_exists("$sourcePath/$filename")) {
                $this->depends = [
                    JqueryUiAsset::class,
                ];

                $this->js = [
                    $filename,
                ];

                break;
            }
        }
    }

    public function register(HtmlStack $htmlStack): void
    {
        foreach ($this->js as $js) {
            $htmlStack->jsFile(craftAsset("legacy/datepickeri18n/dist/$js"));
        }
    }
}
