<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\services;

use Craft;
use craft\base\ElementInterface;
use craft\base\MemoizableArray;
use craft\db\Query;
use craft\db\Table;
use craft\elements\Asset;
use craft\elements\Category;
use craft\elements\GlobalSet;
use craft\elements\Tag;
use craft\errors\SiteNotFoundException;
use craft\events\ConfigEvent;
use craft\events\DeleteSiteEvent;
use craft\events\ReorderSitesEvent;
use craft\events\SiteEvent;
use craft\events\SiteGroupEvent;
use craft\helpers\App;
use craft\helpers\ArrayHelper;
use craft\helpers\Db;
use craft\helpers\Queue;
use craft\helpers\StringHelper;
use craft\models\Site;
use craft\models\SiteGroup;
use craft\queue\jobs\PropagateElements;
use craft\records\Site as SiteRecord;
use craft\records\SiteGroup as SiteGroupRecord;
use yii\base\Component;
use yii\base\Exception;
use yii\base\InvalidArgumentException;
use yii\db\Exception as DbException;

/**
 * Sites service.
 *
 * An instance of the service is available via [[\craft\base\ApplicationTrait::getSites()|`Craft::$app->sites`]].
 *
 * @property-read Site[] $allSites all of the sites
 * @property int[] $allSiteIds all of the site IDs
 * @property Site|null $currentSite the current site
 * @property int[] $editableSiteIds all of the site IDs that are editable by the current user
 * @property Site $primarySite the primary site
 * @property int $totalSites the total number of sites
 * @property int $totalEditableSites the total number of sites that are editable by the current user
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class Sites extends Component
{
    /**
     * @event SiteGroupEvent The event that is triggered before a site group is saved.
     */
    const EVENT_BEFORE_SAVE_SITE_GROUP = 'beforeSaveSiteGroup';

    /**
     * @event SiteGroupEvent The event that is triggered after a site group is saved.
     */
    const EVENT_AFTER_SAVE_SITE_GROUP = 'afterSaveSiteGroup';

    /**
     * @event SiteGroupEvent The event that is triggered before a site group is deleted.
     */
    const EVENT_BEFORE_DELETE_SITE_GROUP = 'beforeDeleteSiteGroup';

    /**
     * @event SiteGroupEvent The event that is triggered before a site group delete is applied to the database.
     * @since 3.1.0
     */
    const EVENT_BEFORE_APPLY_GROUP_DELETE = 'beforeApplyGroupDelete';

    /**
     * @event SiteGroupEvent The event that is triggered after a site group is deleted.
     */
    const EVENT_AFTER_DELETE_SITE_GROUP = 'afterDeleteSiteGroup';

    /**
     * @event SiteEvent The event that is triggered before a site is saved.
     */
    const EVENT_BEFORE_SAVE_SITE = 'beforeSaveSite';

    /**
     * @event SiteEvent The event that is triggered after a site is saved.
     */
    const EVENT_AFTER_SAVE_SITE = 'afterSaveSite';

    /**
     * @event ReorderSitesEvent The event that is triggered before the sites are reordered.
     */
    const EVENT_BEFORE_REORDER_SITES = 'beforeReorderSites';

    /**
     * @event ReorderSitesEvent The event that is triggered after the sites are reordered.
     */
    const EVENT_AFTER_REORDER_SITES = 'afterReorderSites';

    /**
     * @event SiteEvent The event that is triggered after the primary site has changed
     */
    const EVENT_AFTER_CHANGE_PRIMARY_SITE = 'afterChangePrimarySite';

    /**
     * @event DeleteSiteEvent The event that is triggered before a site is deleted.
     *
     * You may set [[\craft\events\CancelableEvent::$isValid]] to `false` to prevent the site from getting deleted.
     */
    const EVENT_BEFORE_DELETE_SITE = 'beforeDeleteSite';

    /**
     * @event DeleteSiteEvent The event that is triggered before a site delete is applied to the database.
     * @since 3.1.0
     */
    const EVENT_BEFORE_APPLY_SITE_DELETE = 'beforeApplySiteDelete';

    /**
     * @event DeleteSiteEvent The event that is triggered after a site is deleted.
     */
    const EVENT_AFTER_DELETE_SITE = 'afterDeleteSite';

    const CONFIG_SITEGROUP_KEY = 'siteGroups';
    const CONFIG_SITES_KEY = 'sites';

    /**
     * @var MemoizableArray<SiteGroup>|null
     * @see _groups()
     */
    private $_groups;

    /**
     * @var int[]|null
     * @see getEditableSiteIds()
     */
    private $_editableSiteIds;

    /**
     * @var Site[]
     * @see getSiteById()
     */
    private $_allSitesById;

    /**
     * @var Site[]
     * @see getSiteById()
     */
    private $_enabledSitesById;

    /**
     * @var Site|null the current site
     * @see getCurrentSite()
     * @see setCurrentSite()
     */
    private $_currentSite;

    /**
     * @var Site|null
     * @see getPrimarySite()
     */
    private $_primarySite;

    /**
     * Serializer
     *
     * @since 3.5.14
     */
    public function __serialize()
    {
        $vars = get_object_vars($this);
        unset($vars['_groups']);
        return $vars;
    }

    /**
     * @inheritdoc
     */
    public function init()
    {
        // No technical reason to put this here, but it's sortof related
        if (defined('CRAFT_LOCALE')) {
            Craft::$app->getDeprecator()->log('CRAFT_LOCALE', 'The `CRAFT_LOCALE` constant has been deprecated. Use `CRAFT_SITE` instead, which can be set to a site ID or handle.');
        }

        // Load all the sites up front
        $this->_loadAllSites();
    }

    // Groups
    // -------------------------------------------------------------------------

    /**
     * Returns a memoizable array of all site groups.
     *
     * @return MemoizableArray<SiteGroup>
     */
    private function _groups(): MemoizableArray
    {
        if ($this->_groups === null) {
            $groups = [];
            foreach ($this->_createGroupQuery()->all() as $result) {
                $groups[] = new SiteGroup($result);
            }
            $this->_groups = new MemoizableArray($groups);
        }

        return $this->_groups;
    }

    /**
     * Returns all site groups.
     *
     * @return SiteGroup[] The site groups
     */
    public function getAllGroups(): array
    {
        return $this->_groups()->all();
    }

    /**
     * Returns a site group by its ID.
     *
     * @param int $groupId The site group’s ID
     * @return SiteGroup|null The site group, or null if it doesn’t exist
     */
    public function getGroupById(int $groupId)
    {
        return $this->_groups()->firstWhere('id', $groupId);
    }

    /**
     * Returns a site group by its UID.
     *
     * @param string $uid The site group’s UID
     * @return SiteGroup|null The site group, or null if it doesn’t exist
     * @since 3.5.8
     */
    public function getGroupByUid(string $uid)
    {
        return $this->_groups()->firstWhere('uid', $uid, true);
    }

    /**
     * Saves a site group.
     *
     * @param SiteGroup $group The site group to be saved
     * @param bool $runValidation Whether the group should be validated
     * @return bool Whether the site group was saved successfully
     */
    public function saveGroup(SiteGroup $group, bool $runValidation = true): bool
    {
        $isNewGroup = !$group->id;

        // Fire a 'beforeSaveSiteGroup' event
        if ($this->hasEventHandlers(self::EVENT_BEFORE_SAVE_SITE_GROUP)) {
            $this->trigger(self::EVENT_BEFORE_SAVE_SITE_GROUP, new SiteGroupEvent([
                'group' => $group,
                'isNew' => $isNewGroup,
            ]));
        }

        if ($runValidation && !$group->validate()) {
            Craft::info('Site group not saved due to validation error.', __METHOD__);
            return false;
        }

        if ($isNewGroup) {
            $group->uid = StringHelper::UUID();
        } elseif (!$group->uid) {
            $group->uid = Db::uidById(Table::SITEGROUPS, $group->id);
        }

        $configPath = self::CONFIG_SITEGROUP_KEY . '.' . $group->uid;
        $configData = $group->getConfig();
        Craft::$app->getProjectConfig()->set($configPath, $configData, "Save the “{$group->getName(false)}” site group");

        // Now that we have an ID, save it on the model
        if ($isNewGroup) {
            $group->id = Db::idByUid(Table::SITEGROUPS, $group->uid);
        }

        return true;
    }

    /**
     * Handle site group change
     *
     * @param ConfigEvent $event
     */
    public function handleChangedGroup(ConfigEvent $event)
    {
        $data = $event->newValue;
        $uid = $event->tokenMatches[0];

        $groupRecord = $this->_getGroupRecord($uid, true);
        $isNewGroup = $groupRecord->getIsNewRecord();

        // If this is a new group, set the UID we want.
        if (!$groupRecord->id) {
            $groupRecord->uid = $uid;
        }

        $groupRecord->name = $data['name'];

        if ($groupRecord->dateDeleted) {
            $groupRecord->restore();
        } else {
            $groupRecord->save(false);
        }

        // Clear caches
        $this->_groups = null;

        // Fire an 'afterSaveSiteGroup' event
        if ($this->hasEventHandlers(self::EVENT_AFTER_SAVE_SITE_GROUP)) {
            $this->trigger(self::EVENT_AFTER_SAVE_SITE_GROUP, new SiteGroupEvent([
                'group' => $this->getGroupById($groupRecord->id),
                'isNew' => $isNewGroup,
            ]));
        }
    }

    /**
     * Handle site group getting deleted.
     *
     * @param ConfigEvent $event
     */
    public function handleDeletedGroup(ConfigEvent $event)
    {
        $uid = $event->tokenMatches[0];
        $groupRecord = $this->_getGroupRecord($uid);

        if (!$groupRecord->id) {
            return;
        }

        $group = $this->getGroupById($groupRecord->id);

        // Fire a 'beforeApplyGroupDelete' event
        if ($this->hasEventHandlers(self::EVENT_BEFORE_APPLY_GROUP_DELETE)) {
            $this->trigger(self::EVENT_BEFORE_APPLY_GROUP_DELETE, new SiteGroupEvent([
                'group' => $group,
            ]));
        }

        $groupRecord->softDelete();

        // Clear caches
        $this->_groups = null;

        // Fire an 'afterDeleteSiteGroup' event
        if ($this->hasEventHandlers(self::EVENT_AFTER_DELETE_SITE_GROUP)) {
            $this->trigger(self::EVENT_AFTER_DELETE_SITE_GROUP, new SiteGroupEvent([
                'group' => $group,
            ]));
        }
    }

    /**
     * Deletes a site group by its ID.
     *
     * @param int $groupId The site group’s ID
     * @return bool Whether the site group was deleted successfully
     */
    public function deleteGroupById(int $groupId): bool
    {
        $group = $this->getGroupById($groupId);

        if (!$group) {
            return false;
        }

        return $this->deleteGroup($group);
    }

    /**
     * Deletes a site group.
     *
     * @param SiteGroup $group The site group
     * @return bool Whether the site group was deleted successfully
     */
    public function deleteGroup(SiteGroup $group): bool
    {
        if ($this->getSitesByGroupId($group->id)) {
            Craft::warning('Attempted to delete a site group that still had sites assigned to it.', __METHOD__);
            return false;
        }

        /** @var SiteGroupRecord $groupRecord */
        $groupRecord = SiteGroupRecord::find()
            ->where(['id' => $group->id])
            ->one();

        if (!$groupRecord) {
            return false;
        }

        // Fire a 'beforeDeleteSiteGroup' event
        if ($this->hasEventHandlers(self::EVENT_BEFORE_DELETE_SITE_GROUP)) {
            $this->trigger(self::EVENT_BEFORE_DELETE_SITE_GROUP, new SiteGroupEvent([
                'group' => $group,
            ]));
        }

        Craft::$app->getProjectConfig()->remove(self::CONFIG_SITEGROUP_KEY . '.' . $group->uid, "Delete the “{$group->getName(false)}” site group");
        return true;
    }

    // Sites
    // -------------------------------------------------------------------------

    /**
     * Returns all of the site IDs.
     *
     * @param bool|null $withDisabled
     * @return int[] All the sites’ IDs
     */
    public function getAllSiteIds(bool $withDisabled = null): array
    {
        return ArrayHelper::getColumn($this->_allSites($withDisabled), 'id', false);
    }

    /**
     * Returns a site by it's UID.
     *
     * @param string $uid
     * @param bool|null $withDisabled
     * @return Site the site
     * @throws SiteNotFoundException if no sites exist
     */
    public function getSiteByUid(string $uid, bool $withDisabled = null): Site
    {
        $site = ArrayHelper::firstWhere($this->_allSites($withDisabled), 'uid', $uid, true);
        if ($site === null) {
            throw new SiteNotFoundException('Site with UID ”' . $uid . '“ not found!');
        }
        return $site;
    }

    /**
     * Returns whether the current site has been set yet.
     *
     * @return bool
     */
    public function getHasCurrentSite(): bool
    {
        return $this->_currentSite !== null;
    }

    /**
     * Returns the current site.
     *
     * @return Site the current site
     * @throws SiteNotFoundException if no sites exist
     */
    public function getCurrentSite(): Site
    {
        if ($this->_currentSite !== null) {
            return $this->_currentSite;
        }

        // Default to the primary site
        return $this->_currentSite = $this->getPrimarySite();
    }

    /**
     * Sets the current site.
     *
     * @param Site|string|int|null $site the current site, or its handle/ID, or null
     * @throws InvalidArgumentException if $site is invalid
     */
    public function setCurrentSite($site)
    {
        // In case this was called from the constructor...
        $this->_loadAllSites();

        if ($site === null) {
            $this->_currentSite = null;
            return;
        }

        if ($site instanceof Site) {
            $this->_currentSite = $site;
        } elseif (is_numeric($site)) {
            $this->_currentSite = $this->getSiteById($site, false);
        } else {
            $this->_currentSite = $this->getSiteByHandle($site, false);
        }

        // Did something go wrong?
        if (!$this->_currentSite) {
            // Fail silently if Craft isn't installed yet or is in the middle of updating
            if (Craft::$app->getIsInstalled() && !Craft::$app->getUpdates()->getIsCraftDbMigrationNeeded()) {
                throw new InvalidArgumentException('Invalid site: ' . $site);
            }
            return;
        }

        // Update the app language if this is a site request
        // (make sure the request component has been initialized first so we don't create an infinite loop)
        if (Craft::$app->has('request', true) && Craft::$app->getRequest()->getIsSiteRequest()) {
            Craft::$app->language = $this->_currentSite->language;
        }
    }

    /**
     * Returns the primary site. The primary site is whatever is listed first in Settings > Sites in the
     * control panel.
     *
     * @return Site The primary site
     * @throws SiteNotFoundException if no sites exist
     */
    public function getPrimarySite(): Site
    {
        if ($this->_primarySite === null) {
            throw new SiteNotFoundException('No primary site exists');
        }

        return $this->_primarySite;
    }

    /**
     * Returns all of the site IDs that are editable by the current user.
     *
     * @return array All the editable sites’ IDs
     */
    public function getEditableSiteIds(): array
    {
        if (!Craft::$app->getIsMultiSite()) {
            return $this->getAllSiteIds(true);
        }

        if ($this->_editableSiteIds !== null) {
            return $this->_editableSiteIds;
        }

        $this->_editableSiteIds = [];
        $userSession = Craft::$app->getUser();

        foreach ($this->getAllSites(true) as $site) {
            if ($userSession->checkPermission("editSite:$site->uid")) {
                $this->_editableSiteIds[] = $site->id;
            }
        }

        return $this->_editableSiteIds;
    }

    /**
     * Returns all sites.
     *
     * @param bool|null $withDisabled
     * @return Site[] All the sites
     */
    public function getAllSites(bool $withDisabled = null): array
    {
        return array_values($this->_allSites($withDisabled));
    }

    /**
     * Returns all editable sites.
     *
     * @return Site[] All the editable sites
     */
    public function getEditableSites(): array
    {
        $editableSiteIds = $this->getEditableSiteIds();
        $editableSites = [];

        foreach ($this->getAllSites() as $site) {
            if (in_array($site->id, $editableSiteIds, false)) {
                $editableSites[] = $site;
            }
        }

        return $editableSites;
    }

    /**
     * Returns sites by a group ID.
     *
     * @param int $groupId
     * @param bool|null $withDisabled
     * @return Site[]
     */
    public function getSitesByGroupId(int $groupId, bool $withDisabled = null): array
    {
        $sites = ArrayHelper::where($this->_allSites($withDisabled), 'groupId', $groupId, false, false);

        // Using array_multisort threw a nesting error for no obvious reason, so don't use it here.
        ArrayHelper::multisort($sites, 'sortOrder', SORT_ASC, SORT_NUMERIC);

        return $sites;
    }

    /**
     * Gets the total number of sites.
     *
     * @return int
     */
    public function getTotalSites(): int
    {
        return count($this->getAllSites());
    }

    /**
     * Gets the total number of sites that are editable by the current user.
     *
     * @return int
     */
    public function getTotalEditableSites(): int
    {
        return count($this->getEditableSiteIds());
    }

    /**
     * Returns a site by its ID.
     *
     * @param int $siteId
     * @param bool|null $withDisabled
     * @return Site|null
     */
    public function getSiteById(int $siteId, bool $withDisabled = null)
    {
        return $this->_allSites($withDisabled)[$siteId] ?? null;
    }

    /**
     * Returns a site by its handle.
     *
     * @param string $siteHandle
     * @param bool|null $withDisabled
     * @return Site|null
     */
    public function getSiteByHandle(string $siteHandle, bool $withDisabled = null)
    {
        return ArrayHelper::firstWhere($this->_allSites($withDisabled), 'handle', $siteHandle, true);
    }

    /**
     * Saves a site.
     *
     * @param Site $site The site to be saved
     * @param bool $runValidation Whether the site should be validated
     * @return bool
     * @throws SiteNotFoundException if $site->id is invalid
     * @throws \Throwable if reasons
     */
    public function saveSite(Site $site, bool $runValidation = true): bool
    {
        $isNewSite = !$site->id;

        if (!empty($this->_allSitesById)) {
            $primarySite = $this->getPrimarySite();
        } else {
            $primarySite = null;
        }

        // Fire a 'beforeSaveSite' event
        if ($this->hasEventHandlers(self::EVENT_BEFORE_SAVE_SITE)) {
            $this->trigger(self::EVENT_BEFORE_SAVE_SITE, new SiteEvent([
                'site' => $site,
                'isNew' => $isNewSite,
                'oldPrimarySiteId' => $primarySite->id ?? null,
            ]));
        }

        if ($runValidation && !$site->validate()) {
            Craft::info('Site not saved due to validation error.', __METHOD__);
            return false;
        }

        if ($isNewSite) {
            $site->uid = StringHelper::UUID();
            $site->sortOrder = ((int)(new Query())
                    ->from([Table::SITES])
                    ->where(['dateDeleted' => null])
                    ->max('[[sortOrder]]')) + 1;
        } elseif (!$site->uid) {
            $site->uid = Db::uidById(Table::SITES, $site->id);
        }

        $projectConfigService = Craft::$app->getProjectConfig();
        $projectConfigService->set(
            self::CONFIG_SITES_KEY . ".$site->uid",
            $site->getConfig(),
            "Save the “{$site->handle}” site"
        );

        // Now that we have a site ID, save it on the model
        if ($isNewSite) {
            $site->id = Db::idByUid(Table::SITES, $site->uid);
        }

        // If this just became the new primary site, update the old primary site's config
        if ($site->primary && $primarySite && $site->id != $primarySite->id) {
            $projectConfigService->set(
                self::CONFIG_SITES_KEY . ".$primarySite->uid.primary",
                false,
                "Set the “{$primarySite->handle}” site not be primary"
            );
        }

        return true;
    }

    /**
     * Handle site changes.
     *
     * @param ConfigEvent $event
     * @throws \Throwable
     */
    public function handleChangedSite(ConfigEvent $event)
    {
        $siteUid = $event->tokenMatches[0];
        $data = $event->newValue;
        $groupUid = $data['siteGroup'];

        // Ensure we have the site group in place first
        $projectConfig = Craft::$app->getProjectConfig();
        $projectConfig->processConfigChanges(self::CONFIG_SITEGROUP_KEY . '.' . $groupUid);

        try {
            $oldPrimarySiteId = $this->getPrimarySite()->id;
        } catch (SiteNotFoundException $e) {
            $oldPrimarySiteId = null;
        }

        $transaction = Craft::$app->getDb()->beginTransaction();

        try {
            $siteRecord = $this->_getSiteRecord($siteUid, true);
            $isNewSite = $siteRecord->getIsNewRecord();
            $groupRecord = $this->_getGroupRecord($groupUid);

            // Shared attributes
            $siteRecord->uid = $siteUid;
            $siteRecord->groupId = $groupRecord->id;
            $siteRecord->name = $data['name'];
            $siteRecord->handle = $data['handle'];
            $siteRecord->language = $data['language'];
            $siteRecord->hasUrls = $data['hasUrls'];
            $siteRecord->baseUrl = $data['baseUrl'];
            $siteRecord->primary = $data['primary'];
            $siteRecord->sortOrder = $data['sortOrder'];

            // todo: remove schema version conditions after next beakpoint
            if (version_compare(Craft::$app->getInstalledSchemaVersion(), '3.5.0', '>=')) {
                $siteRecord->enabled = $data['enabled'] ?? true;
            }

            if ($siteRecord->dateDeleted) {
                $siteRecord->restore();
            } else {
                $siteRecord->save(false);
            }

            $transaction->commit();
        } catch (\Throwable $e) {
            $transaction->rollBack();
            throw $e;
        }

        // Clear caches
        $this->refreshSites();

        /** @var Site $site */
        $site = $this->getSiteById($siteRecord->id);

        // Is this the current site?
        if ($this->_currentSite !== null && $this->_currentSite->id == $site->id) {
            $this->_currentSite = $site;
        }

        // Did the primary site just change?
        if ($oldPrimarySiteId && $data['primary'] && $site->id != $oldPrimarySiteId) {
            $this->_processNewPrimarySite($oldPrimarySiteId, $site->id);
        }

        // If the primary site is changing and the current site was the old primary, let's mark the new primary site as the current site.
        if ($this->_currentSite !== null && $this->_currentSite->id === $oldPrimarySiteId && $this->_currentSite->id !== $site->id && $data['primary']) {
            $this->_currentSite = $site;
        }

        if ($isNewSite && $oldPrimarySiteId) {
            $oldPrimarySiteUid = Db::uidById(Table::SITES, $oldPrimarySiteId);
            $existingCategorySettings = $projectConfig->get(Categories::CONFIG_CATEGORYROUP_KEY);

            if (!$projectConfig->getIsApplyingYamlChanges() && is_array($existingCategorySettings)) {
                foreach ($existingCategorySettings as $categoryUid => $settings) {
                    $primarySiteSettings = $settings['siteSettings'][$oldPrimarySiteUid];
                    $projectConfig->set(Categories::CONFIG_CATEGORYROUP_KEY . '.' . $categoryUid . '.siteSettings.' . $site->uid, $primarySiteSettings, 'Copy site settings for category groups');
                }
            }

            // Re-save most localizable element types
            // (skip entries because they only support specific sites)
            // (skip Matrix blocks because they will be re-saved when their owners are re-saved).
            $elementTypes = [
                GlobalSet::class,
                Asset::class,
                Category::class,
                Tag::class,
            ];

            foreach ($elementTypes as $elementType) {
                Queue::push(new PropagateElements([
                    'elementType' => $elementType,
                    'criteria' => [
                        'siteId' => $oldPrimarySiteId,
                        'status' => null,
                    ],
                    'siteId' => $site->id,
                ]));
            }
        }

        // Fire an 'afterSaveSite' event
        if ($this->hasEventHandlers(self::EVENT_AFTER_SAVE_SITE)) {
            $this->trigger(self::EVENT_AFTER_SAVE_SITE, new SiteEvent([
                'site' => $site,
                'isNew' => $isNewSite,
                'oldPrimarySiteId' => $oldPrimarySiteId,
            ]));
        }

        // Invalidate all element caches
        Craft::$app->getElements()->invalidateAllCaches();
    }

    /**
     * Reorders sites.
     *
     * @param string[] $siteIds The site IDs in their new order
     * @return bool Whether the sites were reordered successfully
     * @throws \Throwable if reasons
     */
    public function reorderSites(array $siteIds): bool
    {
        // Fire a 'beforeReorderSites' event
        if ($this->hasEventHandlers(self::EVENT_BEFORE_REORDER_SITES)) {
            $this->trigger(self::EVENT_BEFORE_REORDER_SITES, new ReorderSitesEvent([
                'siteIds' => $siteIds,
            ]));
        }

        $projectConfig = Craft::$app->getProjectConfig();

        $uidsByIds = Db::uidsByIds(Table::SITES, $siteIds);

        foreach ($siteIds as $sortOrder => $siteId) {
            if (!empty($uidsByIds[$siteId])) {
                $siteUid = $uidsByIds[$siteId];
                $projectConfig->set(self::CONFIG_SITES_KEY . '.' . $siteUid . '.sortOrder', $sortOrder + 1, 'Reorder sites');
            }
        }

        if ($this->hasEventHandlers(self::EVENT_AFTER_REORDER_SITES)) {
            $this->trigger(self::EVENT_AFTER_REORDER_SITES, new ReorderSitesEvent([
                'siteIds' => $siteIds,
            ]));
        }

        return true;
    }

    /**
     * Deletes a site by its ID.
     *
     * @param int $siteId The site ID to be deleted
     * @param int|null $transferContentTo The site ID that should take over the deleted site’s contents
     * @return bool Whether the site was deleted successfully
     * @throws \Throwable if reasons
     */
    public function deleteSiteById(int $siteId, int $transferContentTo = null): bool
    {
        $site = $this->getSiteById($siteId);

        if (!$site) {
            return false;
        }

        return $this->deleteSite($site, $transferContentTo);
    }

    /**
     * Deletes a site.
     *
     * @param Site $site The site to be deleted
     * @param int|null $transferContentTo The site ID that should take over the deleted site’s contents
     * @return bool Whether the site was deleted successfully
     * @throws Exception if $site is the primary site
     * @throws \Throwable if reasons
     */
    public function deleteSite(Site $site, int $transferContentTo = null): bool
    {
        // Make sure this isn't the primary site
        if ($site->id === $this->_primarySite->id) {
            throw new Exception('You cannot delete the primary site.');
        }

        // Fire a 'beforeDeleteSite' event
        $event = new DeleteSiteEvent([
            'site' => $site,
            'transferContentTo' => $transferContentTo,
        ]);

        $this->trigger(self::EVENT_BEFORE_DELETE_SITE, $event);

        // Make sure the event is giving us the go ahead
        if (!$event->isValid) {
            return false;
        }

        $projectConfig = Craft::$app->getProjectConfig();

        // TODO: Move this code into entries module, etc.
        // Get the section IDs that are enabled for this site
        $sectionIds = (new Query())
            ->select(['sectionId'])
            ->from([Table::SECTIONS_SITES])
            ->where(['siteId' => $site->id])
            ->column();

        // Figure out which ones are *only* enabled for this site
        $soloSectionIds = [];

        foreach ($sectionIds as $sectionId) {
            $sectionSiteSettings = Craft::$app->getSections()->getSectionSiteSettings($sectionId);

            if (count($sectionSiteSettings) == 1 && $sectionSiteSettings[0]->siteId == $site->id) {
                $soloSectionIds[] = $sectionId;
            }
        }

        // Did we find any?
        if (!empty($soloSectionIds)) {
            // Should we enable those for a different site?
            if ($transferContentTo !== null) {
                $transferContentToSite = $this->getSiteById($transferContentTo);

                Db::update(Table::SECTIONS_SITES, [
                    'siteId' => $transferContentTo,
                ], [
                    'sectionId' => $soloSectionIds,
                ]);

                // Update the project config too
                $muteEvents = $projectConfig->muteEvents;
                $projectConfig->muteEvents = true;
                foreach ($projectConfig->get(Sections::CONFIG_SECTIONS_KEY) as $sectionUid => $sectionConfig) {
                    if (count($sectionConfig['siteSettings']) === 1 && isset($sectionConfig['siteSettings'][$site->uid])) {
                        $sectionConfig['siteSettings'][$transferContentToSite->uid] = ArrayHelper::remove($sectionConfig['siteSettings'], $site->uid);
                        $projectConfig->set(Sections::CONFIG_SECTIONS_KEY . '.' . $sectionUid, $sectionConfig, 'Prune site settings');
                    }
                }
                $projectConfig->muteEvents = $muteEvents;

                // Get all of the entry IDs in those sections
                $entryIds = (new Query())
                    ->select(['id'])
                    ->from([Table::ENTRIES])
                    ->where(['sectionId' => $soloSectionIds])
                    ->column();

                if (!empty($entryIds)) {
                    // Update the entry tables
                    Db::update(Table::CONTENT, [
                        'siteId' => $transferContentTo,
                    ], [
                        'elementId' => $entryIds,
                    ]);

                    Db::update(Table::ELEMENTS_SITES, [
                        'siteId' => $transferContentTo,
                    ], [
                        'elementId' => $entryIds,
                    ]);

                    Db::update(Table::RELATIONS, [
                        'sourceSiteId' => $transferContentTo,
                    ], [
                        'and',
                        ['sourceId' => $entryIds],
                        ['not', ['sourceSiteId' => null]],
                    ]);

                    // All the Matrix tables
                    $blockIds = (new Query())
                        ->select(['id'])
                        ->from([Table::MATRIXBLOCKS])
                        ->where(['ownerId' => $entryIds])
                        ->column();

                    if (!empty($blockIds)) {
                        Db::delete(Table::ELEMENTS_SITES, [
                            'elementId' => $blockIds,
                            'siteId' => $transferContentTo,
                        ]);

                        Db::update(Table::ELEMENTS_SITES, [
                            'siteId' => $transferContentTo,
                        ], [
                            'elementId' => $blockIds,
                            'siteId' => $site->id,
                        ]);

                        $matrixTablePrefix = Craft::$app->getDb()->getSchema()->getRawTableName('{{%matrixcontent_}}');

                        foreach (Craft::$app->getDb()->getSchema()->getTableNames() as $tableName) {
                            if (strpos($tableName, $matrixTablePrefix) === 0) {
                                Db::delete($tableName, [
                                    'elementId' => $blockIds,
                                    'siteId' => $transferContentTo,
                                ]);

                                Db::update($tableName, [
                                    'siteId' => $transferContentTo,
                                ], [
                                    'elementId' => $blockIds,
                                    'siteId' => $site->id,
                                ]);
                            }
                        }

                        Db::update(Table::RELATIONS, [
                            'sourceSiteId' => $transferContentTo,
                        ], [
                            'and',
                            ['sourceId' => $blockIds],
                            ['not', ['sourceSiteId' => null]],
                        ]);
                    }
                }
            } else {
                // Delete those sections
                foreach ($soloSectionIds as $sectionId) {
                    Craft::$app->getSections()->deleteSectionById($sectionId);
                }
            }
        }

        $projectConfig->remove(self::CONFIG_SITES_KEY . '.' . $site->uid, "Delete the “{$site->handle}” site");
        return true;
    }

    /**
     * Handle a deleted Site.
     *
     * @param ConfigEvent $event
     * @throws DbException
     * @throws \Throwable
     * @throws \yii\base\NotSupportedException
     */
    public function handleDeletedSite(ConfigEvent $event)
    {
        $siteUid = $event->tokenMatches[0];
        $siteRecord = $this->_getSiteRecord($siteUid);

        if (!$siteRecord->id) {
            return;
        }

        /** @var Site $site */
        $site = $this->getSiteById($siteRecord->id);

        // Fire a 'beforeApplySiteDelete' event
        if ($this->hasEventHandlers(self::EVENT_BEFORE_APPLY_SITE_DELETE)) {
            $this->trigger(self::EVENT_BEFORE_APPLY_SITE_DELETE, new DeleteSiteEvent([
                'site' => $site,
            ]));
        }

        $transaction = Craft::$app->getDb()->beginTransaction();

        try {
            Craft::$app->getDb()->createCommand()
                ->softDelete(Table::SITES, ['id' => $siteRecord->id])
                ->execute();

            $transaction->commit();
        } catch (\Throwable $e) {
            $transaction->rollBack();
            throw $e;
        }

        // Refresh sites
        $this->refreshSites();

        // Invalidate all element caches
        Craft::$app->getElements()->invalidateAllCaches();

        // Was this the current site?
        if ($this->_currentSite !== null && $this->_currentSite->id == $site->id) {
            $this->setCurrentSite($this->_primarySite);
        }

        // Fire an 'afterDeleteSite' event
        if ($this->hasEventHandlers(self::EVENT_AFTER_DELETE_SITE)) {
            $this->trigger(self::EVENT_AFTER_DELETE_SITE, new DeleteSiteEvent([
                'site' => $site,
            ]));
        }

        // Invalidate all element caches
        Craft::$app->getElements()->invalidateAllCaches();
    }

    /**
     * Restores a site by its ID.
     *
     * @param int $id The site’s ID
     * @return bool Whether the site was restored successfully
     * @since 3.1.0
     */
    public function restoreSiteById(int $id): bool
    {
        $affectedRows = Craft::$app->getDb()->createCommand()
            ->restore(Table::SITES, ['id' => $id])
            ->execute();
        return (bool)$affectedRows;
    }

    /**
     * Refresh the status of all sites based on the DB data.
     *
     * @throws DbException
     * @since 3.5.13
     */
    public function refreshSites()
    {
        $this->_allSitesById = null;
        $this->_enabledSitesById = null;
        $this->_loadAllSites();
        Craft::$app->getIsMultiSite(true);
    }

    /**
     * Loads all the sites.
     */
    private function _loadAllSites()
    {
        if ($this->_allSitesById !== null) {
            return;
        }

        $this->_allSitesById = [];
        $this->_enabledSitesById = [];

        if (!Craft::$app->getIsInstalled()) {
            return;
        }

        $schemaVersion = Craft::$app->getInstalledSchemaVersion();

        $query = (new Query())
            ->select([
                's.id',
                's.name',
                's.handle',
                's.language',
                's.primary',
                's.hasUrls',
                's.baseUrl',
                's.sortOrder',
                's.uid',
                's.dateCreated',
                's.dateUpdated',
            ])
            ->from(['s' => Table::SITES]);

        // TODO: remove the version checks after the next breakpoint
        if (version_compare($schemaVersion, '3.0.74', '<')) {
            $query
                ->orderBy(['s.name' => SORT_ASC]);
        } else {
            $query
                ->addSelect(['s.groupId'])
                ->innerJoin(['sg' => Table::SITEGROUPS], '[[sg.id]] = [[s.groupId]]')
                ->orderBy(['sg.name' => SORT_ASC, 's.sortOrder' => SORT_ASC]);

            if (version_compare($schemaVersion, '3.1.7', '>=')) {
                $query
                    ->where(['s.dateDeleted' => null])
                    ->andWhere(['sg.dateDeleted' => null]);

                if (version_compare($schemaVersion, '3.5.12', '>=')) {
                    $query
                        ->addSelect(['s.enabled']);
                }
            }
        }

        try {
            $results = $query->all();
        } catch (DbException $e) {
            // todo: remove this after the next breakpoint
            // If the error code is 42S02 (MySQL) or 42P01 (PostgreSQL), the sites table probably doesn't exist yet
            if (isset($e->errorInfo[0]) && in_array($e->errorInfo[0], ['42S02', '42P01'], true)) {
                return;
            }
            /** @noinspection PhpUnhandledExceptionInspection */
            throw $e;
        }

        // Check for results because during installation, the transaction hasn't been committed yet.
        if (!empty($results)) {
            $generalConfig = Craft::$app->getConfig()->getGeneral();

            foreach ($results as $i => $result) {
                $site = new Site($result);
                $this->_allSitesById[$site->id] = $site;
                if ($site->enabled) {
                    $this->_enabledSitesById[$site->id] = $site;
                }

                if ($site->primary) {
                    $this->_primarySite = $site;

                    // todo: remove in Craft 4
                    if (is_string($generalConfig->siteName)) {
                        $site->overrideName($generalConfig->siteName);
                    }
                    if (is_string($generalConfig->siteUrl)) {
                        $site->overrideBaseUrl($generalConfig->siteUrl);
                    }
                }

                // todo: remove in Craft 4
                if (is_array($generalConfig->siteName) && isset($generalConfig->siteName[$site->handle])) {
                    $site->overrideName($generalConfig->siteName[$site->handle]);
                }
                if (is_array($generalConfig->siteUrl) && isset($generalConfig->siteUrl[$site->handle])) {
                    $site->overrideBaseUrl($generalConfig->siteUrl[$site->handle]);
                }
            }
        }
    }

    /**
     * Returns a Query object prepped for retrieving groups.
     *
     * @return Query
     */
    private function _createGroupQuery(): Query
    {
        return (new Query())
            ->select([
                'id',
                'name',
                'uid',
            ])
            ->from([Table::SITEGROUPS])
            ->where(['dateDeleted' => null])
            ->orderBy(['name' => SORT_ASC]);
    }

    /**
     * Gets a site group record or creates a new one.
     *
     * @param mixed $criteria ID or UID of the site group.
     * @param bool $withTrashed Whether to include trashed site groups in search
     * @return SiteGroupRecord
     */
    private function _getGroupRecord($criteria, bool $withTrashed = false): SiteGroupRecord
    {
        $query = $withTrashed ? SiteGroupRecord::findWithTrashed() : SiteGroupRecord::find();
        if (is_numeric($criteria)) {
            $query->andWhere(['id' => $criteria]);
        } elseif (is_string($criteria)) {
            $query->andWhere(['uid' => $criteria]);
        }

        return $query->one() ?? new SiteGroupRecord();
    }

    /**
     * Returns all sites, or only enabled sites.
     *
     * @param bool|null $withDisabled
     * @return Site[]
     */
    private function _allSites(bool $withDisabled = null)
    {
        if ($withDisabled === null) {
            $request = Craft::$app->getRequest();
            $withDisabled = (
                $request->getIsConsoleRequest() ||
                ($request->getIsCpRequest() && !Craft::$app->getUser()->getIsGuest())
            );
        }

        return $withDisabled ? $this->_allSitesById : $this->_enabledSitesById;
    }

    /**
     * Gets a site record or creates a new one.
     *
     * @param mixed $criteria ID or UID of the site group.
     * @param bool $withTrashed Whether to include trashed sites in search
     * @return SiteRecord
     */
    private function _getSiteRecord($criteria, bool $withTrashed = false): SiteRecord
    {
        $query = $withTrashed ? SiteRecord::findWithTrashed() : SiteRecord::find();
        if (is_numeric($criteria)) {
            $query->andWhere(['id' => $criteria]);
        } elseif (\is_string($criteria)) {
            $query->andWhere(['uid' => $criteria]);
        }

        return $query->one() ?? new SiteRecord();
    }

    /**
     * Handles things that happen when there's a new primary site
     *
     * @param int $oldPrimarySiteId
     * @param int $newPrimarySiteId
     * @throws \Throwable
     */
    private function _processNewPrimarySite(int $oldPrimarySiteId, int $newPrimarySiteId)
    {
        App::maxPowerCaptain();

        $db = Craft::$app->getDb();
        $transaction = $db->beginTransaction();

        try {
            Db::update(Table::SITES, [
                'primary' => false,
            ], [
                'id' => $oldPrimarySiteId,
            ]);
            Db::update(Table::SITES, [
                'primary' => true,
            ], [
                'id' => $newPrimarySiteId,
            ]);

            // Update all of the non-localized elements
            $nonLocalizedElementTypes = [];

            foreach (Craft::$app->getElements()->getAllElementTypes() as $elementType) {
                /** @var ElementInterface|string $elementType */
                if (!$elementType::isLocalized()) {
                    $nonLocalizedElementTypes[] = $elementType;
                }
            }

            if (!empty($nonLocalizedElementTypes)) {
                $elementIds = (new Query())
                    ->select(['id'])
                    ->from([Table::ELEMENTS])
                    ->where(['type' => $nonLocalizedElementTypes])
                    ->column();

                if (!empty($elementIds)) {
                    // To be sure we don't hit any unique constraint database errors, first make sure there are no rows for
                    // these elements that don't currently use the old primary site ID
                    $deleteCondition = [
                        'and',
                        ['elementId' => $elementIds],
                        ['not', ['siteId' => $oldPrimarySiteId]],
                    ];

                    Db::delete(Table::ELEMENTS_SITES, $deleteCondition);
                    Db::delete(Table::CONTENT, $deleteCondition);
                    Db::delete(Table::SEARCHINDEX, $deleteCondition);

                    // Now swap the sites
                    $updateColumns = ['siteId' => $newPrimarySiteId];
                    $updateCondition = ['elementId' => $elementIds];

                    Db::update(Table::ELEMENTS_SITES, $updateColumns, $updateCondition, [], false);
                    Db::update(Table::CONTENT, $updateColumns, $updateCondition, [], false);
                    Db::update(Table::SEARCHINDEX, $updateColumns, $updateCondition, [], false);
                }
            }

            $transaction->commit();
        } catch (\Throwable $e) {
            $transaction->rollBack();
            throw $e;
        }

        // Set the new primary site by forcing a reload from the DB.
        $this->refreshSites();

        // Fire an afterChangePrimarySite event
        if ($this->hasEventHandlers(self::EVENT_AFTER_CHANGE_PRIMARY_SITE)) {
            $this->trigger(self::EVENT_AFTER_CHANGE_PRIMARY_SITE, new SiteEvent([
                'site' => $this->_primarySite,
            ]));
        }
    }
}
