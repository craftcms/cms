<?php

declare(strict_types=1);

namespace CraftCms\Cms\User\Events;

use CraftCms\Cms\Shared\Concerns\ValidatableEvent;
use CraftCms\Cms\User\Elements\User;

/**
 * @event AssigningUserToDefaultGroups The event that is triggered before a user is assigned to the default user group.
 *
 * You may set [[$isValid]] to `false` to prevent the user from getting assigned to the default user group.
 */
final class AssigningUserToDefaultGroups
{
    use ValidatableEvent;

    public function __construct(
        public User $user,
        /** @var \CraftCms\Cms\User\Data\UserGroup[] */
        public array $userGroups,
    ) {}
}
