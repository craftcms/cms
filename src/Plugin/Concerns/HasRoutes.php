<?php

namespace CraftCms\Cms\Plugin\Concerns;

use Closure;
use CraftCms\Cms\Config\GeneralConfig;
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
            ->prefix(app(GeneralConfig::class)->cpTrigger)
            ->group($routes);
    }

    protected function registerActionRoutes(string|Closure $routes): void
    {
        $this->app['router']
            ->middleware(['craft', 'craft.cp'])
            ->prefix(implode('/', [
                app(GeneralConfig::class)->cpTrigger,
                app(GeneralConfig::class)->actionTrigger,
            ]))
            ->group($routes);

        $this->app['router']
            ->middleware(['craft', 'craft.web'])
            ->prefix(app(GeneralConfig::class)->actionTrigger)
            ->group($routes);
    }
}
