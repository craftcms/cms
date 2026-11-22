<?php

declare(strict_types=1);

namespace CraftCms\Cms\Route;

use CraftCms\Cms\Http\Controllers\ProjectConfigRouteController;
use CraftCms\Cms\Http\Middleware\ResolveSite;
use Illuminate\Http\Request;
use Illuminate\Routing\Matching\ValidatorInterface;
use Illuminate\Routing\Route;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

/**
 * Matches a route from Settings → Routes only if it’s the requested site’s first route for the request, so other
 * sites get Craft’s fallback route and each site keeps its routes’ sort order.
 *
 * @internal
 *
 * @since 6.0.0
 */
class ProjectConfigRouteValidator implements ValidatorInterface
{
    public function matches(Route $route, Request $request): bool
    {
        if ($route->getControllerClass() !== ProjectConfigRouteController::class) {
            return true;
        }

        try {
            $site = app(ResolveSite::class)->requestedSite($request);
        } catch (HttpExceptionInterface) {
            return false;
        }

        if ($site === null) {
            return false;
        }

        return (app(ProjectConfigRoutes::class)->match($request, $site->uid)[0] ?? null) === $route;
    }
}
