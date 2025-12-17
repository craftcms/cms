<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Middleware;

use Closure;
use CraftCms\Cms\Config\GeneralConfig;
use Illuminate\Http\Request;

final readonly class RequireAdminChanges
{
    public function __construct(
        private GeneralConfig $generalConfig,
    ) {}

    public function handle(Request $request, Closure $next): mixed
    {
        abort_unless($request->user('craft')->isAdmin(), 403, 'User is not permitted to perform this action.');

        if (! $this->generalConfig->allowAdminChanges) {
            abort(403, 'Administrative changes are disallowed in this environment.');
        }

        return $next($request);
    }
}
