<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Middleware;

use Closure;
use Illuminate\Foundation\Http\Middleware\PreventRequestsDuringMaintenance as LaravelMiddleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Override;

class PreventRequestsDuringMaintenance extends LaravelMiddleware
{
    /**
     * @param  Request  $request
     */
    #[Override]
    public function handle($request, Closure $next): mixed
    {
        if ($request->isCpRequest()) {
            return $next($request);
        }

        if (
            $request->getHadToken() ||
            $request->siteToken() !== null ||
            Gate::check('accessSiteWhenSystemIsOff')
        ) {
            return $next($request);
        }

        return parent::handle($request, $next);
    }
}
