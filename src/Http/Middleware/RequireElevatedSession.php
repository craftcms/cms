<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Middleware;

use Closure;
use CraftCms\Cms\Auth\SessionAuth;
use Illuminate\Http\Request;

use function CraftCms\Cms\t;

final readonly class RequireElevatedSession
{
    public function handle(Request $request, Closure $next): mixed
    {
        abort_unless(
            SessionAuth::hasElevatedSession(),
            403,
            t('This action may only be performed with an elevated session.')
        );

        return $next($request);
    }
}
