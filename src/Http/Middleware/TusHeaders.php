<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TusHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = ! $request->isMethod('OPTIONS') && $request->header('Tus-Resumable') !== '1.0.0'
            ? response()->noContent(412, ['Tus-Version' => '1.0.0'])
            : $next($request);

        $response->headers->set('Tus-Resumable', '1.0.0');

        return $response;
    }
}
