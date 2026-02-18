<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Middleware;

use Closure;
use CraftCms\Cms\View\TemplateMode;
use Illuminate\Http\Request;

final readonly class RequireCpRequest
{
    public function handle(Request $request, Closure $next): mixed
    {
        if (! $request->isCpRequest()) {
            abort(401, 'Request must be a control panel request');
        }

        TemplateMode::set(TemplateMode::Cp);

        return $next($request);
    }
}
