<?php

declare(strict_types=1);

namespace CraftCms\Cms\Route;

use CraftCms\Cms\Cms;
use CraftCms\Cms\ProjectConfig\ProjectConfig;
use CraftCms\Cms\Route\Data\Route;
use CraftCms\Cms\Route\Events\DeletingRoute;
use CraftCms\Cms\Route\Events\RouteDeleted;
use CraftCms\Cms\Route\Events\RouteSaved;
use CraftCms\Cms\Route\Events\SavingRoute;
use CraftCms\Cms\Shared\Models\Info;
use CraftCms\Cms\Site\Events\SiteDeleted;
use CraftCms\Cms\Site\Sites;
use CraftCms\Cms\Support\Str;
use Illuminate\Container\Attributes\Singleton;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Event;

#[Singleton]
final class Routes
{
    public array $tokens {
        get => [
            'year' => '\d{4}',
            'month' => '(?:0?[1-9]|1[012])',
            'day' => '(?:0?[1-9]|[12][0-9]|3[01])',
            'number' => '\d+',
            'page' => '\d+',
            'uid' => Str::uuidPattern(),
            'slug' => '[^\/]+',
            'tag' => '[^\/]+',
            '*' => '[^\/]+',
        ];
    }

    /**
     * @var array|null all the routes in project config for current site
     */
    private ?array $projectConfigRoutes = null;

    public function __construct(
        private readonly ProjectConfig $projectConfig,
        private readonly Sites $sites,
    ) {}

    /**
     * Returns the routes defined in the project config.
     *
     * @return Collection<Route>
     */
    public function getProjectConfigRoutes(): Collection
    {
        if (isset($this->projectConfigRoutes)) {
            return collect($this->projectConfigRoutes);
        }

        if (! Info::isInstalled()) {
            return collect();
        }

        if (Cms::config()->headlessMode) {
            return collect($this->projectConfigRoutes = []);
        }

        $routes = collect($this->projectConfig->get(ProjectConfig::PATH_ROUTES) ?? [])
            ->sortBy('sortOrder', SORT_NUMERIC)
            ->filter(fn (array $route) => array_key_exists('siteUid', $route))
            ->all();

        $currentSiteUid = $this->sites->getCurrentSite()->uid;
        $this->projectConfigRoutes = [];

        foreach ($routes as $uid => $route) {
            $route = Route::from(array_merge($route, ['uid' => $uid]));

            $uri = $route->getUri();

            if (isset($this->projectConfigRoutes[$uri])) {
                continue;
            }

            if (empty($route->siteUid) || $route->siteUid === $currentSiteUid) {
                $this->projectConfigRoutes[$uri] = $route;
            }
        }

        return collect($this->projectConfigRoutes)
            ->sortBy('sortOrder')
            ->values();
    }

    /**
     * Saves a new or existing route.
     *
     * @return string The route UID.
     */
    public function saveRoute(Route $route): string
    {
        if (Event::hasListeners(SavingRoute::class)) {
            Event::dispatch(new SavingRoute($route));
        }

        if ($route->uid !== null) {
            $sortOrder = $this->projectConfig->get(
                ProjectConfig::PATH_ROUTES.'.'.$route->uid.'.sortOrder',
            ) ?? $this->getMaxSortOrder();
        } else {
            $route->uid = Str::uuid()->toString();
            $sortOrder = $this->getMaxSortOrder();
        }

        $this->projectConfig->set(
            ProjectConfig::PATH_ROUTES.'.'.$route->uid,
            array_merge($route->configData(), ['sortOrder' => $sortOrder]),
            'Save route',
        );

        if (Event::hasListeners(RouteSaved::class)) {
            Event::dispatch(new RouteSaved($route));
        }

        return $route->uid;
    }

    public function deleteRouteByUid(string $routeUid): bool
    {
        $route = $this->projectConfig->get(ProjectConfig::PATH_ROUTES.'.'.$routeUid);

        if (! $route) {
            return true;
        }

        if (Event::hasListeners(DeletingRoute::class)) {
            Event::dispatch(new DeletingRoute(new Route(
                uid: $routeUid,
                uriParts: $route['uriParts'],
                template: $route['template'],
                siteUid: $route['siteUid'],
            )));
        }

        $this->projectConfig->remove(
            ProjectConfig::PATH_ROUTES.'.'.$routeUid,
            'Delete route',
        );

        if (Event::hasListeners(RouteDeleted::class)) {
            Event::dispatch(new RouteDeleted(new Route(
                uid: $routeUid,
                uriParts: $route['uriParts'],
                template: $route['template'],
                siteUid: $route['siteUid'],
            )));
        }

        return true;
    }

    /**
     * Handle a deleted site when it affects existing routes
     */
    public function handleDeletedSite(SiteDeleted $event): void
    {
        $routes = $this->projectConfig->get(ProjectConfig::PATH_ROUTES) ?? [];

        foreach ($routes as $routeUid => $route) {
            if ($route['siteUid'] === $event->site->uid) {
                $this->projectConfig->remove(
                    ProjectConfig::PATH_ROUTES.'.'.$routeUid,
                    'Remove routes that belong to a site being deleted',
                );
            }
        }
    }

    /**
     * Updates the route order.
     *
     * @param  array  $routeUids  An array of each of the route UIDs, in their new order.
     */
    public function updateRouteOrder(array $routeUids): void
    {
        foreach ($routeUids as $order => $routeUid) {
            $this->projectConfig->set(
                ProjectConfig::PATH_ROUTES.'.'.$routeUid.'.sortOrder',
                $order + 1,
                'Reorder routes',
            );
        }
    }

    /**
     * Return the current max sort order for routes.
     */
    private function getMaxSortOrder(): int
    {
        $routes = $this->projectConfig->get(ProjectConfig::PATH_ROUTES) ?? [];
        $max = 0;

        foreach ($routes as $route) {
            $max = max($max, $route['sortOrder']);
        }

        return (int) $max + 1;
    }
}
