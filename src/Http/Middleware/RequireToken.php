<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Context;

final readonly class RequireToken
{
    public function handle(Request $request, Closure $next): mixed
    {
        $token = Context::getHidden('craft.token');

        abort_if(is_null($token), 401, 'Valid token required');

        return $next($request);
    }
}
