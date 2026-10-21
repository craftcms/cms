<?php

declare(strict_types=1);

namespace CraftCms\Cms\Auth;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;

/**
 * @since 6.0.0
 */
class TwoFactorRateLimiter
{
    public const string NAME = 'two-factor';

    /**
     * Five verifications a minute for each user from an IP address, and the default rate limit for the IP address.
     *
     * @return list<Limit>
     */
    public function limit(Request $request): array
    {
        return array_values(array_filter([
            Limit::perMinute(5)->by($this->key($request)),
            DefaultRateLimit::for($request),
        ]));
    }

    private function key(Request $request): string
    {
        return $request->session()->get('user.id').'|'.$request->ip();
    }
}
