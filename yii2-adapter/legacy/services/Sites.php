<?php

/**
 * @link https://craftcms.com/
 *
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\services;

use Craft;
use craft\events\DeleteSiteEvent;
use craft\events\ReorderSitesEvent;
use craft\events\SiteEvent;
use craft\events\SiteGroupEvent;
use craft\models\Site as LegacySite;
use craft\models\SiteGroup as LegacySiteGroup;
use CraftCms\Cms\ProjectConfig\Events\ConfigEvent;
use CraftCms\Cms\Shared\Exceptions\NotSupportedException;
use CraftCms\Cms\Site\Data\Site;
use CraftCms\Cms\Site\Data\SiteGroup;
use CraftCms\Cms\Site\Events\ApplyingSiteDelete;
use CraftCms\Cms\Site\Events\ApplyingSiteGroupDelete;
use CraftCms\Cms\Site\Events\DeletedSiteGroup;
use CraftCms\Cms\Site\Events\DeletingSite;
use CraftCms\Cms\Site\Events\DeletingSiteGroup;
use CraftCms\Cms\Site\Events\PrimarySiteChanged;
use CraftCms\Cms\Site\Events\ReorderingSites;
use CraftCms\Cms\Site\Events\SavedSiteGroup;
use CraftCms\Cms\Site\Events\SavingSite;
use CraftCms\Cms\Site\Events\SavingSiteGroup;
use CraftCms\Cms\Site\Events\SiteDeleted;
use CraftCms\Cms\Site\Events\SiteSaved;
use CraftCms\Cms\Site\Events\SitesReordered;
use CraftCms\Cms\Site\Exceptions\SiteNotFoundException;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\Facades\SiteGroups;
use CraftCms\Cms\Support\Facades\Sites as SitesFacade;
use Illuminate\Support\Facades\Event;
use Throwable;
use yii\base\Component;
use yii\base\Exception;
use yii\db\Exception as DbException;

/**
 * Sites service.
 *
 * An instance of the service is available via [[\craft\base\ApplicationTrait::getSites()|`Craft::$app->getSites()`]].
 *
 * @property-read Site[] $allSites all of the sites
 * @property int[] $allSiteIds all of the site IDs
 * @property Site|null $currentSite the current site
 * @property int[] $editableSiteIds all of the site IDs that are editable by the current user
 * @property Site $primarySite the primary site
 * @property int $totalSites the total number of sites
 * @property int $totalEditableSites the total number of sites that are editable by the current user
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 *
 * @since 3.0.0
 * @deprecated 6.0.0 use {@see \CraftCms\Cms\Site\Sites} instead.
 */
class Sites extends Component
{
    /**
     * @event SiteGroupEvent The event that is triggered before a site group is saved.
     */
    public const EVENT_BEFORE_SAVE_SITE_GROUP = 'beforeSaveSiteGroup';

    /**
     * @event SiteGroupEvent The event that is triggered after a site group is saved.
     */
    public const EVENT_AFTER_SAVE_SITE_GROUP = 'afterSaveSiteGroup';

    /**
     * @event SiteGroupEvent The event that is triggered before a site group is deleted.
     */
    public const EVENT_BEFORE_DELETE_SITE_GROUP = 'beforeDeleteSiteGroup';

    /**
     * @event SiteGroupEvent The event that is triggered before a site group delete is applied to the database.
     *
     * @since 3.1.0
     */
    public const EVENT_BEFORE_APPLY_GROUP_DELETE = 'beforeApplyGroupDelete';

    /**
     * @event SiteGroupEvent The event that is triggered after a site group is deleted.
     */
    public const EVENT_AFTER_DELETE_SITE_GROUP = 'afterDeleteSiteGroup';

    /**
     * @event SiteEvent The event that is triggered before a site is saved.
     */
    public const EVENT_BEFORE_SAVE_SITE = 'beforeSaveSite';

    /**
     * @event SiteEvent The event that is triggered after a site is saved.
     */
    public const EVENT_AFTER_SAVE_SITE = 'afterSaveSite';

    /**
     * @event ReorderSitesEvent The event that is triggered before the sites are reordered.
     */
    public const EVENT_BEFORE_REORDER_SITES = 'beforeReorderSites';

    /**
     * @event ReorderSitesEvent The event that is triggered after the sites are reordered.
     */
    public const EVENT_AFTER_REORDER_SITES = 'afterReorderSites';

    /**
     * @event SiteEvent The event that is triggered after the primary site has changed
     */
    public const EVENT_AFTER_CHANGE_PRIMARY_SITE = 'afterChangePrimarySite';

    /**
     * @event DeleteSiteEvent The event that is triggered before a site is deleted.
     *
     * You may set [[\craft\events\CancelableEvent::$isValid]] to `false` to prevent the site from getting deleted.
     */
    public const EVENT_BEFORE_DELETE_SITE = 'beforeDeleteSite';

    /**
     * @event DeleteSiteEvent The event that is triggered before a site delete is applied to the database.
     *
     * @since 3.1.0
     */
    public const EVENT_BEFORE_APPLY_SITE_DELETE = 'beforeApplySiteDelete';

    /**
     * @event DeleteSiteEvent The event that is triggered after a site is deleted.
     */
    public const EVENT_AFTER_DELETE_SITE = 'afterDeleteSite';

    /**
     * This value can be configured as needed, but exists as a safeguard against performance issues.
     *
     * ::: warning
     * Craft’s multi-site support is not designed to be infinitely scalable.
     * Increase this limit at your own risk!
     * :::
     *
     * @var int The maximum number of sites that can be created.
     *
     * @since 5.0.0
     */
    public int $maxSites = 100;

    /**
     * Returns all site groups.
     *
     * @return LegacySiteGroup[] The site groups
     */
    public function getAllGroups(): array
    {
        return SiteGroups::getAllGroups()
            ->map(fn(SiteGroup $group) => self::siteGroupToLegacySiteGroup($group))
            ->all();
    }

    /**
     * Returns a site group by its ID.
     *
     * @param  int  $groupId  The site group’s ID
     * @return SiteGroup|null The site group, or null if it doesn’t exist
     */
    public function getGroupById(int $groupId): ?SiteGroup
    {
        $group = SiteGroups::getGroupById($groupId);

        if (!$group) {
            return null;
        }

        return self::siteGroupToLegacySiteGroup($group);
    }

    /**
     * Returns a site group by its UID.
     *
     * @param  string  $uid  The site group’s UID
     * @return SiteGroup|null The site group, or null if it doesn’t exist
     *
     * @since 3.5.8
     */
    public function getGroupByUid(string $uid): ?SiteGroup
    {
        $group = SiteGroups::getGroupByUid($uid);

        if (!$group) {
            return null;
        }

        return self::siteGroupToLegacySiteGroup($group);
    }

    /**
     * Saves a site group.
     *
     * @param  LegacySiteGroup  $group  The site group to be saved
     * @param  bool  $runValidation  Whether the group should be validated
     * @return bool Whether the site group was saved successfully
     */
    public function saveGroup(LegacySiteGroup $group, bool $runValidation = true): bool
    {
        $group = new SiteGroup($group->toArray());

        return SiteGroups::saveGroup($group, $runValidation);
    }

    /**
     * Handle site group change
     */
    public function handleChangedGroup(ConfigEvent $event): void
    {
        SiteGroups::handleChangedGroup($event);
    }

    /**
     * Handle site group getting deleted.
     */
    public function handleDeletedGroup(ConfigEvent $event): void
    {
        SiteGroups::handleDeletedGroup($event);
    }

    /**
     * Deletes a site group by its ID.
     *
     * @param  int  $groupId  The site group’s ID
     * @return bool Whether the site group was deleted successfully
     */
    public function deleteGroupById(int $groupId): bool
    {
        return SiteGroups::deleteGroupById($groupId);
    }

    /**
     * Deletes a site group.
     *
     * @param  LegacySiteGroup  $group  The site group
     * @return bool Whether the site group was deleted successfully
     */
    public function deleteGroup(LegacySiteGroup $group): bool
    {
        return SiteGroups::deleteGroupById($group->id);
    }

    // Sites
    // -------------------------------------------------------------------------

    /**
     * Returns all of the site IDs.
     *
     *
     * @return int[] All the sites’ IDs
     */
    public function getAllSiteIds(?bool $withDisabled = null): array
    {
        return SitesFacade::getAllSiteIds($withDisabled)->all();
    }

    /**
     * Returns a site by it's UID.
     *
     *
     * @return Site the site
     *
     * @throws SiteNotFoundException if no sites exist
     */
    public function getSiteByUid(string $uid, ?bool $withDisabled = null): Site
    {
        $site = SitesFacade::getSiteByUid($uid, $withDisabled);

        return self::siteToLegacySite($site);
    }

    /**
     * Returns whether the current site has been set yet.
     */
    public function getHasCurrentSite(): bool
    {
        return SitesFacade::getHasCurrentSite();
    }

    /**
     * Returns the current site.
     *
     * > [!NOTE]
     * > This will always return the primary site for control panel requests. To fetch the site the control panel
     * > is currently working with, based on the `site` query string param, use [[\craft\helpers\Cp::requestedSite()]].
     *
     * @return Site the current site
     *
     * @throws SiteNotFoundException if no sites exist
     */
    public function getCurrentSite(): Site
    {
        return self::siteToLegacySite(SitesFacade::getCurrentSite());
    }

    /**
     * Sets the current site.
     *
     * @param  LegacySite|string|int|null  $site  the current site, or its handle/ID, or null
     */
    public function setCurrentSite(mixed $site): void
    {
        if ($site instanceof LegacySite) {
            $site = $site->id;
        }

        SitesFacade::setCurrentSite($site);
    }

    /**
     * Returns the primary site. The primary site is whatever is listed first in Settings > Sites in the
     * control panel.
     *
     * @return Site The primary site
     *
     * @throws SiteNotFoundException if no sites exist
     */
    public function getPrimarySite(): Site
    {
        return self::siteToLegacySite(SitesFacade::getPrimarySite());
    }

    /**
     * Returns all of the site IDs that are editable by the current user.
     *
     * @return array All the editable sites’ IDs
     */
    public function getEditableSiteIds(): array
    {
        return SitesFacade::getEditableSiteIds()->all();
    }

    /**
     * Returns all sites.
     *
     *
     * @return LegacySite[] All the sites
     */
    public function getAllSites(?bool $withDisabled = null): array
    {
        return SitesFacade::getAllSites($withDisabled)
            ->map(fn(Site $site) => self::siteToLegacySite($site))
            ->all();
    }

    /**
     * Returns all editable sites.
     *
     * @return LegacySite[] All the editable sites
     */
    public function getEditableSites(): array
    {
        return SitesFacade::getEditableSites()
            ->map(fn(Site $site) => self::siteToLegacySite($site))
            ->all();
    }

    /**
     * Returns sites by a group ID.
     *
     *
     * @return LegacySite[]
     */
    public function getSitesByGroupId(int $groupId, ?bool $withDisabled = null): array
    {
        return SitesFacade::getSitesByGroupId($groupId, $withDisabled)
            ->map(fn(Site $site) => self::siteToLegacySite($site))
            ->all();
    }

    /**
     * Returns editable sites by a group ID.
     *
     *
     * @return LegacySite[]
     *
     * @since 5.4.0
     */
    public function getEditableSitesByGroupId(int $groupId, ?bool $withDisabled = null): array
    {
        return SitesFacade::getEditableSitesByGroupId($groupId, $withDisabled)
            ->map(fn(Site $site) => self::siteToLegacySite($site))
            ->all();
    }

    /**
     * Gets the total number of sites.
     */
    public function getTotalSites(): int
    {
        return SitesFacade::getTotalSites();
    }

    /**
     * Gets the total number of sites that are editable by the current user.
     */
    public function getTotalEditableSites(): int
    {
        return SitesFacade::getTotalEditableSites();
    }

    /**
     * Returns a site by its ID.
     */
    public function getSiteById(int $siteId, ?bool $withDisabled = null): ?LegacySite
    {
        $site = SitesFacade::getSiteById($siteId, $withDisabled);

        if (!$site) {
            return null;
        }

        return self::siteToLegacySite($site);
    }

    /**
     * Returns a site by its handle.
     */
    public function getSiteByHandle(string $siteHandle, ?bool $withDisabled = null): ?LegacySite
    {
        $site = SitesFacade::getSiteByHandle($siteHandle, $withDisabled);

        if (!$site) {
            return null;
        }

        return self::siteToLegacySite($site);
    }

    /**
     * Returns sites by their language.
     *
     *
     * @return LegacySite[]
     *
     * @since 4.9.0
     */
    public function getSitesByLanguage(string $language, ?bool $withDisabled = null): array
    {
        return SitesFacade::getSitesByLanguage($language, $withDisabled)
            ->map(fn(Site $site) => self::siteToLegacySite($site))
            ->all();
    }

    /**
     * Returns the number of sites that can be created, based on [[$maxSites]].
     *
     * @see $maxSites
     * @since 5.0.0
     */
    public function getRemainingSites(): int
    {
        return SitesFacade::getRemainingSites();
    }

    /**
     * Saves a site.
     *
     * @param  LegacySite  $site  The site to be saved
     * @param  bool  $runValidation  Whether the site should be validated
     *
     * @throws SiteNotFoundException if $site->id is invalid
     * @throws Throwable if reasons
     */
    public function saveSite(LegacySite $site, bool $runValidation = true): bool
    {
        $site = new Site($site->toArray());

        return SitesFacade::saveSite($site, $runValidation);
    }

    /**
     * Handle site changes.
     *
     *
     * @throws Throwable
     */
    public function handleChangedSite(ConfigEvent $event): void
    {
        SitesFacade::handleChangedSite($event);
    }

    /**
     * Reorders sites.
     *
     * @param  int[]  $siteIds  The site IDs in their new order
     * @return bool Whether the sites were reordered successfully
     *
     * @throws Throwable if reasons
     */
    public function reorderSites(array $siteIds): bool
    {
        return SitesFacade::reorderSites($siteIds);
    }

    /**
     * Deletes a site by its ID.
     *
     * @param  int  $siteId  The site ID to be deleted
     * @param  int|null  $transferContentTo  The site ID that should take over the deleted site’s contents
     * @return bool Whether the site was deleted successfully
     *
     * @throws Throwable if reasons
     */
    public function deleteSiteById(int $siteId, ?int $transferContentTo = null): bool
    {
        return SitesFacade::deleteSiteById($siteId, $transferContentTo);
    }

    /**
     * Deletes a site.
     *
     * @param  Site  $site  The site to be deleted
     * @param  int|null  $transferContentTo  The site ID that should take over the deleted site’s contents
     * @return bool Whether the site was deleted successfully
     *
     * @throws Exception if $site is the primary site
     * @throws Throwable if reasons
     */
    public function deleteSite(Site $site, ?int $transferContentTo = null): bool
    {
        return SitesFacade::deleteSiteById($site->id, $transferContentTo);
    }

    /**
     * Handle a deleted Site.
     *
     *
     * @throws DbException
     * @throws Throwable
     * @throws NotSupportedException
     */
    public function handleDeletedSite(ConfigEvent $event): void
    {
        SitesFacade::handleDeletedSite($event);
    }

    /**
     * Restores a site by its ID.
     *
     * @param  int  $id  The site’s ID
     * @return bool Whether the site was restored successfully
     *
     * @since 3.1.0
     */
    public function restoreSiteById(int $id): bool
    {
        return SitesFacade::restoreSiteById($id);
    }

    /**
     * Refresh the status of all sites based on the DB data.
     *
     * @throws DbException
     *
     * @since 3.5.13
     */
    public function refreshSites(): void
    {
        SitesFacade::refreshSites();
    }

    public static function registerEvents(): void
    {
        Event::listen(SavingSiteGroup::class, function(SavingSiteGroup $event) {
            if (Craft::$app->getSites()->hasEventHandlers(self::EVENT_BEFORE_SAVE_SITE_GROUP)) {
                Craft::$app->getSites()->trigger(self::EVENT_BEFORE_SAVE_SITE_GROUP, $yiiEvent = new SiteGroupEvent([
                    'group' => self::siteGroupToLegacySiteGroup($event->siteGroup),
                    'isNew' => $event->isNew,
                ]));

                $event->siteGroup = new SiteGroup($yiiEvent->group->toArray());
            }
        });

        Event::listen(SavedSiteGroup::class, function(SavedSiteGroup $event) {
            if (Craft::$app->getSites()->hasEventHandlers(self::EVENT_AFTER_SAVE_SITE_GROUP)) {
                Craft::$app->getSites()->trigger(self::EVENT_AFTER_SAVE_SITE_GROUP, new SiteGroupEvent([
                    'group' => self::siteGroupToLegacySiteGroup($event->siteGroup),
                    'isNew' => $event->isNew,
                ]));
            }
        });

        Event::listen(ApplyingSiteGroupDelete::class, function(ApplyingSiteGroupDelete $event) {
            if (Craft::$app->getSites()->hasEventHandlers(self::EVENT_BEFORE_APPLY_GROUP_DELETE)) {
                Craft::$app->getSites()->trigger(self::EVENT_BEFORE_APPLY_GROUP_DELETE, new SiteGroupEvent([
                    'group' => self::siteGroupToLegacySiteGroup($event->siteGroup),
                ]));
            }
        });

        Event::listen(DeletingSiteGroup::class, function(DeletingSiteGroup $event) {
            if (Craft::$app->getSites()->hasEventHandlers(self::EVENT_BEFORE_DELETE_SITE_GROUP)) {
                Craft::$app->getSites()->trigger(self::EVENT_BEFORE_DELETE_SITE_GROUP, new SiteGroupEvent([
                    'group' => self::siteGroupToLegacySiteGroup($event->siteGroup),
                ]));
            }
        });

        Event::listen(DeletedSiteGroup::class, function(DeletedSiteGroup $event) {
            if (Craft::$app->getSites()->hasEventHandlers(self::EVENT_AFTER_DELETE_SITE_GROUP)) {
                Craft::$app->getSites()->trigger(self::EVENT_AFTER_DELETE_SITE_GROUP, new SiteGroupEvent([
                    'group' => self::siteGroupToLegacySiteGroup($event->siteGroup),
                ]));
            }
        });

        Event::listen(SavingSite::class, function(SavingSite $event) {
            if (Craft::$app->getSites()->hasEventHandlers(self::EVENT_BEFORE_SAVE_SITE)) {
                Craft::$app->getSites()->trigger(self::EVENT_BEFORE_SAVE_SITE, $yiiEvent = new SiteEvent([
                    'site' => self::siteToLegacySite($event->site),
                    'isNew' => $event->isNew,
                    'oldPrimarySiteId' => $event->oldPrimarySiteId,
                ]));

                $event->site = new Site($yiiEvent->site->toArray());
            }
        });

        Event::listen(SiteSaved::class, function(SiteSaved $event) {
            if (Craft::$app->getSites()->hasEventHandlers(self::EVENT_AFTER_SAVE_SITE)) {
                Craft::$app->getSites()->trigger(self::EVENT_AFTER_SAVE_SITE, new SiteEvent([
                    'site' => self::siteToLegacySite($event->site),
                    'isNew' => $event->isNew,
                    'oldPrimarySiteId' => $event->oldPrimarySiteId,
                ]));
            }
        });

        Event::listen(ReorderingSites::class, function(ReorderingSites $event) {
            if (Craft::$app->getSites()->hasEventHandlers(self::EVENT_BEFORE_REORDER_SITES)) {
                Craft::$app->getSites()->trigger(self::EVENT_BEFORE_REORDER_SITES, new ReorderSitesEvent([
                    'siteIds' => $event->siteIds,
                ]));
            }
        });

        Event::listen(SitesReordered::class, function(SitesReordered $event) {
            if (Craft::$app->getSites()->hasEventHandlers(self::EVENT_AFTER_REORDER_SITES)) {
                Craft::$app->getSites()->trigger(self::EVENT_AFTER_REORDER_SITES, new ReorderSitesEvent([
                    'siteIds' => $event->siteIds,
                ]));
            }
        });

        Event::listen(DeletingSite::class, function(DeletingSite $event) {
            if (Craft::$app->getSites()->hasEventHandlers(self::EVENT_BEFORE_DELETE_SITE)) {
                Craft::$app->getSites()->trigger(self::EVENT_BEFORE_DELETE_SITE, $yiiEvent = new DeleteSiteEvent([
                    'site' => self::siteToLegacySite($event->site),
                    'transferContentTo' => $event->transferContentTo,
                ]));

                $event->site = new Site($yiiEvent->site->toArray());
                $event->transferContentTo = $yiiEvent->transferContentTo;
                $event->isValid = $yiiEvent->isValid;
            }
        });

        Event::listen(ApplyingSiteDelete::class, function(ApplyingSiteDelete $event) {
            if (Craft::$app->getSites()->hasEventHandlers(self::EVENT_BEFORE_APPLY_SITE_DELETE)) {
                Craft::$app->getSites()->trigger(self::EVENT_BEFORE_APPLY_SITE_DELETE, new DeleteSiteEvent([
                    'site' => self::siteToLegacySite($event->site),
                ]));
            }
        });

        Event::listen(SiteDeleted::class, function(SiteDeleted $event) {
            if (Craft::$app->getSites()->hasEventHandlers(self::EVENT_AFTER_DELETE_SITE)) {
                Craft::$app->getSites()->trigger(self::EVENT_AFTER_DELETE_SITE, new DeleteSiteEvent([
                    'site' => self::siteToLegacySite($event->site),
                ]));
            }
        });

        Event::listen(PrimarySiteChanged::class, function(PrimarySiteChanged $event) {
            if (Craft::$app->getSites()->hasEventHandlers(self::EVENT_AFTER_CHANGE_PRIMARY_SITE)) {
                Craft::$app->getSites()->trigger(self::EVENT_AFTER_CHANGE_PRIMARY_SITE, new SiteEvent([
                    'site' => self::siteToLegacySite($event->site),
                ]));
            }
        });
    }

    private static function siteGroupToLegacySiteGroup(SiteGroup $siteGroup): LegacySiteGroup
    {
        return new LegacySiteGroup(Arr::except($siteGroup->toArray(), ['rawName']));
    }

    private static function siteToLegacySite(Site $site): LegacySite
    {
        return new LegacySite(Arr::except($site->toArray(), [
            'nameRaw',
            'uiLabel',
            'languageRaw',
            'locale',
            'baseUrlRaw',
            'enabledRaw',
            'group',
        ]));
    }
}
