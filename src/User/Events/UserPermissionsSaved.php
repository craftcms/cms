<?php

declare(strict_types=1);

namespace CraftCms\Cms\User\Events;

/**
 * @event UserPermissionsSaved The event triggered after saving user permissions.
 *
 * @since 6.0.0
 */
class UserPermissionsSaved
{
    public function __construct(
        public int $userId,
        /** @var string[] */
        public array $permissions,
    ) {}
}
