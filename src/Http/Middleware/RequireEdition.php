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
        try {
            Edition::require(Edition::from((int) $edition));
        } catch (Edition\Exceptions\WrongEditionException) {
            abort(404);
        }

        return $next($request);
    }
}
