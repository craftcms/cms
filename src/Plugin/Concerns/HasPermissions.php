<?php

declare(strict_types=1);

namespace CraftCms\Cms\Plugin\Concerns;

use CraftCms\Cms\Plugin\Plugin;
use CraftCms\Cms\User\Data\Permission;
use CraftCms\Cms\User\Data\PermissionGroup;
use CraftCms\Cms\User\UserPermissions;

/**
 * @mixin Plugin
 *
 * @internal
 */
trait HasPermissions
{
    /**
     * @return Permission[]
     */
    protected function getPermissions(): array
    {
        return [];
    }

    public function bootHasPermissions(): void
    {
        $this->app->make(UserPermissions::class)->registerPermissionGroup(
            "plugin:$this->handle",
            function (): ?PermissionGroup {
                $permissions = collect($this->getPermissions());

                if ($permissions->isEmpty()) {
                    return null;
                }

                throw_if(
                    $permissions->whereInstanceOf(Permission::class)->count() !== $permissions->count(),
                    sprintf('Each permission returned from `getPermissions()` needs to be an instance of `%s`', Permission::class)
                );

                return new PermissionGroup(
                    handle: "plugin:$this->handle",
                    heading: $this->name ?? $this->handle,
                    permissions: $permissions,
                );
            },
        );
    }
}
