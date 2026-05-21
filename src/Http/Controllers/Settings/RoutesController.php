<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Controllers\Settings;

use CraftCms\Cms\Cms;
use CraftCms\Cms\Http\Requests\RouteRequest;
use CraftCms\Cms\Http\RespondsWithFlash;
use CraftCms\Cms\Http\Responses\CpScreenResponse;
use CraftCms\Cms\Route\Data\Route;
use CraftCms\Cms\Route\Routes;
use CraftCms\Cms\Site\Sites;
use CraftCms\Cms\Support\Url;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

use function CraftCms\Cms\t;

readonly class RoutesController
{
    use RespondsWithFlash;

    public function __construct(
        private Routes $routes,
        private Sites $sites,
    ) {}

    public function index(): CpScreenResponse
    {
        return new CpScreenResponse()
            ->title(t('Routes'))
            ->crumbs([
                ['label' => t('Settings'), 'url' => Url::cpUrl('settings')],
                ['label' => t('Routes')],
            ])
            ->inertiaPage('settings/routes/RoutesPage', [
                'tokens' => $this->routes->tokens,
                'routes' => $this->routeProps(),
                'sites' => $this->siteProps(),
                'isMultiSite' => $this->sites->isMultiSite(),
                'readOnly' => ! Cms::config()->allowAdminChanges,
            ]);
    }

    public function store(RouteRequest $request): Response
    {
        $route = $request->toRoute();
        $routeUid = $this->routes->saveRoute($route);

        return $this->asSuccess(t('Route saved.'), [
            'routeUid' => $routeUid,
            'siteUid' => $route->siteUid,
        ]);
    }

    public function update(RouteRequest $request, string $routeUid): Response
    {
        $route = $request->toRoute($routeUid);
        $routeUid = $this->routes->saveRoute($route);

        return $this->asSuccess(t('Route saved.'), [
            'routeUid' => $routeUid,
            'siteUid' => $route->siteUid,
        ]);
    }

    public function destroy(string $routeUid): Response
    {
        $this->routes->deleteRouteByUid($routeUid);

        return $this->asSuccess(t('Route deleted.'));
    }

    public function reorder(Request $request): Response
    {
        $routeUids = $request->validate([
            'routeUids' => ['required', 'array'],
            'routeUids.*' => ['required', 'string'],
        ])['routeUids'];

        $this->routes->updateRouteOrder($routeUids);

        return $this->asSuccess(t('New route order saved.'));
    }

    private function routeProps(): array
    {
        $sitesByUid = $this->sites->getAllSites()->keyBy('uid');

        return $this->routes->getProjectConfigRoutes()
            ->map(fn (Route $route): array => [
                'uid' => $route->uid,
                'siteUid' => $route->siteUid,
                'siteName' => $route->siteUid
                    ? t($sitesByUid->get($route->siteUid)?->getName() ?? $route->siteUid, category: 'site')
                    : t('Global'),
                'uriParts' => array_values($route->uriParts),
                'template' => $route->template,
                'sortOrder' => $route->sortOrder,
            ])
            ->values()
            ->all();
    }

    private function siteProps(): array
    {
        return $this->sites->getAllSites()
            ->map(fn ($site): array => [
                'uid' => $site->uid,
                'name' => t($site->getName(), category: 'site'),
            ])
            ->values()
            ->all();
    }
}
