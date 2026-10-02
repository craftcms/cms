<?php

declare(strict_types=1);

namespace CraftCms\Cms\User\Events;

use CraftCms\Cms\Shared\Concerns\ValidatableEvent;
use CraftCms\Cms\User\Elements\User;

/**
 * @event UserUnsuspending The event that is triggered before a user is unsuspended.
 *
 * You may set [[$isValid]] to `false` to prevent the user from getting unsuspended.
 *
 * @since 6.0.0
 */
class UserUnsuspending extends UserEvent
{
    use ValidatableEvent;
}
