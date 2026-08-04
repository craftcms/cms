<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Middleware;

use Closure;
use Illuminate\Auth\Middleware\Authenticate;

class AuthenticateIfLive extends Authenticate
{
    #[\Override]
    public function handle($request, Closure $next, ...$guards): mixed
    {
        if (app()->isLive()) {
            return parent::handle($request, $next, ...$guards);
        }

        return $next($request);
    }
}
