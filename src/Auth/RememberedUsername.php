<?php

declare(strict_types=1);

namespace CraftCms\Cms\Auth;

use CraftCms\Cms\Cms;
use CraftCms\Cms\User\Elements\User;
use Illuminate\Support\Facades\Cookie;

final readonly class RememberedUsername
{
    public static function cookieName(): string
    {
        return config('session.cookie').'_username';
    }

    public static function get(): ?string
    {
        return Cookie::get(self::cookieName());
    }

    public static function set(User $user): void
    {
        if (Cms::config()->rememberUsernameDuration === 0) {
            Cookie::unqueue(self::cookieName());
            Cookie::forget(self::cookieName());

            return;
        }

        Cookie::queue(
            name: self::cookieName(),
            value: $user->username,
            minutes: floor(Cms::config()->rememberUsernameDuration / 60),
        );
    }
}
