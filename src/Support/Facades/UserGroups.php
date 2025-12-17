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
 * @method static Collection getAllGroups()
 * @method static Collection getAssignableGroups(User|null $user = null)
 * @method static UserGroup|null getGroupById(int $groupId)
 * @method static UserGroup|null getGroupByUid(string $uid)
 * @method static UserGroup|null getGroupByHandle(string $groupHandle)
 * @method static UserGroup getTeamGroup()
 * @method static Collection getGroupsByUserId(int $userId)
 * @method static void eagerLoadGroups(User[] $users)
 * @method static bool saveGroup(UserGroup $group)
 * @method static void handleChangedUserGroup(ConfigEvent $event)
 * @method static void handleDeletedUserGroup(ConfigEvent $event)
 * @method static bool deleteGroupById(int $groupId)
 * @method static bool deleteGroup(UserGroup $group)
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
