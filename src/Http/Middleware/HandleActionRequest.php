<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Middleware;

use Closure;
use CraftCms\Cms\Http\Routing\ActionRoute;
use CraftCms\Cms\Http\Routing\ActionRouteResolver;
use Illuminate\Http\Request;

readonly class HandleActionRequest
{
    public function __construct(
        private ActionRouteResolver $actionRoutes,
    ) {}

    public function handle(Request $request, Closure $next): mixed
    {
        $actionRoute = $this->actionRoutes->resolve($request);

        if ($actionRoute === null) {
            return $next($request);
        }

        if ($actionRoute->matches($request)) {
            return $next($request);
        }

        $newRequest = $request->duplicateWithUri($actionRoute->uri);
        $newRequest->attributes->set(ActionRoute::class, $actionRoute);

        app()->instance('request', $newRequest);

        return $next($newRequest);
    }
}
