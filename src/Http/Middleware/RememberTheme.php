<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Middleware;

use Closure;
use CraftCms\Cms\Cp\Enums\Theme;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;

use function CraftCms\Cms\currentUserElement;

/**
 * Remembers the logged-in user's theme in a cookie, so logged-out pages in the
 * same browser — the login page, say — can show it too.
 *
 * Only set when it changes, so it isn't sent back with every response.
 *
 * @since 6.0.0
 */
class RememberTheme
{
    public function handle(Request $request, Closure $next): mixed
    {
        $response = $next($request);
        $user = currentUserElement();

        if ($user === null) {
            return $response;
        }

        $theme = Theme::forUser($user)->value;

        if ($request->cookie(Theme::cookieName()) !== $theme) {
            Cookie::queue(Cookie::forever(Theme::cookieName(), $theme));
        }

        return $response;
    }
}
