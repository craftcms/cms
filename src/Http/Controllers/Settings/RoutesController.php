<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Controllers\Settings;

use CraftCms\Cms\Cms;
use CraftCms\Cms\Cp\SelectOptions;
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
                ['label' => t('Settings'), 'href' => Url::cpUrl('settings')],
                ['label' => t('Routes')],
            ])
            ->inertiaPage('settings/routes/Index', [
                'readOnly' => ! Cms::config()->allowAdminChanges,
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

        return new CpScreenResponse()
            ->title($title)
            ->crumbs([
                ['label' => t('Settings'), 'href' => Url::cpUrl('settings')],
                ['label' => t('Routes'), 'href' => Url::cpUrl('settings/routes')],
                ['label' => $title],
            ])
            ->redirectUrl('settings/routes')
            ->inertiaPage('settings/routes/Edit', [
                'route' => $route,
                'readOnly' => ! Cms::config()->allowAdminChanges,
                'tokens' => $this->tokenProps(),
                'sites' => $this->siteProps(),
                'templateOptions' => SelectOptions::getTemplateSuggestions(),
            ]);
    }

    /** @return list<array{label:string, value:string}> */
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

    /** @return list<array{label:string, value:string}> */
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
