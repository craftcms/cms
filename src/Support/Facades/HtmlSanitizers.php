<?php

declare(strict_types=1);

namespace CraftCms\Cms\Support\Facades;

use Illuminate\Support\Facades\Facade;
use Override;

/**
 * @method static void register(string $name, \Closure|\Symfony\Component\HtmlSanitizer\HtmlSanitizerInterface $definition)
 * @method static void defaults(\Closure $callback)
 * @method static bool has(string $name)
 * @method static \Illuminate\Support\Collection<string, \Symfony\Component\HtmlSanitizer\HtmlSanitizerInterface> all()
 * @method static string sanitize(string $html, \Symfony\Component\HtmlSanitizer\HtmlSanitizerInterface|string|null $sanitizer = null)
 * @method static \Symfony\Component\HtmlSanitizer\HtmlSanitizerInterface sanitizer(string|null $name = null)
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
