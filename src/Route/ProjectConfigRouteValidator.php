<?php

declare(strict_types=1);

namespace CraftCms\Cms\Route;

use CraftCms\Cms\Http\Middleware\ResolveSite;
use Illuminate\Http\Request;
use Illuminate\Routing\Matching\ValidatorInterface;
use Illuminate\Routing\Route;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

/**
 * Matches routes from Settings → Routes only on the sites they’re for, so other sites get Craft’s fallback route.
 *
 * @internal
 *
 * @since 6.0.0
 */
class ProjectConfigRouteValidator implements ValidatorInterface
{
    public function matches(Route $route, Request $request): bool
    {
        if ($route->getAction(ProjectConfigRoutes::TEMPLATES_ACTION) === null) {
            return true;
        }

        try {
            $site = app(ResolveSite::class)->requestedSite($request);
        } catch (HttpExceptionInterface) {
            return false;
        }

        return $site !== null && ProjectConfigRoutes::template($route, $site->uid) !== null;
    }
}
