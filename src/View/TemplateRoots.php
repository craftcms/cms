<?php

declare(strict_types=1);

namespace CraftCms\Cms\View;

use Illuminate\Container\Attributes\Singleton;

/**
 * Registers namespaced template roots for control panel or site templates.
 *
 * ```php
 * public function boot(TemplateRoots $templateRoots): void
 * {
 *     $templateRoots->register(
 *         TemplateMode::Cp,
 *         'my-plugin',
 *         __DIR__.'/templates',
 *     );
 * }
 * ```
 */
#[Singleton]
class TemplateRoots
{
    /** @var array<string, array<string, array<string, string>>> */
    private array $roots = [];

    public function register(TemplateMode $mode, string $namespace, string ...$paths): void
    {
        $namespace = strtolower(trim($namespace, '/'));

        foreach ($paths as $path) {
            $this->roots[$mode->value][$namespace][$path] = $path;
        }
    }

    public function remove(TemplateMode $mode, string ...$namespaces): void
    {
        foreach ($namespaces as $namespace) {
            $namespace = strtolower(trim($namespace, '/'));
            unset($this->roots[$mode->value][$namespace]);
        }
    }

    /** @return array<string, list<string>> */
    public function roots(TemplateMode $mode): array
    {
        $roots = array_map(array_values(...), $this->roots[$mode->value] ?? []);
        krsort($roots, SORT_STRING);

        return $roots;
    }
}
