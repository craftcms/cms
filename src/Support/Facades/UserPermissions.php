<?php

declare(strict_types=1);

namespace CraftCms\Cms\Support\Facades;

use Illuminate\Support\Facades\Facade;
use Override;

/**
 * @method static \Illuminate\Support\Collection<\CraftCms\Cms\User\Data\PermissionGroup> getAllPermissions()
 * @method static \Illuminate\Support\Collection<\CraftCms\Cms\User\Data\PermissionGroup> getAssignablePermissions(\CraftCms\Cms\User\Elements\User|null $user = null)
 * @method static \Illuminate\Support\Collection<string> getPermissionsByGroupId(int $groupId)
 * @method static \Illuminate\Support\Collection<string> getGroupPermissionsByUserId(int $userId)
 * @method static bool doesGroupHavePermission(int $groupId, string $checkPermission)
 * @method static bool saveGroupPermissions(int $groupId, array $permissions)
 * @method static \Illuminate\Support\Collection<string> getPermissionsByUserId(int $userId)
 * @method static bool validatePermission(string $permission)
 * @method static bool doesUserHavePermission(int $userId, string $checkPermission)
 * @method static bool saveUserPermissions(int $userId, array $permissions)
 * @method static void handleChangedGroupPermissions(\CraftCms\Cms\ProjectConfig\Events\ConfigEvent $event)
 * @method static void reset()
 *
 * @see \CraftCms\Cms\User\UserPermissions
 */
class UserPermissions extends Facade
{
    #[Override]
    protected static function getFacadeAccessor(): string
    {
        return \CraftCms\Cms\User\UserPermissions::class;
    }
}
