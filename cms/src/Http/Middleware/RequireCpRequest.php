<?php

namespace CraftCms\Cms\Http\Middleware;

use Closure;
use CraftCms\Cms\Config\GeneralConfig;
use HttpException;
use Illuminate\Http\Request;

/**
 * @since 6.0.0
 */
final readonly class RequireCpRequest
{
    public function __construct(
        protected GeneralConfig $generalConfig,
    ) {}

    public function handle(Request $request, Closure $next): mixed
    {
        if (! $request->is($this->generalConfig->cpTrigger.'/*')) {
            throw new HttpException('Request must be a control panel request');
        }

        return $next($request);
    }
}
