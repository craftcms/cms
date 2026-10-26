<?php

declare(strict_types=1);

namespace CraftCms\Cms\Auth;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

/**
 * @since 6.0.0
 */
class LoginRateLimiter
{
    public const string NAME = 'login';

    /**
     * Five attempts a minute for each account from an IP address, which a successful login resets, and the
     * default rate limit for the IP address across all accounts, which it doesn't.
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

    public function clear(Request $request): void
    {
        RateLimiter::clear(md5(self::NAME.$this->key($request)));
    }

    private function key(Request $request): string
    {
        return mb_strtolower((string) $request->input('loginName')).'|'.$request->ip();
    }
}
