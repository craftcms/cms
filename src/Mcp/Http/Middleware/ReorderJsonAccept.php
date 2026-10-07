<?php

declare(strict_types=1);

namespace CraftCms\Cms\Mcp\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** @since 6.0.0 */
readonly class ReorderJsonAccept
{
    public function handle(Request $request, Closure $next): Response
    {
        $accept = $request->header('Accept') ?? 'application/json';

        if (is_string($accept) && str_contains($accept, ',')) {
            $accept = array_map(trim(...), explode(',', $accept));
        }

        if (is_array($accept)) {
            usort($accept, static fn (string $a, string $b): int => str_contains($b, 'application/json') <=> str_contains($a, 'application/json'));
            $accept = implode(', ', $accept);
        }

        $request->headers->set('Accept', $accept);

        return $next($request);
    }
}
