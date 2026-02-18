<?php

declare(strict_types=1);

namespace CraftCms\Cms\Support\Facades;

use CraftCms\Cms\ProjectConfig\Events\ConfigEvent;
use CraftCms\Cms\User\Data\UserGroup;
use CraftCms\Cms\User\Elements\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Facade;
use Override;

/**
 * @method static \Illuminate\Support\Collection getAllGroups()
 * @method static \Illuminate\Support\Collection getAssignableGroups(\CraftCms\Cms\User\Elements\User|null $user = null)
 * @method static \CraftCms\Cms\User\Data\UserGroup|null getGroupById(int $groupId)
 * @method static \CraftCms\Cms\User\Data\UserGroup|null getGroupByUid(string $uid)
 * @method static \CraftCms\Cms\User\Data\UserGroup|null getGroupByHandle(string $groupHandle)
 * @method static \CraftCms\Cms\User\Data\UserGroup getTeamGroup()
 * @method static \Illuminate\Support\Collection getGroupsByUserId(int $userId)
 * @method static void eagerLoadGroups(\CraftCms\Cms\User\Elements\User[] $users)
 * @method static bool saveGroup(\CraftCms\Cms\User\Data\UserGroup $group)
 * @method static void handleChangedUserGroup(\CraftCms\Cms\ProjectConfig\Events\ConfigEvent $event)
 * @method static void handleDeletedUserGroup(\CraftCms\Cms\ProjectConfig\Events\ConfigEvent $event)
 * @method static bool deleteGroupById(int $groupId)
 * @method static bool deleteGroup(\CraftCms\Cms\User\Data\UserGroup $group)
 *
 * @see \CraftCms\Cms\User\UserGroups
 */
final class UserGroups extends Facade
{
    #[Override]
    protected static function getFacadeAccessor(): string
    {
        return \CraftCms\Cms\User\UserGroups::class;
    }
}
