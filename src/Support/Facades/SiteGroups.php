<?php

declare(strict_types=1);

namespace CraftCms\Cms\Support\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static \Illuminate\Support\Collection getAllGroups()
 * @method static \CraftCms\Cms\Site\Data\SiteGroup|null getGroupById(int $groupId)
 * @method static \CraftCms\Cms\Site\Data\SiteGroup|null getGroupByUid(string $uid)
 * @method static bool saveGroup(\CraftCms\Cms\Site\Data\SiteGroup $group, bool $runValidation = true)
 * @method static void handleChangedGroup(\CraftCms\Cms\ProjectConfig\Events\ConfigEvent $event)
 * @method static void handleDeletedGroup(\CraftCms\Cms\ProjectConfig\Events\ConfigEvent $event)
 * @method static bool deleteGroupById(int $groupId)
 * @method static bool deleteGroup(\CraftCms\Cms\Site\Data\SiteGroup $group)
 * @method static void refreshGroups()
 *
 * @see \CraftCms\Cms\Site\SiteGroups
 */
final class SiteGroups extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \CraftCms\Cms\Site\SiteGroups::class;
    }
}
