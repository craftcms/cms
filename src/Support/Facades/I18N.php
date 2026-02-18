<?php

declare(strict_types=1);

namespace CraftCms\Cms\Support\Facades;

use CraftCms\Cms\Translation\Formatter;
use CraftCms\Cms\Translation\Locale;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Facade;
use Stringable;
use Yiisoft\Translator\CategorySource;

/**
 * @method static \CraftCms\Cms\Translation\Formatter getFormatter()
 * @method static \CraftCms\Cms\Translation\Locale getLocale()
 * @method static \CraftCms\Cms\Translation\Locale getFormattingLocale()
 * @method static \CraftCms\Cms\Translation\Locale getLocaleById(string $localeId)
 * @method static \Illuminate\Support\Collection getAllLocaleIds()
 * @method static \Illuminate\Support\Collection getAllLocales()
 * @method static \Illuminate\Support\Collection getAppLocaleIds()
 * @method static \Illuminate\Support\Collection getAppLocales()
 * @method static bool validateAppLocaleId(string $localeId)
 * @method static string normalizeLanguage(string $language)
 * @method static mixed normalizeNumber(mixed $number, string|null $localeId = null)
 * @method static \Illuminate\Support\Collection getSiteLocaleIds()
 * @method static \Illuminate\Support\Collection getSiteLocales()
 * @method static \Illuminate\Support\Collection getEditableLocales()
 * @method static \Illuminate\Support\Collection getEditableLocaleIds()
 * @method static string translate(\Stringable|string $message, array $parameters = [], string|null $category = null, string|null $locale = null)
 * @method static void addCategorySources(\Yiisoft\Translator\CategorySource ...$categories)
 * @method static string prep(string $message, array $params = [], ?string $category = null, ?string $locale = null)
 *
 * @see \CraftCms\Cms\Translation\I18N
 */
final class I18N extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \CraftCms\Cms\Translation\I18N::class;
    }
}
