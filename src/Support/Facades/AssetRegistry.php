<?php

declare(strict_types=1);

namespace CraftCms\Cms\Support\Facades;

use CraftCms\Cms\View\Enums\Position;
use Illuminate\Support\Facades\Facade;

/**
 * @method static void js(string $js, Position $position = 2, string|null $key = null)
 * @method static void jsWithVars(callable $fn, array $vars, Position $position = 2, string|null $key = null)
 * @method static void jsFile(string $url, array $options = [], string|null $key = null)
 * @method static void cssFile(string $url, array $options = [], string|null $key = null)
 * @method static void css(string $css, array $options = [], string|null $key = null)
 * @method static void script(string $script, Position $position = 2, array $options = [], string|null $key = null)
 * @method static void scriptWithVars(callable $fn, array $vars, Position $position = 2, array $options = [], string|null $key = null)
 * @method static void html(string $html, Position $position = 2, string|null $key = null)
 * @method static void jsImport(string $key, string $value)
 * @method static void translations(array $messages, string $category = 'app')
 * @method static void icons(array $icons)
 * @method static void metaTag(array $attributes, string|null $key = null)
 * @method static void linkTag(array $attributes, string|null $key = null)
 * @method static string headHtml(bool $clear = true)
 * @method static string bodyHtml(bool $clear = true)
 * @method static string bodyBeginHtml(bool $clear = true)
 * @method static string bodyEndHtml(bool $clear = true)
 * @method static void startBuffer(array|string $keys)
 * @method static mixed clearBuffer(array|string $keys)
 * @method static void startCssBuffer()
 * @method static array clearCssBuffer()
 * @method static void startCssFileBuffer()
 * @method static array clearCssFileBuffer()
 * @method static void startHtmlBuffer()
 * @method static array clearHtmlBuffer()
 * @method static void startJsBuffer()
 * @method static string|array clearJsBuffer(bool $scriptTag = true, bool $combine = true)
 * @method static void startJsFileBuffer()
 * @method static array clearJsFileBuffer()
 * @method static void startJsImportBuffer()
 * @method static array clearJsImportBuffer()
 * @method static void startMetaTagBuffer()
 * @method static array clearMetaTagBuffer()
 * @method static void startScriptBuffer()
 * @method static array clearScriptBuffer()
 * @method static void applyBuffer(array $buffer)
 * @method static void clear()
 *
 * @see \CraftCms\Cms\View\AssetRegistry
 */
final class AssetRegistry extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \CraftCms\Cms\View\AssetRegistry::class;
    }
}
