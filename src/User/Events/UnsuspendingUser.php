<?php

declare(strict_types=1);

namespace CraftCms\Cms\User\Events;

use CraftCms\Cms\Shared\Concerns\ValidatableEvent;
use CraftCms\Cms\User\Elements\User;

/**
 * @event UnsuspendingUser The event that is triggered before a user is unsuspended.
 *
 * You may set [[$isValid]] to `false` to prevent the user from getting unsuspended.
 */
final class UnsuspendingUser extends UserEvent
{
    use ValidatableEvent;
}
