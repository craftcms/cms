<?php

declare(strict_types=1);

namespace CraftCms\Cms\User\Events;

use CraftCms\Cms\Shared\Concerns\ValidatableEvent;
use CraftCms\Cms\User\Data\UserGroup;
use CraftCms\Cms\User\Elements\User;

/**
 * @event UserAssignedToDefaultGroups The event that is triggered after a user is assigned to the default user group.
 */
class UserAssignedToDefaultGroups
{
    use ValidatableEvent;

    public function __construct(
        public User $user,
        /** @var UserGroup[] */
        public array $userGroups,
    ) {}
}
