<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Middleware;

use Closure;
use Illuminate\Auth\Middleware\Authenticate;

/**
 * @since 6.0.0
 */
class AuthenticateIfLive extends Authenticate
{
    #[\Override]
    public function handle($request, Closure $next, ...$guards): mixed
    {
        if (! app()->isDownForMaintenance()) {
            return parent::handle($request, $next, ...$guards);
        }

        return $next($request);
    }
}
