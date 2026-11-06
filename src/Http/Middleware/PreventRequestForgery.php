<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\PreventRequestForgery as LaravelMiddleware;
use Illuminate\Http\Request;
use Override;

/**
 * Accepts requests without a CSRF token when their `Origin` header matches the
 * request's scheme and host, if the browser didn't send `Sec-Fetch-Site`.
 *
 * Browsers only send `Sec-Fetch-Site` to HTTPS and `localhost` origins, so
 * same-origin forms without a token would otherwise fail on plain-HTTP
 * sites, such as `http://my-site.test` local dev sites, and in older browsers.
 *
 * The same fallback is proposed for Laravel in laravel/framework#59198.
 *
 * @since 6.0.0
 */
class PreventRequestForgery extends LaravelMiddleware
{
    /**
     * Whether requests are checked by their origin alone, set by {@see useOriginOnly()}, so CSRF tokens are never checked.
     */
    public static function checksOriginOnly(): bool
    {
        return static::$originOnly;
    }

    /**
     * @param  Request  $request
     */
    #[Override]
    protected function hasValidOrigin($request): bool
    {
        if (! $request->headers->has('Sec-Fetch-Site') && $this->originMatchesHost($request)) {
            return true;
        }

        return parent::hasValidOrigin($request);
    }

    private function originMatchesHost(Request $request): bool
    {
        $origin = $request->headers->get('Origin');

        if ($origin === null || $origin === 'null') {
            return false;
        }

        return strcasecmp(rtrim($origin, '/'), $request->getSchemeAndHttpHost()) === 0;
    }
}
