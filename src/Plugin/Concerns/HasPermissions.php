<?php

declare(strict_types=1);

namespace CraftCms\Cms\Plugin\Concerns;

use CraftCms\Cms\Plugin\Plugin;
use CraftCms\Cms\User\Data\PermissionGroup;
use CraftCms\Cms\User\Events\RegisterUserPermissions;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Event;

/**
 * @mixin Plugin
 *
 * @internal
 */
trait HasPermissions
{
    /**
     * @return Collection<PermissionGroup>|PermissionGroup
     */
    protected function getPermissions(): Collection|PermissionGroup
    {
        return collect();
    }

    public function bootHasPermissions(): void
    {
        $permissions = $this->getPermissions();

        if (! $permissions instanceof Collection) {
            $permissions = collect([$permissions]);
        }

        if ($permissions->isEmpty()) {
            return;
        }

        throw_if(
            $permissions->whereInstanceOf(PermissionGroup::class)->count() !== $permissions->count(),
            'Each item in the permissions collection needs to be an instance of PermissionGroup'
        );

        Event::listen(RegisterUserPermissions::class, function (RegisterUserPermissions $event) use ($permissions) {
            $event->permissions = $event->permissions->merge($permissions);
        });
    }
}
