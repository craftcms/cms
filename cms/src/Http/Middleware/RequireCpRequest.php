<?php

namespace Craft\Cms\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class RequireCpRequest
{
    public function handle(Request $request, Closure $next): mixed
    {
        if (! $request->is(config('craft.general.cpTrigger').'/*')) {
            throw new \HttpException('Request must be a control panel request');
        }

        return $next($request);
    }
}
