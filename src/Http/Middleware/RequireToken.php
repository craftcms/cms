<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Context;

readonly class RequireToken
{
    public function handle(Request $request, Closure $next): mixed
    {
        $token = Context::pullHidden(HandleTokenRequest::TOKEN_KEY);

        abort_if(is_null($token), 401, 'Valid token required');

        $request->headers->remove(HandleTokenRequest::TOKEN_HEADER);

        return $next($request);
    }
}
