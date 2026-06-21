<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Middleware;

use Closure;
use CraftCms\Cms\Cms;
use Illuminate\Http\Request;

use function CraftCms\Cms\setLocale;

readonly class UpdateLocale
{
    public function handle(Request $request, Closure $next): mixed
    {
        setLocale(Cms::targetLanguage($request));

        return $next($request);
    }
}
