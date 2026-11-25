<?php

declare(strict_types=1);

namespace CraftCms\Cms\Mcp\Http\Middleware;

use Closure;
use CraftCms\Cms\User\Contracts\CraftUser;
use CraftCms\Cms\User\Elements\User;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;
use Laravel\Passport\Exceptions\MissingScopeException;
use Symfony\Component\HttpFoundation\Response;

/**
 * Requires the access token of an authenticated MCP server request to have at least one of the given scopes.
 *
 * It also rejects the token when its user has been deactivated, suspended or made pending since it was issued.
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

        if ($user instanceof CraftUser && $user->asElement()->getStatus() !== User::STATUS_ACTIVE) {
            throw new AuthenticationException(guards: ['craft-mcp']);
        }

        if (! $user || ! array_any($scopes, $user->tokenCan(...))) {
            throw new MissingScopeException($scopes);
        }

        return $next($request);
    }
}
