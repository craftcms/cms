<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

final readonly class RequireCpRequest
{
    public function handle(Request $request, Closure $next): mixed
    {
        abort_unless($request->isCpRequest(), 401, 'Request must be a control panel request');

        return $next($request);
    }
}
