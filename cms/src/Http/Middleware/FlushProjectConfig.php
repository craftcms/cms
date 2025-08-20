<?php

namespace CraftCms\Cms\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

final class FlushProjectConfig
{
    public function handle(Request $request, Closure $next): mixed
    {
        $response = $next($request);

        app('Craft')->getProjectConfig()->flush();

        return $response;
    }
}
