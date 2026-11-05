<?php

declare(strict_types=1);

namespace CraftCms\Cms\Cp\Enums;

use CraftCms\Cms\User\Elements\User;

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
     * The theme to show: the one the user has chosen, or `System` if they
     * haven't chosen one or aren't logged in, such as on the login page.
     */
    public static function forUser(?User $user): self
    {
        $theme = $user?->getPreference('theme');

        return is_string($theme) ? self::tryFrom($theme) ?? self::System : self::System;
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
