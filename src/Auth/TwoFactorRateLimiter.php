<?php

declare(strict_types=1);

namespace CraftCms\Cms\Auth;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;

class TwoFactorRateLimiter
{
    public const string NAME = 'two-factor';

    public function limit(Request $request): Limit
    {
        return Limit::perMinute(5)->by($this->key($request));
    }

    private function key(Request $request): string
    {
        return $request->session()->get('user.id').'|'.$request->ip();
    }
}
