<?php

declare(strict_types=1);

namespace CraftCms\Cms\Mcp\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Laravel\Passport\Exceptions\MissingScopeException;
use Symfony\Component\HttpFoundation\Response;

/**
 * Requires the access token of an authenticated MCP server request to have at least one of the given scopes.
 *
 * Unlike Passport’s `CheckTokenForAnyScope`, it doesn’t load the OAuth signing keys when it’s created, which Laravel
 * does for every route middleware when a request terminates, so requests still get their response when the keys are
 * missing or invalid.
 *
 * @since 6.0.0
 */
readonly class RequireAnyTokenScope
{
    /** @param list<string> $scopes */
    public static function using(array $scopes): string
    {
        return static::class.':'.implode(',', $scopes);
    }

    public function handle(Request $request, Closure $next, string ...$scopes): Response
    {
        $user = $request->user('craft-mcp');

        if (! $user || ! array_any($scopes, $user->tokenCan(...))) {
            throw new MissingScopeException($scopes);
        }

        return $next($request);
    }
}
