<?php

declare(strict_types=1);

namespace CraftCms\Cms\Mcp\Http\Middleware;

use Closure;
use CraftCms\Cms\Cms;
use CraftCms\Cms\User\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * @since 6.0.0
 */
class UseDebugMcpUser
{
    public function handle(Request $request, Closure $next): mixed
    {
        $user = User::query()->find(Cms::config()->mcp->debugUserId);

        if ($user instanceof User) {
            Auth::setUser($user);
            $request->setUserResolver(static fn (): User => $user);
        }

        return $next($request);
    }
}
