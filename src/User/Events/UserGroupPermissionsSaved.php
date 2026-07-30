<?php

declare(strict_types=1);

namespace CraftCms\Cms\User\Events;

/**
 * @event UserGroupPermissionsSaved The event triggered after saving group permissions.
 */
class UserGroupPermissionsSaved
{
    public function __construct(
        public int $userGroupId,
        /** @var string[] */
        public array $permissions,
    ) {}
}
