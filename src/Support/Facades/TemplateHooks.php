<?php

declare(strict_types=1);

namespace CraftCms\Cms\Support\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static void register(string $hook, callable|string $handler, bool $append = true)
 * @method static string invoke(string $hook, array $context)
 *
 * @see \CraftCms\Cms\View\TemplateHooks
 */
final class TemplateHooks extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \CraftCms\Cms\View\TemplateHooks::class;
    }
}
