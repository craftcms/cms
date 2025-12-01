<?php

declare(strict_types=1);

namespace CraftCms\Cms\Plugin\Concerns;

use Closure;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Plugin\Plugin;

/**
 * @mixin Plugin
 *
 * @internal
 */
trait HasRoutes
{
    public function bootHasRoutes(): void
    {
        $directory = dirname(self::getInstance()->getBasePath());

        foreach (['web', 'cp', 'actions'] as $type) {
            if (! $this->app['files']->exists($path = "$directory/routes/$type.php")) {
                continue;
            }

            match ($type) {
                'web' => $this->registerWebRoutes($path),
                'cp' => $this->registerCpRoutes($path),
                'actions' => $this->registerActionRoutes($path),
            };
        }
    }

    protected function registerWebRoutes(string|Closure $routes): void
    {
        $this->app['router']
            ->middleware(['craft', 'craft.web'])
            ->group($routes);
    }

    protected function registerCpRoutes(string|Closure $routes): void
    {
        $this->app['router']
            ->middleware(['craft', 'craft.cp'])
            ->prefix(Cms::config()->cpTrigger)
            ->group($routes);
    }

    protected function registerActionRoutes(string|Closure $routes): void
    {
        $this->app['router']
            ->middleware(['craft', 'craft.cp'])
            ->prefix(implode('/', [
                Cms::config()->cpTrigger,
                Cms::config()->actionTrigger,
            ]))
            ->group($routes);

        $this->app['router']
            ->middleware(['craft', 'craft.web'])
            ->prefix(Cms::config()->actionTrigger)
            ->group($routes);
    }
}
