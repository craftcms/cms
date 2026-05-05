<?php

declare(strict_types=1);

namespace CraftCms\Cms\User\Events;

use CraftCms\Cms\User\Data\UserGroup;

/**
 * @event UserGroupSaving The event that is triggered before a user group is saved.
 */
class UserGroupSaving
{
    public function __construct(
        public UserGroup $userGroup,
        public bool $isNew = false
    ) {}
}
