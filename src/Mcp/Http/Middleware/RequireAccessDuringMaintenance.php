<?php

declare(strict_types=1);

namespace CraftCms\Cms\Mcp\Http\Middleware;

use Closure;
use Illuminate\Contracts\Foundation\MaintenanceMode;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

/**
 * Lets MCP endpoints through maintenance mode for users who can access the site or control panel while the system is
 * off, as the site and control panel themselves do.
 *
 * The endpoint skips the usual maintenance check, which only sees the session user, not the access token’s.
 *
 * @since 6.0.0
 */
class RequireAccessDuringMaintenance
{
    public function __construct(private readonly MaintenanceMode $maintenanceMode) {}

    /** @param Closure(Request): mixed $next */
    public function handle(Request $request, Closure $next): mixed
    {
        if ($this->maintenanceMode->active() && ! Gate::check($request->isCpRequest() ? 'accessCpWhenSystemIsOff' : 'accessSiteWhenSystemIsOff')) {
            abort(503);
        }

        return $next($request);
    }
}
