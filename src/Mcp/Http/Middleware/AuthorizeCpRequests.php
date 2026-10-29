<?php

declare(strict_types=1);

namespace CraftCms\Cms\Mcp\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

/**
 * Requires control panel access for signed-in users when an MCP endpoint is in the control panel.
 *
 * Guests pass through, so the endpoint can ask them to sign in.
 *
 * @since 6.0.0
 */
class AuthorizeCpRequests
{
    /** @param Closure(Request): mixed $next */
    public function handle(Request $request, Closure $next): mixed
    {
        if ($request->isCpRequest() && $request->user() !== null && ! Gate::check('accessCp')) {
            abort(403);
        }

        return $next($request);
    }
}
