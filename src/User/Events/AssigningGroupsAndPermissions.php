<?php

declare(strict_types=1);

namespace CraftCms\Cms\User\Events;

use CraftCms\Cms\Element\Elements\User;

/**
 * @event AssigningGroupsAndPermissions The event that is triggered BEFORE user groups and permissions ARE assigned to the user getting saved
 */
final class AssigningGroupsAndPermissions
{
    public function __construct(
        public User $user,
    ) {}
}
