<?php

declare(strict_types=1);

namespace CraftCms\Cms\Auth;

use CraftCms\Cms\Cms;
use CraftCms\Cms\User\Elements\User;
use Illuminate\Support\Facades\Cookie;

final readonly class RememberedUsername
{
    public static function get(): ?string
    {
        return Cookie::get(Cms::cookiePrefix().'_username');
    }

    public static function set(User $user): void
    {
        $prefix = Cms::cookiePrefix();

        if (Cms::config()->rememberUsernameDuration === 0) {
            Cookie::forget("{$prefix}_username");

            return;
        }

        Cookie::queue(
            name: "{$prefix}_username",
            value: $user->username,
            minutes: floor(Cms::config()->rememberUsernameDuration / 60),
        );
    }
}
