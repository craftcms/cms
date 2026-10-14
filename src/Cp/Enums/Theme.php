<?php

declare(strict_types=1);

namespace CraftCms\Cms\Cp\Enums;

use CraftCms\Cms\User\Elements\User;
use Illuminate\Http\Request;

/**
 * Control panel color themes a user can choose in their preferences. `System`
 * follows the operating system's light or dark setting.
 */
enum Theme: string
{
    case Light = 'light';
    case Dark = 'dark';
    case System = 'system';

    /**
     * The theme a user has chosen, or `System` if they haven't chosen one or
     * aren't logged in.
     */
    public static function forUser(?User $user): self
    {
        $theme = $user?->getPreference('theme');

        return is_string($theme) ? self::tryFrom($theme) ?? self::System : self::System;
    }

    /**
     * The theme to show: the user's, or on a logged-out page, the one
     * remembered from whoever last used the control panel in this browser
     * (see {@see cookieName()}), or `System`.
     */
    public static function current(?User $user, ?Request $request = null): self
    {
        if ($user !== null) {
            return self::forUser($user);
        }

        $theme = ($request ?? request())->cookie(self::cookieName());

        return is_string($theme) ? self::tryFrom($theme) ?? self::System : self::System;
    }

    /**
     * The cookie that remembers the theme for logged-out pages, such as the
     * login page.
     */
    public static function cookieName(): string
    {
        return config('session.cookie').'_theme';
    }
}
