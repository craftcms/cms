<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Middleware;

use Closure;
use CraftCms\Cms\Support\Facades\TemplateHooks;
use CraftCms\Cms\View\Hooks\PrepareElementIndexVariables;
use CraftCms\Cms\View\Hooks\PrepareElementSourcesVariables;
use CraftCms\Cms\View\Hooks\PrepareElementToolbarVariables;
use CraftCms\Cms\View\TemplateMode;
use Illuminate\Http\Request;

readonly class RequireCpRequest
{
    public function handle(Request $request, Closure $next): mixed
    {
        if (! $request->isCpRequest()) {
            abort(401, 'Request must be a control panel request');
        }

        TemplateMode::set(TemplateMode::Cp);

        $this->registerCpTemplateHooks();

        return $next($request);
    }

    public function registerCpTemplateHooks(): void
    {
        TemplateHooks::register('cp.layouts.elementindex', PrepareElementIndexVariables::class);
        TemplateHooks::register('cp.elements.toolbar', PrepareElementToolbarVariables::class);
        TemplateHooks::register('cp.elements.sources', PrepareElementSourcesVariables::class);
    }
}
