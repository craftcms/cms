<?php

declare(strict_types=1);

namespace CraftCms\Cms\Auth;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

class LoginRateLimiter
{
    public const string NAME = 'login';

    public function limit(Request $request): Limit
    {
        return Limit::perMinute(5)->by($this->key($request));
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
