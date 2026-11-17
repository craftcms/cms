<?php

declare(strict_types=1);

namespace CraftCms\Cms\Mcp\Http\Middleware;

use Closure;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Contracts\Auth\Factory as Auth;
use Illuminate\Http\Request;
use LogicException;
use Symfony\Component\HttpFoundation\Response;

/**
 * Rejects authenticated MCP server requests without a bearer token when the OAuth signing keys are missing or invalid.
 *
 * Passport loads the keys when it creates its guard, which would fail with a server error, so clients still get the
 * challenge that tells them how to sign in.
 *
 * @since 6.0.0
 */
readonly class ChallengeWithoutOAuthKeys
{
    public function __construct(private Auth $auth) {}

    public function handle(Request $request, Closure $next): Response
    {
        if ($request->bearerToken() === null) {
            try {
                $this->auth->guard('craft-mcp');
            } catch (LogicException) {
                throw new AuthenticationException(guards: ['craft-mcp']);
            }
        }

        return $next($request);
    }
}
