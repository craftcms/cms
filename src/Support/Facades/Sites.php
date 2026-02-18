<?php

declare(strict_types=1);

namespace CraftCms\Cms\Support\Facades;

use CraftCms\Cms\ProjectConfig\Events\ConfigEvent;
use CraftCms\Cms\Section\Data\Section;
use CraftCms\Cms\Site\Data\Site;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Facade;

/**
 * @method static bool isMultiSite(bool $refresh = false, bool $withTrashed = false)
 * @method static bool isMultiSiteWithTrashed(bool $refresh = false)
 * @method static \Illuminate\Support\Collection getAllSiteIds(bool|null $withDisabled = null)
 * @method static \CraftCms\Cms\Site\Data\Site getSiteByUid(string $uid, bool|null $withDisabled = null)
 * @method static bool getHasCurrentSite()
 * @method static \CraftCms\Cms\Site\Data\Site getCurrentSite()
 * @method static void setCurrentSite(\CraftCms\Cms\Site\Data\Site|string|int|null $site)
 * @method static \CraftCms\Cms\Site\Data\Site getPrimarySite()
 * @method static \Illuminate\Support\Collection getEditableSiteIds()
 * @method static \Illuminate\Support\Collection getEditableSiteIdsForSection(\CraftCms\Cms\Section\Data\Section $section)
 * @method static \Illuminate\Support\Collection getAllSites(bool|null $withDisabled = null)
 * @method static \Illuminate\Support\Collection getEditableSites()
 * @method static \Illuminate\Support\Collection getSitesByGroupId(int $groupId, bool|null $withDisabled = null)
 * @method static \Illuminate\Support\Collection getEditableSitesByGroupId(int $groupId, bool|null $withDisabled = null)
 * @method static int getTotalSites()
 * @method static int getTotalEditableSites()
 * @method static \CraftCms\Cms\Site\Data\Site|null getSiteById(int $siteId, bool|null $withDisabled = null)
 * @method static \CraftCms\Cms\Site\Data\Site|null getSiteByHandle(string $siteHandle, bool|null $withDisabled = null)
 * @method static \Illuminate\Support\Collection getSitesByLanguage(string $language, bool|null $withDisabled = null)
 * @method static int getRemainingSites()
 * @method static bool saveSite(\CraftCms\Cms\Site\Data\Site $site, bool $runValidation = true)
 * @method static void handleChangedSite(\CraftCms\Cms\ProjectConfig\Events\ConfigEvent $event)
 * @method static bool reorderSites(int[] $siteIds)
 * @method static bool deleteSiteById(int $siteId, int|null $transferContentTo = null)
 * @method static bool deleteSite(\CraftCms\Cms\Site\Data\Site $site, int|null $transferContentTo = null)
 * @method static void handleDeletedSite(\CraftCms\Cms\ProjectConfig\Events\ConfigEvent $event)
 * @method static bool restoreSiteById(int $id)
 * @method static void refreshSites()
 *
 * @see \CraftCms\Cms\Site\Sites
 */
final class Sites extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \CraftCms\Cms\Site\Sites::class;
    }
}
