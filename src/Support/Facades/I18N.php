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
 * @method static Formatter getFormatter()
 * @method static Locale getLocale()
 * @method static Locale getFormattingLocale()
 * @method static Locale getLocaleById(string $localeId)
 * @method static Collection getAllLocaleIds()
 * @method static Collection getAllLocales()
 * @method static Collection getAppLocaleIds()
 * @method static Collection getAppLocales()
 * @method static bool validateAppLocaleId(string $localeId)
 * @method static string normalizeLanguage(string $language)
 * @method static mixed normalizeNumber(mixed $number, string|null $localeId = null)
 * @method static Collection getSiteLocaleIds()
 * @method static Collection getSiteLocales()
 * @method static Collection getEditableLocales()
 * @method static Collection getEditableLocaleIds()
 * @method static string translate(Stringable|string $message, array $parameters = [], string|null $category = null, string|null $locale = null)
 * @method static void addCategorySources(CategorySource ...$categories)
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
