<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/**
 * This middleware removes the cpTrigger and actionTrigger parameters
 * from the route. Otherwise, these parameters will be added
 * as the first parameter to every controller action.
 */
class ForgetTriggerParameters
{
    public function handle(Request $request, Closure $next): mixed
    {
        $request->route()?->forgetParameter('cpTrigger');
        $request->route()?->forgetParameter('actionTrigger');

        return $next($request);
    }
}
