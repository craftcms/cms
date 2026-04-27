<?php

declare(strict_types=1);

namespace CraftCms\Cms\User\Events;

use CraftCms\Cms\Shared\Concerns\ValidatableEvent;

/**
 * @event AssigningUserToGroups The event that is triggered before a user is assigned to some user groups.
 *
 * You may set [[$isValid]] to `false` to prevent the user from getting assigned to the groups.
 */
class AssigningUserToGroups
{
    use ValidatableEvent;

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
