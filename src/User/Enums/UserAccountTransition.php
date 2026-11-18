<?php

declare(strict_types=1);

namespace CraftCms\Cms\User\Enums;

use Carbon\CarbonInterface;
use CraftCms\Cms\User\Models\User as UserModel;

/**
 * Lists the ways a user account can change, and the `users` columns each one writes.
 *
 * An account has a credential state (inactive, pending or active), a suspended
 * flag and a locked flag. Activate and Deactivate also clear both flags and the
 * failed login count.
 */
enum UserAccountTransition
{
    /** Makes an inactive or pending user active. Verifying a pending user's email also activates them. */
    case Activate;

    /** Makes a pending or active user inactive. */
    case Deactivate;

    /** Makes an inactive user pending so they can verify their email. An active user stays active. */
    case SendVerification;

    /** Makes a user inactive and clears their password, but leaves the suspended and locked flags alone. */
    case RemoveCredentials;

    /** Locks the user once their failed logins reach `maxInvalidLogins`. */
    case Lock;

    /** Unlocks the user. */
    case Unlock;

    /** Suspends the user. */
    case Suspend;

    /** Lifts the user's suspension. */
    case Unsuspend;

    /**
     * @return array<string, mixed> the `users` columns to write
     */
    public function changes(UserModel $user, ?CarbonInterface $now = null): array
    {
        return match ($this) {
            self::Activate, self::Deactivate => [
                'active' => $this === self::Activate,
                'pending' => false,
                'locked' => false,
                'suspended' => false,
                'invalidLoginWindowStart' => null,
                'invalidLoginCount' => null,
                'lastInvalidLoginDate' => null,
                'lockoutDate' => null,
            ],
            self::SendVerification => ['pending' => $user->active ? $user->pending : true],
            self::RemoveCredentials => ['active' => false, 'pending' => false, 'password' => null],
            self::Lock => [
                'locked' => true,
                'invalidLoginWindowStart' => null,
                'invalidLoginCount' => null,
                'lockoutDate' => $now ?? now('UTC'),
            ],
            self::Unlock => [
                'locked' => false,
                'invalidLoginCount' => null,
                'invalidLoginWindowStart' => null,
                'lockoutDate' => null,
            ],
            self::Suspend => ['suspended' => true],
            self::Unsuspend => ['suspended' => false],
        };
    }
}
