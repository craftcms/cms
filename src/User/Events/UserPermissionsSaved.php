<?php

declare(strict_types=1);

namespace CraftCms\Cms\User\Events;

/**
 * @event UserPermissionsSaved The event triggered after saving user permissions.
 */
class UserPermissionsSaved
{
    public function __construct(
        public int $userId,
        public array $permissions,
    ) {}
}
