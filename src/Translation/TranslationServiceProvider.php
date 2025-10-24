<?php

declare(strict_types=1);

namespace CraftCms\Cms\Translation;

use Illuminate\Support\ServiceProvider;
use Yiisoft\Translator\CategorySource;
use Yiisoft\Translator\IntlMessageFormatter;
use Yiisoft\Translator\Message\Php\MessageSource;
use Yiisoft\Translator\Translator;

final class TranslationServiceProvider extends ServiceProvider
{
    #[\Override]
    public function register(): void
    {
        $this->app->singleton(Translator::class, function () {
            $translator = new Translator(
                locale: app()->getLocale(),
                fallbackLocale: $this->app['config']->get('app.fallback_locale'),
                defaultCategory: 'app',
            );

            $appMessageSource = new MessageSource(dirname(__DIR__, 2).'/resources/translations');
            $formatter = new IntlMessageFormatter;
            $appCategory = new CategorySource(
                name: 'app',
                reader: $appMessageSource,
                formatter: $formatter
            );

            $siteMessageSource = new MessageSource(lang_path());
            $formatter = new IntlMessageFormatter;
            $siteCategory = new CategorySource(
                name: 'site',
                reader: $siteMessageSource,
                formatter: $formatter
            );

            $translator->addCategorySources($appCategory, $siteCategory);

            return $translator;
        });
    }
}
