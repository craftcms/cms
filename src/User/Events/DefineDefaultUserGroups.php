<?php

declare(strict_types=1);

namespace CraftCms\Cms\User\Events;

use CraftCms\Cms\User\Elements\User;

/**
 * @event DefineDefaultUserGroups The event that is triggered when defining the default user groups to assign to a publicly-registered user.
 *
 * {@see \CraftCms\Cms\User\Users::getDefaultUserGroups()}
 */
final class DefineDefaultUserGroups
{
    public function __construct(
        public User $user,
        /** @var \CraftCms\Cms\User\Data\UserGroup[] */
        public array $userGroups,
    ) {}
}
