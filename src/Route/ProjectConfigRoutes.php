<?php

declare(strict_types=1);

namespace CraftCms\Cms\Route;

use CraftCms\Cms\Http\Controllers\ProjectConfigRouteController;
use CraftCms\Cms\Route\Data\Route;
use Illuminate\Container\Attributes\Singleton;
use Illuminate\Http\Request;
use Illuminate\Routing\Matching\UriValidator;
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

    /** @var list<array{?string, string, LaravelRoute}> Each route’s site UID, template, and Laravel route, in sort order */
    private array $siteRoutes = [];

    /** A hash of the registered routes, or null if none have been registered. */
    private ?string $signature = null;

    public function __construct(
        private readonly Router $router,
    ) {}

    /**
     * Registers each URI once for all sites. Routes are registered when the app boots, before the requested
     * site is known, so {@see ProjectConfigRouteValidator} only matches them on the sites they’re for.
     */
    public function register(): void
    {
        if (! array_any(LaravelRoute::getValidators(), fn (object $validator) => $validator instanceof ProjectConfigRouteValidator)) {
            LaravelRoute::$validators[] = new ProjectConfigRouteValidator;
        }

        $routes = $this->routes();
        $registered = [];

        $this->signature = $this->signature($routes);
        $this->siteRoutes = $routes
            ->map(function (Route $route) use (&$registered): array {
                $uri = $route->getUri();
                $registered[$uri] ??= $this->router
                    ->get($uri, ProjectConfigRouteController::class)
                    ->middleware(['craft', 'craft.web'])
                    ->withLazySession();

                return [$route->siteUid, $route->template, $registered[$uri]];
            })
            ->all();
        $this->registered = array_values($registered);
    }

    /**
     * Returns the first route for a site that matches the request, in the routes’ sort order, as its Laravel route
     * and template.
     *
     * @return array{LaravelRoute, string}|null
     */
    public function match(Request $request, string $siteUid): ?array
    {
        $uriValidator = new UriValidator;

        foreach ($this->siteRoutes as [$routeSiteUid, $template, $route]) {
            if ((empty($routeSiteUid) || $routeSiteUid === $siteUid) && $uriValidator->matches($route, $request)) {
                return [$route, $template];
            }
        }

        return null;
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
     * @return Collection<int, Route>
     */
    private function routes(): Collection
    {
        return app(Routes::class)->getAllProjectConfigRoutes();
    }

    /**
     * @param  Collection<int, Route>  $routes
     */
    private function signature(Collection $routes): string
    {
        return hash('xxh128', serialize($routes->map(fn (Route $route) => [$route->getUri(), $route->template, $route->siteUid])->all()));
    }
}
