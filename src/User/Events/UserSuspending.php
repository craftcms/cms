<?php

declare(strict_types=1);

namespace CraftCms\Cms\User\Events;

use CraftCms\Cms\Shared\Concerns\ValidatableEvent;
use CraftCms\Cms\User\Elements\User;

/**
 * @event UserSuspending The event that is triggered before a user is suspended.
 *
 * You may set [[$isValid]] to `false` to prevent the user from getting suspended.
 */
class UserSuspending extends UserEvent
{
    use ValidatableEvent;
}
