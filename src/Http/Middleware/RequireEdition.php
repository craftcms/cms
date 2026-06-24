<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Middleware;

use Closure;
use CraftCms\Cms\Edition;
use Illuminate\Http\Request;

readonly class RequireEdition
{
    public function handle(Request $request, Closure $next, string $edition): mixed
    {
        Edition::require(Edition::from((int) $edition));

        return $next($request);
    }
}
