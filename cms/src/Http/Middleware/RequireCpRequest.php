<?php

namespace CraftCms\Cms\Http\Middleware;

use Closure;
use craft\web\Application;
use Illuminate\Container\Attributes\Give;
use Illuminate\Http\Request;

class RequireCpRequest
{
    public function __construct(
        #[Give('Craft')] protected Application $craft,
    ) {}

    public function handle(Request $request, Closure $next): mixed
    {
        if (! $request->is($this->craft->getConfig()->getGeneral()->cpTrigger.'/*')) {
            throw new \HttpException('Request must be a control panel request');
        }

        return $next($request);
    }
}
