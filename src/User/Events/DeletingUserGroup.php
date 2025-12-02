<?php

declare(strict_types=1);

namespace CraftCms\Cms\User\Events;

use CraftCms\Cms\User\Data\UserGroup;

/**
 * @event DeletingUserGroup The event that is triggered before a user group is deleted.
 */
final class DeletingUserGroup
{
    public function __construct(
        public UserGroup $userGroup
    ) {}
}
