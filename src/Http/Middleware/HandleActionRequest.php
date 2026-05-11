<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

readonly class HandleActionRequest
{
    public function handle(Request $request, Closure $next): mixed
    {
        if (! $request->isActionRequest()) {
            return $next($request);
        }

        $route = $request->actionSegmentsToRoute();

        if ($request->path() === $route) {
            return $next($request);
        }

        $newRequest = $request->duplicateWithUri($route);

        app()->instance('request', $newRequest);

        return $next($newRequest);
    }
}
