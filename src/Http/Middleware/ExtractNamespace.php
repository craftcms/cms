<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

final readonly class ExtractNamespace
{
    public function handle(Request $request, Closure $next): mixed
    {
        if (! $namespace = $request->header('X-Craft-Namespace')) {
            return $next($request);
        }

        $request->merge($request->get($namespace));

        return $next($request);
    }
}
