<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Middleware;

use Closure;
use CraftCms\Cms\Config\GeneralConfig;
use Illuminate\Http\Request;

final readonly class RequireCpRequest
{
    public function __construct(
        protected GeneralConfig $generalConfig,
    ) {}

    public function handle(Request $request, Closure $next): mixed
    {
        if (! $request->isCpRequest()) {
            abort(401, 'Request must be a control panel request');
        }

        return $next($request);
    }
}
