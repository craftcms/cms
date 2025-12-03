<?php

declare(strict_types=1);

namespace CraftCms\Cms\User\Events;

use CraftCms\Cms\Element\Elements\User;

/**
 * @event GroupsAndPermissionsAssigned The event that is triggered after user groups and permissions have been assigned to the user getting saved
 */
final class GroupsAndPermissionsAssigned
{
    public function __construct(
        public User $user,
    ) {}
}
