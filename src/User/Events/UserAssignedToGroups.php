<?php

declare(strict_types=1);

namespace CraftCms\Cms\User\Events;

/**
 * @event UserAssignedToGroups The event that is triggered after a user is assigned to some user groups.
 */
class UserAssignedToGroups
{
    public function __construct(
        public int $userId,
        /** @var int[] */
        public array $groupIds,
        /** @var int[] */
        public array $removedGroupIds,
        /** @var int[] */
        public array $newGroupIds,
    ) {}
}
