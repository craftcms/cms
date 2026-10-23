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
     * The theme to show: the one the user has chosen, or `System` if they
     * haven't chosen one or aren't logged in, such as on the login page.
     */
    public static function forUser(?User $user): self
    {
        $theme = $user?->getPreference('theme');

        return is_string($theme) ? self::tryFrom($theme) ?? self::System : self::System;
    }
}
