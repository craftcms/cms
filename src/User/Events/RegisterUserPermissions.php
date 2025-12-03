<?php

declare(strict_types=1);

namespace CraftCms\Cms\User\Events;

use Illuminate\Support\Collection;

/**
 * @event RegisterUserPermissions The event that is triggered when registering user permissions.
 */
final class RegisterUserPermissions
{
    public function __construct(
        /** @var Collection<\CraftCms\Cms\User\Data\PermissionGroup> */
        public Collection $permissions,
    ) {}
}
