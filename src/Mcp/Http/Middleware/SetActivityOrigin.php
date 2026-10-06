<?php

declare(strict_types=1);

namespace CraftCms\Cms\Mcp\Http\Middleware;

use Closure;
use CraftCms\Cms\Activity\ActivityEventRecorder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Context;

/** @since 6.0.0 */
class SetActivityOrigin
{
    /** @param Closure(Request): mixed $next */
    public function handle(Request $request, Closure $next): mixed
    {
        return Context::scope(
            fn (): mixed => $next($request),
            hidden: [ActivityEventRecorder::ContextOrigin => 'MCP'],
        );
    }
}
