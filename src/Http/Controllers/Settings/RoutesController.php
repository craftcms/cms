<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Controllers\Settings;

use craft\web\assets\routes\RoutesAsset;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Http\RespondsWithFlash;
use CraftCms\Cms\Route\Data\Route;
use CraftCms\Cms\Route\Routes;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final readonly class RoutesController
{
    use RespondsWithFlash;

    public function __construct(
        private Routes $routes,
    ) {}

    public function index(): View
    {
        \Craft::$app->getView()->registerAssetBundle(RoutesAsset::class);

        return view('craftcms::settings.routes', [
            'tokens' => $this->routes->tokens,
            'routes' => $this->routes->getProjectConfigRoutes(),
            'readOnly' => ! Cms::config()->allowAdminChanges,
        ]);
    }

    public function store(Route $route): Response
    {
        $routeUid = $this->routes->saveRoute($route);

        return $this->asSuccess(data: [
            'routeUid' => $routeUid,
            'siteUid' => $route->siteUid,
        ]);
    }

    public function destroy(Request $request): Response
    {
        $routeUid = $request->validate([
            'routeUid' => ['required', 'string'],
        ])['routeUid'];

        $this->routes->deleteRouteByUid($routeUid);

        return $this->asSuccess();
    }

    public function reorder(Request $request): Response
    {
        $routeUids = $request->validate([
            'routeUids' => ['required', 'array'],
            'routeUids.*' => ['required', 'string'],
        ])['routeUids'];

        $this->routes->updateRouteOrder($routeUids);

        return $this->asSuccess();
    }
}
