<?php

declare(strict_types=1);

namespace CraftCms\Cms\Route;

use CraftCms\Cms\Route\Data\Route;
use Illuminate\Container\Attributes\Singleton;
use Illuminate\Routing\Route as LaravelRoute;
use Illuminate\Routing\RouteCollection;
use Illuminate\Routing\Router;
use Illuminate\Support\Collection;

/**
 * Registers the routes defined in the project config with Laravel’s router.
 *
 * @internal
 *
 * @since 6.0.0
 */
#[Singleton]
class ProjectConfigRoutes
{
    /** @var list<LaravelRoute> */
    private array $registered = [];

    /** A hash of the registered routes, or null if none have been registered. */
    private ?string $signature = null;

    public function __construct(
        private readonly Router $router,
    ) {}

    public function register(): void
    {
        $routes = $this->routes();

        $this->signature = $this->signature($routes);
        $this->registered = $routes
            ->map(fn (Route $route) => $this->router
                ->view($route->getUri(), $route->template)
                ->middleware(['craft', 'craft.web'])
                ->withLazySession())
            ->values()
            ->all();
    }

    /**
     * Registers the routes again if the project config’s routes have changed since they were registered.
     *
     * A long-lived worker, such as under Laravel Octane, keeps its router between requests, while the routes can be
     * changed by another worker or a command.
     */
    public function refresh(): void
    {
        if ($this->signature === null || $this->signature($this->routes()) === $this->signature) {
            return;
        }

        $collection = new RouteCollection;

        foreach ($this->router->getRoutes()->getRoutes() as $route) {
            if (! in_array($route, $this->registered, true)) {
                $collection->add($route);
            }
        }

        $this->router->setRoutes($collection);
        $this->register();
    }

    /**
     * @return Collection<string, Route>
     */
    private function routes(): Collection
    {
        return app(Routes::class)->getProjectConfigRoutes();
    }

    /**
     * @param  Collection<string, Route>  $routes
     */
    private function signature(Collection $routes): string
    {
        return hash('xxh128', serialize($routes->map(fn (Route $route) => [$route->getUri(), $route->template])->all()));
    }
}
