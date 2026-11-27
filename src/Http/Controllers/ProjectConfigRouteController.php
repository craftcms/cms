<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Controllers;

use CraftCms\Cms\Livewire\LivewireComponents;
use CraftCms\Cms\Route\ProjectConfigRoutes;
use CraftCms\Cms\Site\Sites;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Livewire\Livewire;
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
        private ProjectConfigRoutes $projectConfigRoutes,
        private Sites $sites,
    ) {}

    public function __invoke(Request $request): Response
    {
        [, $template] = $this->projectConfigRoutes->match($request, $this->sites->getCurrentSite()->uid) ?? abort(404);

        $parameters = $request->route()->parameters();

        if (class_exists(Livewire::class) && $name = app(LivewireComponents::class)->nameForTemplate(View::getFinder()->find($template))) {
            return app(LivewireComponents::class)->mountPage($name, $parameters);
        }

        return response()->view($template, $parameters);
    }
}
