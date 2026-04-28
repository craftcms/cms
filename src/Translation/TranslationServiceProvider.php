<?php

declare(strict_types=1);

namespace CraftCms\Cms\Translation;

use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Support\ServiceProvider;
use Yiisoft\Translator\CategorySource;
use Yiisoft\Translator\IntlMessageFormatter;
use Yiisoft\Translator\Message\Php\MessageSource;
use Yiisoft\Translator\Translator;

class TranslationServiceProvider extends ServiceProvider
{
    #[\Override]
    public function register(): void
    {
        $this->app->singleton(fn (): Translator => new Translator(
            locale: app()->getLocale(),
            fallbackLocale: $this->app->make(ConfigRepository::class)->get('app.fallback_locale'),
            defaultCategory: 'app',
        ));
    }

    public function boot(): void
    {
        $this->callAfterResolving(I18N::class, function (I18N $i18n): void {
            $appCategory = new CategorySource(
                name: 'app',
                reader: new MessageSource(dirname(__DIR__, 2).'/resources/translations'),
                formatter: new IntlMessageFormatter,
            );

            $siteCategory = new CategorySource(
                name: 'site',
                reader: new MessageSource(lang_path()),
                formatter: new IntlMessageFormatter,
            );

            $i18n->addCategorySources($appCategory, $siteCategory);
        });
    }
}
