<?php

declare(strict_types=1);

namespace CraftCms\Cms\Support\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static void js(string $js, \CraftCms\Cms\View\Enums\Position $position = 3, string|null $key = null)
 * @method static void jsWithVars(callable $fn, array $vars, \CraftCms\Cms\View\Enums\Position $position = 3, string|null $key = null)
 * @method static void jsFile(string $url, array $options = [], string|null $key = null)
 * @method static void cssFile(string $url, array $options = [], string|null $key = null)
 * @method static void css(string $css, array $options = [], string|null $key = null)
 * @method static void script(string $script, \CraftCms\Cms\View\Enums\Position $position = 3, array $options = [], string|null $key = null)
 * @method static void scriptWithVars(callable $fn, array $vars, \CraftCms\Cms\View\Enums\Position $position = 3, array $options = [], string|null $key = null)
 * @method static void html(string $html, \CraftCms\Cms\View\Enums\Position $position = 3, string|null $key = null)
 * @method static void jsImport(string $key, string $value)
 * @method static void icons(array<string> $icons)
 * @method static void metaTag(array $attributes, string|null $key = null)
 * @method static void linkTag(array $attributes, string|null $key = null)
 * @method static string headHtml(bool $clear = true)
 * @method static string bodyHtml(bool $clear = true)
 * @method static string bodyBeginHtml(bool $clear = true)
 * @method static string bodyEndHtml(bool $clear = true)
 * @method static void startBuffer(array|string $keys)
 * @method static mixed clearBuffer(array|string $keys)
 * @method static void startCssBuffer()
 * @method static array<string, \Stringable|string> clearCssBuffer()
 * @method static void startCssFileBuffer()
 * @method static array<string, \Stringable|string> clearCssFileBuffer()
 * @method static void startHtmlBuffer()
 * @method static array<int, array<string, string>> clearHtmlBuffer()
 * @method static void startJsBuffer()
 * @method static string|array clearJsBuffer(bool $scriptTag = true, bool $combine = true)
 * @method static void startJsFileBuffer()
 * @method static array<int, array<string, \Stringable|string>> clearJsFileBuffer()
 * @method static void startJsImportBuffer()
 * @method static array<string, string> clearJsImportBuffer()
 * @method static void startMetaTagBuffer()
 * @method static array<string, \Stringable|string> clearMetaTagBuffer()
 * @method static void startScriptBuffer()
 * @method static array<int, array<string, \Stringable|string>> clearScriptBuffer()
 * @method static void applyBuffer(array<string, mixed> $buffer)
 * @method static void clear()
 *
 * @see \CraftCms\Cms\View\HtmlStack
 */
class HtmlStack extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \CraftCms\Cms\View\HtmlStack::class;
    }
}
