<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Middleware;

use Closure;
use CraftCms\Cms\User\Models\User;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;

final readonly class RequireAdmin
{
    public function handle(Request $request, Closure $next): mixed
    {
        throw_unless($user = $request->user(), AuthenticationException::class, 'Unauthenticated.');

        /** @var User $user */
        abort_unless($user->isAdmin(), 403, 'User is not permitted to perform this action.');

        return $next($request);
    }
}
