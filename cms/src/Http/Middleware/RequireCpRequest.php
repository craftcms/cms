<?php

namespace CraftCms\Cms\Http\Middleware;

use Closure;
use CraftCms\Cms\Config\GeneralConfig;
use Illuminate\Http\Request;

class RequireCpRequest
{
    public function __construct(
        protected GeneralConfig $generalConfig,
    ) {}

    public function handle(Request $request, Closure $next): mixed
    {
        if (! $request->is($this->generalConfig->cpTrigger.'/*')) {
            throw new \HttpException('Request must be a control panel request');
        }

        return $next($request);
    }
}
