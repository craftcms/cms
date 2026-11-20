<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Middleware;

use Closure;
use CraftCms\Cms\Cms;
use Illuminate\Auth\AuthManager;
use Illuminate\Http\Request;

/**
 * @since 6.0.0
 */
class UseCraftAuthGuard
{
    public function __construct(private readonly AuthManager $auth) {}

    public function handle(Request $request, Closure $next): mixed
    {
        if (Cms::config()->security->authGuard === null) {
            return $next($request);
        }

        $defaultGuard = $this->auth->getDefaultDriver();
        $this->auth->shouldUse(Cms::config()->security->getAuthGuard());

        try {
            return $next($request);
        } finally {
            $this->auth->shouldUse($defaultGuard);
        }
    }
}
