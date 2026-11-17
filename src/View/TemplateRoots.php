<?php

declare(strict_types=1);

namespace CraftCms\Cms\View;

use Illuminate\Container\Attributes\Singleton;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\View\Factory;
use Illuminate\View\ViewFinderInterface;

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
 *
 * @since 6.0.0
 */
#[Singleton]
class TemplateRoots
{
    /** @var array<string, array<string, array<string, string>>> */
    private array $roots = [];

    /** The view finder as it was before any template roots were added to it. */
    private ?ViewFinderInterface $bootFinder = null;

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

    /**
     * Adds a template mode's roots to the view finder, as namespaces and as locations.
     *
     * @internal
     */
    public function addToViewFactory(Factory $factory, TemplateMode $mode): void
    {
        $this->bootFinder ??= clone $factory->getFinder();

        foreach ($this->roots($mode) as $namespace => $roots) {
            $factory->addNamespace($namespace, $roots);

            foreach ($roots as $root) {
                $factory->prependLocation($root);
            }
        }
    }

    /**
     * Restores the view finder as it was booted, with a template mode's roots.
     *
     * A long-lived worker, such as under Laravel Octane, boots once without a request, and keeps the view factory
     * between requests, while each request has its own template mode, and control panel requests add Craft's
     * templates to the finder.
     *
     * @internal
     */
    public function resetViewFactory(Application $app, TemplateMode $mode): void
    {
        /** @var Factory $factory */
        $factory = $app->make(\Illuminate\Contracts\View\Factory::class);
        $this->bootFinder ??= clone $factory->getFinder();
        $finder = clone $this->bootFinder;
        $factory->setFinder($finder);
        $app->instance('view.finder', $finder);

        $this->addToViewFactory($factory, $mode);
    }
}
