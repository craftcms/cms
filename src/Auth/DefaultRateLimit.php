<?php

declare(strict_types=1);

namespace CraftCms\Cms\Auth;

use CraftCms\Cms\Cms;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;

/**
 * The per-IP limit for actions that are rate-limited by default, set by the
 * `defaultRateLimit` and `defaultRateLimitDuration` config settings.
 *
 * @since 6.0.0
 */
final class DefaultRateLimit
{
    /**
     * The limit for a request, or `null` if `defaultRateLimit` is disabled.
     *
     * Each rate limiter tracks its own requests, so a limiter's other limits key by something other than the IP alone.
     */
    public static function for(Request $request): ?Limit
    {
        $config = Cms::config();

        if ($config->defaultRateLimit === false) {
            return null;
        }

        return Limit::perSecond($config->defaultRateLimit, (int) $config->defaultRateLimitDuration)
            ->by('ip:'.$request->ip());
    }
}
