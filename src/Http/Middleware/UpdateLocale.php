<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Middleware;

use Closure;
use CraftCms\Cms\Cms;
use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

readonly class UpdateLocale
{
    public function __construct(
        private Application $app,
    ) {}

    public function handle(Request $request, Closure $next): mixed
    {
        $this->app->setLocale(Cms::targetLanguage($request));

        return $next($request);
    }
}
