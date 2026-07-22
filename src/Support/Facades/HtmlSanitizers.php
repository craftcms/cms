<?php

declare(strict_types=1);

namespace CraftCms\Cms\Support\Facades;

use CraftCms\Cms\Support\HtmlSanitizer\HtmlSanitizerManager;
use Illuminate\Support\Facades\Facade;
use Override;

/**
 * @method static \CraftCms\Cms\Support\HtmlSanitizer\HtmlSanitizerManager extend(string $name, \Closure|\Symfony\Component\HtmlSanitizer\HtmlSanitizerInterface|array $definition)
 * @method static \CraftCms\Cms\Support\HtmlSanitizer\HtmlSanitizerManager defaults(\Closure $callback)
 * @method static bool has(string $name)
 * @method static array names()
 * @method static \Illuminate\Support\Collection all()
 * @method static string sanitize(string $html, \Symfony\Component\HtmlSanitizer\HtmlSanitizerInterface|string|null $sanitizer = null)
 * @method static \Symfony\Component\HtmlSanitizer\HtmlSanitizerInterface sanitizer(string|null $name = null)
 * @method static \Symfony\Component\HtmlSanitizer\HtmlSanitizerInterface driver(string|null $driver = null)
 * @method static \Symfony\Component\HtmlSanitizer\HtmlSanitizerConfig defaultConfig()
 * @method static array getDrivers()
 * @method static \CraftCms\Cms\Support\HtmlSanitizer\HtmlSanitizerManager forgetDrivers()
 *
 * @see HtmlSanitizerManager
 */
class HtmlSanitizers extends Facade
{
    #[Override]
    protected static function getFacadeAccessor(): string
    {
        return HtmlSanitizerManager::class;
    }
}
