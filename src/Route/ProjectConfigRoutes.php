<?php

declare(strict_types=1);

namespace CraftCms\Cms\Route;

use CraftCms\Cms\Http\Controllers\ProjectConfigRouteController;
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
    /** The route action key with the `[siteUid, template]` pairs for a URI, in sort order. */
    public const string TEMPLATES_ACTION = 'craftTemplates';

    /** @var list<LaravelRoute> */
    private array $registered = [];

    /** A hash of the registered routes, or null if none have been registered. */
    private ?string $signature = null;

    public function __construct(
        private readonly Router $router,
    ) {}

    /**
     * Returns the template a route from {@see register()} renders on a site, or `null` if it isn’t for the site.
     */
    public static function template(LaravelRoute $route, string $siteUid): ?string
    {
        foreach ($route->getAction(self::TEMPLATES_ACTION) ?? [] as [$routeSiteUid, $template]) {
            if (empty($routeSiteUid) || $routeSiteUid === $siteUid) {
                return $template;
            }
        }

        return null;
    }

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

        $this->signature = $this->signature($routes);
        $this->registered = $routes
            ->groupBy(fn (Route $route) => $route->getUri())
            ->map(fn (Collection $uriRoutes, string $uri) => $this->router
                ->get($uri, [
                    'uses' => ProjectConfigRouteController::class,
                    self::TEMPLATES_ACTION => $uriRoutes->map(fn (Route $route) => [$route->siteUid, $route->template])->all(),
                ])
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
