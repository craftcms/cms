<?php

declare(strict_types=1);

namespace CraftCms\Cms\Support\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static \CraftCms\Cms\Translation\Formatter getFormatter()
 * @method static \CraftCms\Cms\Translation\Locale getLocale()
 * @method static \CraftCms\Cms\Translation\Locale getFormattingLocale()
 * @method static mixed withLocale(string $language, string|null $formattingLocaleId, callable $callback)
 * @method static \CraftCms\Cms\Translation\Locale getLocaleById(string $localeId)
 * @method static \Illuminate\Support\Collection<string> getAllLocaleIds()
 * @method static \Illuminate\Support\Collection<\Locale> getAllLocales()
 * @method static \Illuminate\Support\Collection<string> getAppLocaleIds()
 * @method static \Illuminate\Support\Collection<\Locale> getAppLocales()
 * @method static bool validateAppLocaleId(string $localeId)
 * @method static string normalizeLanguage(string $language)
 * @method static mixed normalizeNumber(mixed $number, string|null $localeId = null)
 * @method static \Illuminate\Support\Collection<string> getSiteLocaleIds()
 * @method static \Illuminate\Support\Collection<\Locale> getSiteLocales()
 * @method static \Illuminate\Support\Collection<\Locale> getEditableLocales()
 * @method static \Illuminate\Support\Collection<string> getEditableLocaleIds()
 * @method static string translate(\Stringable|string|null $message, array $parameters = [], string|null $category = null, string|null $locale = null)
 * @method static void addCategorySources(\Yiisoft\Translator\CategorySource ...$categories)
 * @method static array<string, array<string, string>> getAllTranslationsForLocale(string $locale)
 * @method static string prep(string $message, array $params = [], ?string $category = null, ?string $locale = null)
 *
 * @see \CraftCms\Cms\Translation\I18N
 */
class I18N extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \CraftCms\Cms\Translation\I18N::class;
    }
}
