<?php

declare(strict_types=1);

namespace CraftCms\Cms\User\Events;

use CraftCms\Cms\Shared\Concerns\ValidatableEvent;
use CraftCms\Cms\User\Elements\User;

/**
 * @event UnlockingUser The event that is triggered before a user is unlocked.
 *
 * You may set [[$isValid]] to `false` to prevent the user from getting unlocked.
 */
class UnlockingUser extends UserEvent
{
    use ValidatableEvent;
}
