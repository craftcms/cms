<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Middleware;

use Closure;
use Illuminate\Config\Repository;
use Illuminate\Http\Request;

readonly class SetCraftGuard
{
    public function __construct(
        private Repository $config,
    ) {}

    public function handle(Request $request, Closure $next): mixed
    {
        $this->config->set('auth.defaults.guard', 'craft');

        return $next($request);
    }
}
