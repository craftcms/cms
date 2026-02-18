<?php

declare(strict_types=1);

namespace CraftCms\Cms\Support\Facades;

use CraftCms\Cms\ProjectConfig\Events\ConfigEvent;
use CraftCms\Cms\User\Elements\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Facade;
use Override;

/**
 * @method static Collection getAllPermissions()
 * @method static Collection getAssignablePermissions(User|null $user = null)
 * @method static Collection getPermissionsByGroupId(int $groupId)
 * @method static Collection getGroupPermissionsByUserId(int $userId)
 * @method static bool doesGroupHavePermission(int $groupId, string $checkPermission)
 * @method static bool saveGroupPermissions(int $groupId, array $permissions)
 * @method static Collection getPermissionsByUserId(int $userId)
 * @method static bool validatePermission(string $permission)
 * @method static bool doesUserHavePermission(int $userId, string $checkPermission)
 * @method static bool saveUserPermissions(int $userId, array $permissions)
 * @method static void handleChangedGroupPermissions(ConfigEvent $event)
 * @method static void reset()
 *
 * @see \CraftCms\Cms\User\UserPermissions
 */
final class UserPermissions extends Facade
{
    #[Override]
    protected static function getFacadeAccessor(): string
    {
        return \CraftCms\Cms\User\UserPermissions::class;
    }
}
