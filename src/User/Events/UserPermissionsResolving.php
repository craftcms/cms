<?php

declare(strict_types=1);

namespace CraftCms\Cms\User\Events;

use CraftCms\Cms\User\Data\PermissionGroup;
use Illuminate\Support\Collection;

/**
 * @event UserPermissionsResolving The event that is triggered when registering user permissions.
 */
class UserPermissionsResolving
{
    public function __construct(
        /** @var Collection<PermissionGroup> */
        public Collection $permissions,
    ) {}
}
