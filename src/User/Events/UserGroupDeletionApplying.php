<?php

declare(strict_types=1);

namespace CraftCms\Cms\User\Events;

use CraftCms\Cms\User\Data\UserGroup;

/**
 * @event UserGroupDeletionApplying The event that is triggered before a user group delete is applied to the database.
 */
class UserGroupDeletionApplying
{
    public function __construct(
        public UserGroup $userGroup,
    ) {}
}
