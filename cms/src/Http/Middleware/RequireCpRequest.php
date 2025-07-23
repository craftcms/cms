<?php

namespace CraftCms\Cms\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class RequireCpRequest
{
    public function handle(Request $request, Closure $next): mixed
    {
        /** @var \craft\web\Application $craft */
        $craft = app('Craft');

        if (! $request->is($craft->getConfig()->getGeneral()->cpTrigger.'/*')) {
            throw new \HttpException('Request must be a control panel request');
        }

        return $next($request);
    }
}
