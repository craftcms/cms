<?php

declare(strict_types=1);

namespace CraftCms\Cms\User\Events;

use CraftCms\Cms\Shared\Concerns\ValidatableEvent;
use CraftCms\Cms\User\Elements\User;

/**
 * @event ActivatingUser The event that is triggered before a user is activated.
 *
 * You may set [[$isValid]] to `false` to prevent the user from getting activated.
 */
class ActivatingUser extends UserEvent
{
    use ValidatableEvent;
}
