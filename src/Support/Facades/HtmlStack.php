<?php

declare(strict_types=1);

namespace CraftCms\Cms\Support\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static void js(string $js, \CraftCms\Cms\View\Enums\Position $position = 4, string|null $key = null)
 * @method static void jsWithVars(callable $fn, array<array-key, mixed> $vars, \CraftCms\Cms\View\Enums\Position $position = 4, string|null $key = null)
 * @method static void jsFile(string $url, array<array-key, mixed> $options = [], string|null $key = null)
 * @method static void cssFile(string $url, array<array-key, mixed> $options = [], string|null $key = null)
 * @method static void css(string $css, array<array-key, mixed> $options = [], string|null $key = null)
 * @method static void script(string $script, \CraftCms\Cms\View\Enums\Position $position = 3, array<array-key, mixed> $options = [], string|null $key = null)
 * @method static void scriptWithVars(callable $fn, array<array-key, mixed> $vars, \CraftCms\Cms\View\Enums\Position $position = 3, array<array-key, mixed> $options = [], string|null $key = null)
 * @method static void html(string $html, \CraftCms\Cms\View\Enums\Position $position = 3, string|null $key = null)
 * @method static void jsImport(string $key, string $value)
 * @method static void icons(array<array-key, mixed> $icons)
 * @method static void metaTag(array<array-key, mixed> $attributes, string|null $key = null)
 * @method static void linkTag(array<array-key, mixed> $attributes, string|null $key = null)
 * @method static string headHtml(bool $clear = true)
 * @method static string bodyHtml(bool $clear = true)
 * @method static string bodyBeginHtml(bool $clear = true)
 * @method static string bodyEndHtml(bool $clear = true)
 * @method static \CraftCms\Cms\View\HtmlFragment capture(callable $render)
 * @method static void startBuffer(array<array-key, mixed>|string $keys)
 * @method static mixed clearBuffer(array<array-key, mixed>|string $keys)
 * @method static void startCssBuffer()
 * @method static array<array-key, mixed> clearCssBuffer()
 * @method static void startCssFileBuffer()
 * @method static array<array-key, mixed> clearCssFileBuffer()
 * @method static void startHtmlBuffer()
 * @method static array<array-key, mixed> clearHtmlBuffer()
 * @method static void startJsBuffer()
 * @method static string|array<array-key, mixed> clearJsBuffer(bool $scriptTag = true, bool $combine = true)
 * @method static void startJsFileBuffer()
 * @method static array<array-key, mixed> clearJsFileBuffer()
 * @method static void startJsImportBuffer()
 * @method static array<array-key, mixed> clearJsImportBuffer()
 * @method static void startMetaTagBuffer()
 * @method static array<array-key, mixed> clearMetaTagBuffer()
 * @method static void startScriptBuffer()
 * @method static array<array-key, mixed> clearScriptBuffer()
 * @method static void applyBuffer(array<array-key, mixed> $buffer)
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
