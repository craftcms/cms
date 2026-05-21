<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Controllers\Settings;

use CraftCms\Cms\Cms;
use CraftCms\Cms\Http\Requests\RouteRequest;
use CraftCms\Cms\Http\RespondsWithFlash;
use CraftCms\Cms\Http\Responses\CpScreenResponse;
use CraftCms\Cms\Route\Data\Route;
use CraftCms\Cms\Route\Routes;
use CraftCms\Cms\Site\Data\Site;
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
            ->inertiaPage('settings/routes/RoutesIndexPage', [
                'routes' => $this->routes->getProjectConfigRoutes()->values(),
            ]);
    }

    public function create(): CpScreenResponse
    {
        return $this->editResponse(new Route(uriParts: [''], template: ''), isNew: true);
    }

    public function edit(string $uid): CpScreenResponse
    {
        $route = $this->routes->getProjectConfigRoutes()->firstWhere('uid', $uid);

        abort_if(is_null($route), 404, 'Route not found');

        return $this->editResponse($route, isNew: false);
    }

    public function store(RouteRequest $request): Response
    {
        $this->routes->saveRoute($request->toRoute());

        return $this->asSuccess(t('Route saved.'));
    }

    public function update(RouteRequest $request, string $uid): Response
    {
        $this->routes->saveRoute($request->toRoute($uid));

        return $this->asSuccess(t('Route saved.'));
    }

    public function destroy(string $uid): Response
    {
        $this->routes->deleteRouteByUid($uid);

        return $this->asSuccess(t('Route deleted.'), redirect: route('craft.cp.settings.routes.index'));
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

    private function editResponse(Route $route, bool $isNew): CpScreenResponse
    {
        $title = $isNew
            ? t('Create a new route')
            : t('Edit Route');

        $response = new CpScreenResponse()
            ->title($title)
            ->crumbs([
                ['label' => t('Settings'), 'url' => Url::cpUrl('settings')],
                ['label' => t('Routes'), 'url' => Url::cpUrl('settings/routes')],
                ['label' => $title],
            ])
            ->redirectUrl('settings/routes');

        if (! $isNew && Cms::config()->allowAdminChanges) {
            $response->actionMenuItems(fn () => [[
                'label' => t('Delete'),
                'icon' => 'trash',
                'destructive' => true,
                'attributes' => [
                    'type' => 'button',
                    'data' => [
                        'route-delete-action' => true,
                        'route-delete-url' => Url::cpUrl("settings/routes/{$route->uid}"),
                    ],
                ],
            ]]);
        }

        return $response->inertiaPage('settings/routes/EditRoutePage', [
            'route' => $route,
            'tokens' => $this->tokenProps(),
            'sites' => $this->siteProps(),
        ]);
    }

    private function tokenProps(): array
    {
        return collect($this->routes->tokens)
            ->map(fn (string $value, string $label): array => [
                'label' => $label,
                'value' => $value,
            ])
            ->values()
            ->all();
    }

    private function siteProps(): array
    {
        return collect([[
            'value' => '',
            'label' => t('Global'),
        ]])
            ->merge($this->sites->getAllSites()->map(fn (Site $site): array => [
                'value' => $site->uid,
                'label' => t($site->getName(), category: 'site'),
            ]))
            ->values()
            ->all();
    }
}
