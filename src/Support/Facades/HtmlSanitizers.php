<?php

declare(strict_types=1);

namespace CraftCms\Cms\Support\Facades;

use Closure;
use Illuminate\Support\Facades\Facade;
use Override;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerInterface;

/**
 * @method static void register(string $name, Closure|HtmlSanitizerInterface $definition)
 * @method static void defaults(Closure $callback)
 * @method static bool has(string $name)
 * @method static string sanitize(string $html, HtmlSanitizerInterface|string|null $sanitizer = null)
 * @method static HtmlSanitizerInterface sanitizer(string|null $name = null)
 * @method static \Symfony\Component\HtmlSanitizer\HtmlSanitizerConfig defaultConfig()
 *
 * @see \CraftCms\Cms\Support\HtmlSanitizer\HtmlSanitizers
 */
class HtmlSanitizers extends Facade
{
    #[Override]
    protected static function getFacadeAccessor(): string
    {
        return \CraftCms\Cms\Support\HtmlSanitizer\HtmlSanitizers::class;
    }
}
