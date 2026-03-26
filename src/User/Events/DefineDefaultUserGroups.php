<?php

declare(strict_types=1);

namespace CraftCms\Cms\User\Events;

use CraftCms\Cms\User\Data\UserGroup;
use CraftCms\Cms\User\Elements\User;
use CraftCms\Cms\User\Users;

/**
 * @event DefineDefaultUserGroups The event that is triggered when defining the default user groups to assign to a publicly-registered user.
 *
 * {@see Users::getDefaultUserGroups()}
 */
class DefineDefaultUserGroups
{
    public function __construct(
        public User $user,
        /** @var UserGroup[] */
        public array $userGroups,
    ) {}
}
