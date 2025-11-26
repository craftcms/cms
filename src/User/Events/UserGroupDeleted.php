<?php

declare(strict_types=1);

namespace CraftCms\Cms\User\Events;

use CraftCms\Cms\User\Data\UserGroup;

/**
 * @event UserGroupDeleted The event that is triggered after a user group is saved.
 */
final class UserGroupDeleted
{
    public function __construct(
        public UserGroup $userGroup
    ) {}
}
