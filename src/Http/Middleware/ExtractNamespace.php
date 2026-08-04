<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Middleware;

use Closure;
use CraftCms\Cms\Support\Arr;
use Illuminate\Http\Request;

readonly class ExtractNamespace
{
    public function handle(Request $request, Closure $next): mixed
    {
        if (! $namespace = $request->header('X-Craft-Namespace')) {
            return $next($request);
        }

        $namespacedInput = Arr::get($request->input(), $namespace, []);

        if (is_array($namespacedInput)) {
            $request->merge($namespacedInput);
        }

        return $next($request);
    }
}
