<?php

declare(strict_types=1);

namespace CraftCms\Cms\User\Events;

use CraftCms\Cms\User\Data\UserGroup;

/**
 * @event SavingUserGroup The event that is triggered before a user group is saved.
 */
final class SavingUserGroup
{
    public function __construct(
        public UserGroup $userGroup,
        public bool $isNew = false
    ) {}
}
