<?php

namespace CraftCms\Cms\Http\Middleware;

use Closure;
use CraftCms\Cms\Config\GeneralConfig;
use CraftCms\Cms\User\Models\User;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;

final class RequireAdmin
{
    public function __construct(
        protected GeneralConfig $generalConfig,
    ) {}

    public function handle(Request $request, Closure $next): mixed
    {
        if (! $user = $request->user()) {
            throw new AuthenticationException('Unauthenticated.');
        }

        /** @var User $user */
        abort_unless($user->isAdmin(), 403, 'User is not permitted to perform this action.');

        return $next($request);
    }
}
