<?php

declare(strict_types=1);

namespace CraftCms\Yii2Adapter\I18N;

use CraftCms\Cms\Support\Facades\Deprecator;
use CraftCms\Cms\Support\Facades\I18N;
use Yiisoft\Translator\CategorySource;
use Yiisoft\Translator\IntlMessageFormatter;
use Yiisoft\Translator\Message\Php\MessageSource;

readonly class I18NCompatibility
{
    public function boot(): void
    {
        /**
         * Add fallback for when translations are still stored in `/translations`
         */
        if (is_dir(base_path('translations'))) {
            Deprecator::log('translations-path', 'Storing site translations in `/translations` is deprecated. Rename the folder to `lang` instead.');

            I18N::addCategorySources(new CategorySource(
                'site',
                new MessageSource(base_path('translations')),
                new IntlMessageFormatter(),
            ));
        }

        /**
         * Load legacy translations
         */
        I18N::addCategorySources(new CategorySource(
            'yii2-adapter',
            new MessageSource(dirname(__DIR__) . '/resources/translations'),
            new IntlMessageFormatter(),
        ));
    }
}
