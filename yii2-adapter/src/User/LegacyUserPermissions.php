<?php

declare(strict_types=1);

namespace CraftCms\Yii2Adapter\User;

use Craft;
use craft\events\RegisterUserPermissionsEvent;
use craft\services\UserPermissions as LegacyUserPermissionsService;
use CraftCms\Cms\User\Data\Permission;
use CraftCms\Cms\User\Data\PermissionGroup;
use CraftCms\Cms\User\UserPermissions;
use Illuminate\Support\Collection;
use Override;

/** @internal */
class LegacyUserPermissions extends UserPermissions
{
    /** @var Collection<int, PermissionGroup> */
    private Collection $legacyPermissions;

    #[Override]
    public function getAllPermissions(): Collection
    {
        if (isset($this->legacyPermissions)) {
            return $this->legacyPermissions;
        }

        $groups = parent::getAllPermissions();
        $service = Craft::$app->getUserPermissions();

        if (!$service->hasEventHandlers(LegacyUserPermissionsService::EVENT_REGISTER_PERMISSIONS)) {
            return $groups;
        }

        $event = new RegisterUserPermissionsEvent(['permissions' => $groups->values()->toArray()]);
        $service->trigger(LegacyUserPermissionsService::EVENT_REGISTER_PERMISSIONS, $event);

        $groups = collect($event->permissions)->map(fn(array $group, int|string $key) => new PermissionGroup(
            handle: $group['handle'] ?? "yii2-adapter:legacy:$key",
            heading: $group['heading'],
            permissions: $this->keyPermissions($group['permissions']),
        ));

        return $this->legacyPermissions = $groups->values();
    }

    #[Override]
    public function reset(): void
    {
        unset($this->legacyPermissions);
        parent::reset();
    }

    private function keyPermissions(array $permissions): Collection
    {
        return collect($permissions)->map(fn(array $permission, string $key) => new Permission(
            key: $key,
            label: $permission['label'],
            info: $permission['info'] ?? null,
            warning: $permission['warning'] ?? null,
            nested: isset($permission['nested'])
                ? $this->keyPermissions($permission['nested'])
                : collect(),
        ));
    }
}
