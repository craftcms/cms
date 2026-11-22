<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Controllers;

use CraftCms\Cms\Route\ProjectConfigRoutes;
use CraftCms\Cms\Site\Sites;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Renders the current site’s template for a route from Settings → Routes.
 *
 * @internal
 *
 * @since 6.0.0
 */
readonly class ProjectConfigRouteController
{
    public function __construct(
        private Sites $sites,
    ) {}

    public function __invoke(Request $request): Response
    {
        $route = $request->route();
        $template = ProjectConfigRoutes::template($route, $this->sites->getCurrentSite()->uid);

        abort_if($template === null, 404);

        return response()->view($template, $route->parameters());
    }
}
