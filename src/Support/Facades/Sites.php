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
 * @method static Collection getAllSiteIds(bool|null $withDisabled = null)
 * @method static Site getSiteByUid(string $uid, bool|null $withDisabled = null)
 * @method static bool getHasCurrentSite()
 * @method static Site getCurrentSite()
 * @method static void setCurrentSite(Site|string|int|null $site)
 * @method static Site getPrimarySite()
 * @method static Collection getEditableSiteIds()
 * @method static Collection getEditableSiteIdsForSection(Section $section)
 * @method static Collection getAllSites(bool|null $withDisabled = null)
 * @method static Collection getEditableSites()
 * @method static Collection getSitesByGroupId(int $groupId, bool|null $withDisabled = null)
 * @method static Collection getEditableSitesByGroupId(int $groupId, bool|null $withDisabled = null)
 * @method static int getTotalSites()
 * @method static int getTotalEditableSites()
 * @method static Site|null getSiteById(int $siteId, bool|null $withDisabled = null)
 * @method static Site|null getSiteByHandle(string $siteHandle, bool|null $withDisabled = null)
 * @method static Collection getSitesByLanguage(string $language, bool|null $withDisabled = null)
 * @method static int getRemainingSites()
 * @method static bool saveSite(Site $site, bool $runValidation = true)
 * @method static void handleChangedSite(ConfigEvent $event)
 * @method static bool reorderSites(int[] $siteIds)
 * @method static bool deleteSiteById(int $siteId, int|null $transferContentTo = null)
 * @method static bool deleteSite(Site $site, int|null $transferContentTo = null)
 * @method static void handleDeletedSite(ConfigEvent $event)
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
