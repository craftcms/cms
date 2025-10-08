<?php

namespace CraftCms\Cms\Translation;

use Illuminate\Support\ServiceProvider;
use Yiisoft\Translator\CategorySource;
use Yiisoft\Translator\IntlMessageFormatter;
use Yiisoft\Translator\Message\Php\MessageSource;
use Yiisoft\Translator\Translator;

final class TranslationServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(Translator::class, function () {
            $translator = new Translator(
                locale: $this->app['config']->get('app.locale'),
                fallbackLocale: $this->app['config']->get('app.fallback_locale'),
                defaultCategory: 'app',
            );

            $messageSource = new MessageSource(dirname(__DIR__, 2).'/resources/translations');
            $formatter = new IntlMessageFormatter;
            $category = new CategorySource(
                name: 'app',
                reader: $messageSource,
                formatter: $formatter
            );

            $translator->addCategorySources($category);

            return $translator;
        });
    }
}
