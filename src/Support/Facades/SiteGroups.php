<?php

declare(strict_types=1);

namespace CraftCms\Cms\Support\Facades;

use CraftCms\Cms\ProjectConfig\Events\ConfigEvent;
use CraftCms\Cms\Site\Data\SiteGroup;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Facade;

/**
 * @method static Collection getAllGroups()
 * @method static SiteGroup|null getGroupById(int $groupId)
 * @method static SiteGroup|null getGroupByUid(string $uid)
 * @method static bool saveGroup(SiteGroup $group, bool $runValidation = true)
 * @method static void handleChangedGroup(ConfigEvent $event)
 * @method static void handleDeletedGroup(ConfigEvent $event)
 * @method static bool deleteGroupById(int $groupId)
 * @method static bool deleteGroup(SiteGroup $group)
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
