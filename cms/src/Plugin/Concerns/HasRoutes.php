<?php

namespace CraftCms\Cms\Plugin\Concerns;

use Closure;
use CraftCms\Cms\Plugin\Plugin;

/**
 * @mixin Plugin
 *
 * @internal
 *
 * @since 6.0.0
 */
trait HasRoutes
{
    public function bootHasRoutes(): void
    {
        $directory = dirname(self::getInstance()->getBasePath());

        foreach (['web', 'cp'] as $type) {
            if (! $this->app['files']->exists($path = "$directory/routes/$type.php")) {
                continue;
            }

            match ($type) {
                'web' => $this->registerWebRoutes($path),
                'cp' => $this->registerCpRoutes($path),
            };
        }
    }

    protected function registerWebRoutes(string|Closure $routes): void
    {
        $this->app['router']->middleware(['craft', 'craft.web'])->group($routes);
    }

    protected function registerCpRoutes(string|Closure $routes): void
    {
        $this->app['router']->middleware(['craft', 'craft.cp'])->group($routes);
    }
}
