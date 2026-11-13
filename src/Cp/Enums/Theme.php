<?php

declare(strict_types=1);

namespace CraftCms\Cms\Cp\Enums;

use CraftCms\Cms\User\Elements\User;
use Illuminate\Support\Facades\Cookie;

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
     * The cookie the control panel keeps set to the theme it's showing,
     * `light` or `dark`, so the server can tell what `System` resolves to.
     */
    public const string COOKIE = 'CraftTheme';

    /**
     * The cookie the control panel keeps set to the signed-in user's theme
     * preference, so pages shown once nobody is logged in, such as the login
     * page, can keep showing the theme the last user chose.
     */
    public const string PREFERENCE_COOKIE = 'CraftThemePreference';

    /**
     * The theme to show: the one the user has chosen, or `System` if they
     * haven't chosen one. Without a user, such as on the login page, it's the
     * one the last user to sign in on this browser had chosen.
     */
    public static function forUser(?User $user): self
    {
        $theme = $user ? $user->getPreference('theme') : request()->cookie(self::PREFERENCE_COOKIE);

        return is_string($theme) ? self::tryFrom($theme) ?? self::System : self::System;
    }

    /**
     * Remembers the user's preference in {@see self::PREFERENCE_COOKIE} for the
     * pages shown once they sign out. The control panel's JavaScript keeps it up
     * to date as the preference changes, so it isn't HTTP-only.
     */
    public static function rememberPreference(User $user): void
    {
        Cookie::queue(Cookie::make(self::PREFERENCE_COOKIE, self::forUser($user)->value, 525600, httpOnly: false, sameSite: 'lax'));
    }

    /**
     * The theme the control panel is showing, `Light` or `Dark`: the one the
     * user has chosen, or for `System`, the one the control panel last
     * reported through {@see self::COOKIE}, defaulting to `Light`.
     */
    public static function resolveFor(?User $user): self
    {
        $theme = self::forUser($user);

        if ($theme !== self::System) {
            return $theme;
        }

        return request()->cookie(self::COOKIE) === self::Dark->value ? self::Dark : self::Light;
    }
}
