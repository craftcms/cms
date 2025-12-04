<?php

declare(strict_types=1);

namespace CraftCms\Cms\Plugin\Concerns;

use CraftCms\Cms\Plugin\Plugin;
use CraftCms\Cms\User\Data\Permission;
use CraftCms\Cms\User\Data\PermissionGroup;
use CraftCms\Cms\User\Events\RegisterUserPermissions;
use Illuminate\Support\Facades\Event;

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
        $permissions = collect($this->getPermissions());

        if ($permissions->isEmpty()) {
            return;
        }

        throw_if(
            $permissions->whereInstanceOf(Permission::class)->count() !== $permissions->count(),
            sprintf('Each permission returned from `getPermissions()` needs to be an instance of `%s`', Permission::class)
        );

        Event::listen(RegisterUserPermissions::class, function (RegisterUserPermissions $event) use ($permissions) {
            $plugin = self::getInstance();

            $event->permissions = $event->permissions->push(new PermissionGroup(
                heading: $plugin->name ?? $plugin->handle,
                permissions: $permissions,
            ));
        });
    }
}
