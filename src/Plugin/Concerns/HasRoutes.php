<?php

declare(strict_types=1);

namespace CraftCms\Cms\Plugin\Concerns;

use Closure;
use CraftCms\Cms\Plugin\Plugin;
use CraftCms\Cms\Route\Routes;

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
        $craftRoutes = $this->app->get(Routes::class);

        $this->app['router']
            ->middleware(['web', 'craft', 'craft.cp'])
            ->prefix($craftRoutes->cpTriggerRoutePrefix())
            ->group($routes);
    }

    protected function registerActionRoutes(string|Closure $routes): void
    {
        $handle = self::getInstance()->handle;
        $craftRoutes = $this->app->get(Routes::class);
        $cpActionPrefix = $craftRoutes->joinRoutePrefix([
            $craftRoutes->cpActionTriggerRoutePrefix(),
            $handle,
        ]);
        $siteActionPrefix = $craftRoutes->joinRoutePrefix([
            $craftRoutes->actionTriggerRoutePrefix(),
            $handle,
        ]);

        $this->app['router']
            ->middleware(['web', 'craft', 'craft.cp'])
            ->prefix($cpActionPrefix)
            ->group($routes);

        if ($siteActionPrefix === $cpActionPrefix) {
            return;
        }

        $this->app['router']
            ->middleware(['craft', 'craft.web'])
            ->prefix($siteActionPrefix)
            ->group($routes);
    }
}
