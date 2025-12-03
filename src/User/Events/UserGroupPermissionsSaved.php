<?php

declare(strict_types=1);

namespace CraftCms\Cms\User\Events;

/**
 * @event UserGroupPermissionsSaved The event triggered after saving group permissions.
 */
final class UserGroupPermissionsSaved
{
    public function __construct(
        public int $userGroupId,
        public array $permissions,
    ) {}
}
