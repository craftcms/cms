<?php

declare(strict_types=1);

namespace CraftCms\Cms\Route;

use CraftCms\Cms\Cms;
use CraftCms\Cms\ProjectConfig\ProjectConfig;
use CraftCms\Cms\Route\Data\Route;
use CraftCms\Cms\Route\Events\RouteDeleted;
use CraftCms\Cms\Route\Events\RouteDeleting;
use CraftCms\Cms\Route\Events\RouteSaved;
use CraftCms\Cms\Route\Events\RouteSaving;
use CraftCms\Cms\Site\Data\Site;
use CraftCms\Cms\Site\Events\SiteDeleted;
use CraftCms\Cms\Site\Exceptions\SiteNotFoundException;
use CraftCms\Cms\Site\Sites;
use CraftCms\Cms\Support\Str;
use Illuminate\Container\Attributes\Scoped;
use Illuminate\Support\Collection;

#[Scoped]
class Routes
{
    /** @var array<string, string> */
    public array $tokens {
        get => [
            'year' => '\d{4}',
            'month' => '(?:0?[1-9]|1[012])',
            'day' => '(?:0?[1-9]|[12][0-9]|3[01])',
            'number' => '\d+',
            'page' => '\d+',
            'uid' => Str::uuidPattern(),
            'handle' => '[^\/]+',
            'slug' => '[^\/]+',
            'tag' => '[^\/]+',
            'cpTrigger' => preg_quote(trim((string) Cms::config()->cpTrigger, '/'), '#') ?: '(?!)',
            'actionTrigger' => preg_quote(trim(Cms::config()->actionTrigger, '/'), '#') ?: '(?!)',
            '*' => '[^\/]+',
        ];
    }

    public function cpTriggerRoutePrefix(): string
    {
        return trim((string) Cms::config()->cpTrigger, '/') === '' ? '' : '{cpTrigger}';
    }

    public function actionTriggerRoutePrefix(): string
    {
        return trim(Cms::config()->actionTrigger, '/') === '' ? '' : '{actionTrigger}';
    }

    public function cpActionTriggerRoutePrefix(): string
    {
        return $this->joinRoutePrefix([
            $this->cpTriggerRoutePrefix(),
            $this->actionTriggerRoutePrefix(),
        ]);
    }

    public function actionTriggerUriPrefix(): string
    {
        return trim(Cms::config()->actionTrigger, '/');
    }

    public function cpActionTriggerUriPrefix(): string
    {
        return $this->joinRoutePrefix([
            trim((string) Cms::config()->cpTrigger, '/'),
            $this->actionTriggerUriPrefix(),
        ]);
    }

    /**
     * @param  list<string|null>  $segments
     */
    public function joinRoutePrefix(array $segments): string
    {
        return implode('/', array_filter(
            $segments,
            fn (?string $segment) => $segment !== null && $segment !== '',
        ));
    }

    /**
     * @var array<string, Route>|null all the routes in project config for current site
     */
    private ?array $projectConfigRoutes = null;

    public function __construct(
        private readonly ProjectConfig $projectConfig,
        private readonly Sites $sites,
    ) {}

    /**
     * Returns the unique localized values of a GeneralConfig path setting across all sites,
     * for settings that can be defined per site (a site handle keyed array or callable).
     *
     * @return Collection<int, non-empty-string>
     */
    public function localizedConfigPaths(string $getter): Collection
    {
        return $this->sites->getAllSites()
            ->map(fn (Site $site) => Cms::config()->$getter($site->handle))
            ->filter(fn (mixed $path) => is_string($path) && $path !== '')
            ->unique()
            ->values();
    }

    /**
     * Returns the routes defined in the project config.
     *
     * @return Collection<array-key, Route>
     */
    public function getProjectConfigRoutes(): Collection
    {
        if (isset($this->projectConfigRoutes)) {
            return collect($this->projectConfigRoutes);
        }

        if (Cms::config()->headlessMode) {
            return collect($this->projectConfigRoutes = []);
        }

        $configRoutes = $this->projectConfig->get(ProjectConfig::PATH_ROUTES);

        if (! is_array($configRoutes)) {
            $configRoutes = [];
        }

        $routes = collect($configRoutes)
            ->sortBy('sortOrder', SORT_NUMERIC)
            ->filter(fn (array $route) => array_key_exists('siteUid', $route))
            ->all();

        try {
            $currentSiteUid = $this->sites->getCurrentSite()->uid;
        } catch (SiteNotFoundException) {
            return collect($this->projectConfigRoutes = []);
        }

        $this->projectConfigRoutes = [];

        foreach ($routes as $uid => $route) {
            $route = new Route(
                uriParts: $route['uriParts'] ?? [],
                template: $route['template'],
                siteUid: $route['siteUid'] ?? null,
                uid: $uid,
                sortOrder: isset($route['sortOrder']) ? (int) $route['sortOrder'] : null,
            );

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
        event(new RouteSaving($route));

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

        event(new RouteSaved($route));

        $this->projectConfigRoutes = null;

        return $route->uid;
    }

    public function deleteRouteByUid(string $routeUid): bool
    {
        $route = $this->projectConfig->get(ProjectConfig::PATH_ROUTES.'.'.$routeUid);

        if (! $route) {
            return true;
        }

        event(new RouteDeleting(new Route(
            uriParts: $route['uriParts'] ?? [],
            template: $route['template'],
            siteUid: $route['siteUid'],
            uid: $routeUid,
        )));

        $this->projectConfig->remove(
            ProjectConfig::PATH_ROUTES.'.'.$routeUid,
            'Delete route',
        );

        event(new RouteDeleted(new Route(
            uriParts: $route['uriParts'] ?? [],
            template: $route['template'],
            siteUid: $route['siteUid'],
            uid: $routeUid,
        )));

        $this->projectConfigRoutes = null;

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

        $this->projectConfigRoutes = null;
    }

    /**
     * Updates the route order.
     *
     * @param  list<string>  $routeUids  An array of each of the route UIDs, in their new order.
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

        $this->projectConfigRoutes = null;
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
