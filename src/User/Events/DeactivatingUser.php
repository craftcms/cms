<?php

declare(strict_types=1);

namespace CraftCms\Cms\User\Events;

use CraftCms\Cms\Shared\Concerns\ValidatableEvent;
use CraftCms\Cms\User\Elements\User;

/**
 * @event DeactivatingUser The event that is triggered before a user is deactivated.
 *
 * You may set [[$isValid]] to `false` to prevent the user from getting deactivated.
 */
class DeactivatingUser extends UserEvent
{
    use ValidatableEvent;
}
