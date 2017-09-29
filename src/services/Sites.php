<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\services;

use Craft;
use craft\base\Element;
use craft\db\Query;
use craft\elements\Asset;
use craft\elements\Category;
use craft\elements\GlobalSet;
use craft\elements\Tag;
use craft\errors\DbConnectException;
use craft\errors\SiteNotFoundException;
use craft\events\DeleteSiteEvent;
use craft\events\ReorderSitesEvent;
use craft\events\SiteEvent;
use craft\helpers\App;
use craft\models\Site;
use craft\queue\jobs\ResaveElements;
use craft\records\Site as SiteRecord;
use yii\base\Component;
use yii\base\Exception;
use yii\base\InvalidConfigException;

/**
 * Class Sites service.
 *
 * An instance of the Sites service is globally accessible in Craft via [[Application::sites `Craft::$app->getSites()`]].
 *
 * @property int[] $allSiteIds         All of the site IDs
 * @property int[] $editableSiteIds    All of the site IDs that are editable by the current user
 * @property Site  $primarySite        The primary site
 * @property int   $totalSites         The total number of sites
 * @property int   $totalEditableSites The total number of sites that are editable by the current user
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class Sites extends Component
{
    // Constants
    // =========================================================================

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
     * You may set [[SiteEvent::isValid]] to `false` to prevent the site from getting deleted.
     */
    const EVENT_BEFORE_DELETE_SITE = 'beforeDeleteSite';

    /**
     * @event DeleteSiteEvent The event that is triggered after a site is deleted.
     */
    const EVENT_AFTER_DELETE_SITE = 'afterDeleteSite';

    // Properties
    // =========================================================================

    /**
     * @var Site|null The current site, or null if Craft isn't installed yet
     */
    public $currentSite;

    /**
     * @var int[]|null
     */
    private $_editableSiteIds;

    /**
     * @var Site[]|null
     */
    private $_sitesById;

    /**
     * @var Site[]|null
     */
    private $_sitesByHandle;

    /**
     * @var Site|null
     */
    private $_primarySite;

    /**
     * @var bool
     */
    private $_fetchedAllSites = false;

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     *
     * @throws InvalidConfigException if currentSite was set incorrectly
     * @throws SiteNotFoundException if currentSite was not set and no sites exist
     */
    public function init()
    {
        // No technical reason to put this here, but it's sortof related
        if (defined('CRAFT_LOCALE')) {
            Craft::$app->getDeprecator()->log('CRAFT_LOCALE', 'The CRAFT_LOCALE constant has been deprecated. Use CRAFT_SITE instead, which can be set to a site ID or handle.');
        }

        $this->_loadAllSites();
        $this->_setCurrentSite();
    }

    // Sites
    // -------------------------------------------------------------------------

    /**
     * Returns all of the site IDs.
     *
     * @return int[] All the sites’ IDs
     */
    public function getAllSiteIds(): array
    {
        $this->_loadAllSites();

        return array_keys($this->_sitesById);
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
        $this->_loadAllSites();

        if ($this->_primarySite === null) {
            throw new SiteNotFoundException('No sites exist');
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
        if ($this->_editableSiteIds !== null) {
            return $this->_editableSiteIds;
        }

        $this->_editableSiteIds = [];

        foreach ($this->getAllSiteIds() as $siteId) {
            if (Craft::$app->getUser()->checkPermission('editSite:'.$siteId)) {
                $this->_editableSiteIds[] = $siteId;
            }
        }

        return $this->_editableSiteIds;
    }

    /**
     * Returns all sites.
     *
     * @return Site[] All the sites
     */
    public function getAllSites(): array
    {
        $this->_loadAllSites();

        return array_values($this->_sitesById);
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
     *
     * @return Site|null
     */
    public function getSiteById(int $siteId)
    {
        if (!$siteId) {
            return null;
        }

        // If we've already fetched all sites we can save ourselves a trip to the DB for site IDs that don't exist
        if (!$this->_fetchedAllSites && !array_key_exists($siteId, $this->_sitesById)) {
            $result = $this->_createSiteQuery()
                ->where(['id' => $siteId])
                ->one();

            if ($result) {
                $site = new Site($result);
                $this->_sitesByHandle[$site->handle] = $site;
            } else {
                $site = null;
            }

            $this->_sitesById[$siteId] = $site;
        }

        if (isset($this->_sitesById[$siteId])) {
            return $this->_sitesById[$siteId];
        }

        return null;
    }

    /**
     * Returns a site by its handle.
     *
     * @param string $siteHandle
     *
     * @return Site|null
     */
    public function getSiteByHandle(string $siteHandle)
    {
        // If we've already fetched all sites we can save ourselves a trip to the DB for site handles that don't exist
        if (!$this->_fetchedAllSites && !array_key_exists($siteHandle, $this->_sitesByHandle)) {
            $result = $this->_createSiteQuery()
                ->where(['handle' => $siteHandle])
                ->one();

            if ($result) {
                $site = new Site($result);
                $this->_sitesById[$site->id] = $site;
            } else {
                $site = null;
            }

            $this->_sitesByHandle[$siteHandle] = $site;
        }

        if (isset($this->_sitesByHandle[$siteHandle])) {
            return $this->_sitesByHandle[$siteHandle];
        }

        return null;
    }

    /**
     * Saves a site.
     *
     * @param Site $site          The site to be saved
     * @param bool $runValidation Whether the site should be validated
     *
     * @return bool
     * @throws SiteNotFoundException if $site->id is invalid
     * @throws \Throwable if reasons
     */
    public function saveSite(Site $site, bool $runValidation = true): bool
    {
        $isNewSite = !$site->id;

        // Fire a 'beforeSaveSite' event
        if ($this->hasEventHandlers(self::EVENT_BEFORE_SAVE_SITE)) {
            $this->trigger(self::EVENT_BEFORE_SAVE_SITE, new SiteEvent([
                'site' => $site,
                'isNew' => $isNewSite,
            ]));
        }

        if ($runValidation && !$site->validate()) {
            Craft::info('Site not saved due to validation error.', __METHOD__);
            return false;
        }

        if (!$isNewSite) {
            $siteRecord = SiteRecord::find()
                ->where(['id' => $site->id])
                ->one();

            if (!$siteRecord) {
                throw new SiteNotFoundException("No site exists with the ID '{$site->id}'");
            }
        } else {
            $siteRecord = new SiteRecord();
            $maxSortOrder = false;

            if (Craft::$app->getIsInstalled()) {
                // Get the next biggest sort order
                $maxSortOrder = (new Query())
                    ->from(['{{%sites}}'])
                    ->max('[[sortOrder]]');
            }

            $siteRecord->sortOrder = $maxSortOrder ? $maxSortOrder + 1 : 1;
        }

        // Shared attributes
        $siteRecord->name = $site->name;
        $siteRecord->handle = $site->handle;
        $siteRecord->language = $site->language;
        $siteRecord->hasUrls = $site->hasUrls;
        $siteRecord->baseUrl = $site->baseUrl;

        $transaction = Craft::$app->getDb()->beginTransaction();
        try {
            // Is the event giving us the go-ahead?
            $siteRecord->save(false);

            // Now that we have a site ID, save it on the model
            if ($isNewSite) {
                $site->id = $siteRecord->id;
            }

            // Update our cache of the site
            $this->_sitesById[$site->id] = $site;
            $this->_sitesByHandle[$site->handle] = $site;

            $transaction->commit();
        } catch (\Throwable $e) {
            $transaction->rollBack();

            throw $e;
        }

        if (Craft::$app->getIsInstalled() && $isNewSite) {
            // TODO: Move this code into element/category modules
            // Create site settings for each of the category groups
            $allSiteSettings = (new Query())
                ->select(['groupId', 'uriFormat', 'template', 'hasUrls'])
                ->from(['{{%categorygroups_sites}}'])
                ->where(['siteId' => $this->getPrimarySite()->id])
                ->all();

            if (!empty($allSiteSettings)) {
                $newSiteSettings = [];

                foreach ($allSiteSettings as $siteSettings) {
                    $newSiteSettings[] = [
                        $siteSettings['groupId'],
                        $site->id,
                        $siteSettings['uriFormat'],
                        $siteSettings['template'],
                        $siteSettings['hasUrls']
                    ];
                }

                Craft::$app->getDb()->createCommand()
                    ->batchInsert(
                        '{{%categorygroups_sites}}',
                        ['groupId', 'siteId', 'uriFormat', 'template', 'hasUrls'],
                        $newSiteSettings)
                    ->execute();
            }

            // Re-save most localizable element types
            // (skip entries because they only support specific sites)
            // (skip Matrix blocks because they will be re-saved when their owners are re-saved).
            $queue = Craft::$app->getQueue();
            $siteId = $this->getPrimarySite()->id;
            $elementTypes = [
                Asset::class,
                Category::class,
                GlobalSet::class,
                Tag::class,
            ];

            foreach ($elementTypes as $elementType) {
                $queue->push(new ResaveElements([
                    'elementType' => $elementType,
                    'criteria' => [
                        'siteId' => $siteId,
                        'status' => null,
                        'enabledForSite' => false
                    ]
                ]));
            }
        }

        // Fire an 'afterSaveSite' event
        if ($this->hasEventHandlers(self::EVENT_AFTER_SAVE_SITE)) {
            $this->trigger(self::EVENT_AFTER_SAVE_SITE, new SiteEvent([
                'site' => $site,
                'isNew' => $isNewSite,
            ]));
        }

        return true;
    }

    /**
     * Reorders sites.
     *
     * @param int[] $siteIds The site IDs in their new order
     *
     * @return bool Whether the sites were reordered successfthe sites are reorderedy
     * @throws \Throwable if reasons
     */
    public function reorderSites(array $siteIds): bool
    {
        // Fire a 'beforeSaveSite' event
        if ($this->hasEventHandlers(self::EVENT_BEFORE_REORDER_SITES)) {
            $this->trigger(self::EVENT_BEFORE_REORDER_SITES, new ReorderSitesEvent([
                'siteIds' => $siteIds,
            ]));
        }

        $this->_loadAllSites();

        $transaction = Craft::$app->getDb()->beginTransaction();

        try {
            foreach ($siteIds as $sortOrder => $siteId) {
                $siteRecord = SiteRecord::findOne($siteId);
                $siteRecord->sortOrder = $sortOrder + 1;
                $siteRecord->save();
            }

            $transaction->commit();
        } catch (\Throwable $e) {
            $transaction->rollBack();

            throw $e;
        }

        // Did the primary site just change?
        $oldPrimarySiteId = $this->getPrimarySite()->id;
        $newPrimarySiteId = $siteIds[0];

        if ($newPrimarySiteId != $oldPrimarySiteId) {
            $this->_processNewPrimarySite($oldPrimarySiteId, $newPrimarySiteId);
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
     * @param int      $siteId            The site ID to be deleted
     * @param int|null $transferContentTo The site ID that should take over the deleted site’s contents
     *
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
     * @param Site     $site              The site to be deleted
     * @param int|null $transferContentTo The site ID that should take over the deleted site’s contents
     *
     * @return bool Whether the site was deleted successfully
     * @throws \Throwable if reasons
     */
    public function deleteSite(Site $site, int $transferContentTo = null): bool
    {
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

        // TODO: Move this code into entries module, etc.
        // Get the section IDs that are enabled for this site
        $sectionIds = (new Query())
            ->select(['sectionId'])
            ->from(['{{%sections_sites}}'])
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
                Craft::$app->getDb()->createCommand()
                    ->update(
                        '{{%sections_sites}}',
                        ['siteId' => $transferContentTo],
                        ['sectionId' => $soloSectionIds])
                    ->execute();

                // Get all of the entry IDs in those sections
                $entryIds = (new Query())
                    ->select(['id'])
                    ->from(['{{%entries}}'])
                    ->where(['sectionId' => $soloSectionIds])
                    ->column();

                if (!empty($entryIds)) {
                    // Delete their template caches
                    Craft::$app->getTemplateCaches()->deleteCachesByElementId($entryIds);

                    // Update the entry tables
                    Craft::$app->getDb()->createCommand()
                        ->update(
                            '{{%content}}',
                            ['siteId' => $transferContentTo],
                            ['elementId' => $entryIds])
                        ->execute();

                    Craft::$app->getDb()->createCommand()
                        ->update(
                            '{{%elements_sites}}',
                            ['siteId' => $transferContentTo],
                            ['elementId' => $entryIds])
                        ->execute();

                    Craft::$app->getDb()->createCommand()
                        ->update(
                            '{{%entrydrafts}}',
                            ['siteId' => $transferContentTo],
                            ['entryId' => $entryIds])
                        ->execute();

                    Craft::$app->getDb()->createCommand()
                        ->update(
                            '{{%entryversions}}',
                            ['siteId' => $transferContentTo],
                            ['entryId' => $entryIds])
                        ->execute();

                    Craft::$app->getDb()->createCommand()
                        ->update(
                            '{{%relations}}',
                            ['sourceSiteId' => $transferContentTo],
                            [
                                'and',
                                ['sourceId' => $entryIds],
                                ['not', ['sourceSiteId' => null]]
                            ])
                        ->execute();

                    // All the Matrix tables
                    $blockIds = (new Query())
                        ->select(['id'])
                        ->from(['{{%matrixblocks}}'])
                        ->where(['ownerId' => $entryIds])
                        ->column();

                    if (!empty($blockIds)) {
                        Craft::$app->getDb()->createCommand()
                            ->update(
                                '{{%matrixblocks}}',
                                ['ownerSiteId' => $transferContentTo],
                                [
                                    'and',
                                    ['id' => $blockIds],
                                    ['not', ['ownerSiteId' => null]]
                                ])
                            ->execute();

                        Craft::$app->getDb()->createCommand()
                            ->delete(
                                '{{%elements_sites}}',
                                [
                                    'elementId' => $blockIds,
                                    'siteId' => $transferContentTo
                                ])
                            ->execute();

                        Craft::$app->getDb()->createCommand()
                            ->update(
                                '{{%elements_sites}}',
                                ['siteId' => $transferContentTo],
                                [
                                    'elementId' => $blockIds,
                                    'siteId' => $site->id
                                ])
                            ->execute();

                        $matrixTablePrefix = Craft::$app->getDb()->getSchema()->getRawTableName('{{%matrixcontent_}}');
                        $tablePrefixLength = strlen(Craft::$app->getDb()->tablePrefix);

                        foreach (Craft::$app->getDb()->getSchema()->getTableNames() as $tableName) {
                            if (strpos($tableName, $matrixTablePrefix) === 0) {
                                $tableName = substr($tableName, $tablePrefixLength);

                                Craft::$app->getDb()->createCommand()
                                    ->delete(
                                        $tableName,
                                        [
                                            'elementId' => $blockIds,
                                            'siteId' => $transferContentTo
                                        ])
                                    ->execute();

                                Craft::$app->getDb()->createCommand()
                                    ->update(
                                        $tableName,
                                        ['siteId' => $transferContentTo],
                                        [
                                            'elementId' => $blockIds,
                                            'siteId' => $site->id
                                        ])
                                    ->execute();
                            }
                        }

                        Craft::$app->getDb()->createCommand()
                            ->update(
                                '{{%relations}}',
                                ['sourceSiteId' => $transferContentTo],
                                [
                                    'and',
                                    ['sourceId' => $blockIds],
                                    ['not', ['sourceSiteId' => null]]
                                ])
                            ->execute();
                    }
                }
            } else {
                // Delete those sections
                foreach ($soloSectionIds as $sectionId) {
                    Craft::$app->getSections()->deleteSectionById($sectionId);
                }
            }
        }

        $oldPrimarySiteId = $this->getPrimarySite()->id;

        // Is the primary site ID getting deleted?
        if ($oldPrimarySiteId === $site->id) {
            $newPrimarySiteId = $this->_createSiteQuery()
                ->select('id')
                ->offset(1)
                ->scalar();

            if (!$newPrimarySiteId || $oldPrimarySiteId == $newPrimarySiteId) {
                throw new Exception('Deleting the only site is not permitted');
            }

            $this->_processNewPrimarySite($oldPrimarySiteId, $newPrimarySiteId);
        }

        $transaction = Craft::$app->getDb()->beginTransaction();
        try {
            $affectedRows = Craft::$app->getDb()->createCommand()
                ->delete('{{%sites}}', ['id' => $site->id])
                ->execute();

            $transaction->commit();

            $success = (bool)$affectedRows;
        } catch (\Throwable $e) {
            $transaction->rollBack();

            throw $e;
        }

        // Fire an 'afterDeleteSite' event
        if ($this->hasEventHandlers(self::EVENT_AFTER_DELETE_SITE)) {
            $this->trigger(self::EVENT_AFTER_DELETE_SITE, new DeleteSiteEvent([
                'site' => $site,
                'transferContentTo' => $transferContentTo,
            ]));
        }

        return $success;
    }

    // Private Methods
    // =========================================================================

    /**
     * Loads all the sites.
     *
     * @return void
     * @throws DbConnectException if Craft isn't installed yet
     * @throws \yii\db\Exception if the sites table is missing
     */
    private function _loadAllSites()
    {
        if ($this->_fetchedAllSites) {
            return;
        }

        $this->_sitesById = [];
        $this->_sitesByHandle = [];

        if (!Craft::$app->getIsInstalled()) {
            return;
        }

        try {
            $results = $this->_createSiteQuery()->all();

            // Check for results because during installation, then transaction
            // hasn't been committed yet.
            if (!empty($results)) {
                foreach ($results as $i => $result) {
                    $site = new Site($result);
                    $this->_sitesById[$site->id] = $site;
                    $this->_sitesByHandle[$site->handle] = $site;

                    if ($i == 0) {
                        $this->_primarySite = $site;
                    }
                }

                $this->_fetchedAllSites = true;
            }
        } catch (\yii\db\Exception $e) {
            // todo: remove this after the next breakpoint
            // If the error code is 42S02 (MySQL) or 42P01 (PostgreSQL), the sites table probably doesn't exist yet
            if (isset($e->errorInfo[0]) && ($e->errorInfo[0] === '42S02' || $e->errorInfo[0] === '42P01')) {
                return;
            }

            throw $e;
        }
    }

    /**
     * Sets the current site.
     *
     * @return void
     * @throws \Throwable if reasons
     */
    private function _setCurrentSite()
    {
        // Skip if Craft isn't installed yet
        if (!Craft::$app->getIsInstalled()) {
            $this->currentSite = null;
            return;
        }

        try {
            // Set $this->currentSite to an actual Site model if it's not already
            if (!($this->currentSite instanceof Site)) {
                if ($this->currentSite !== null) {
                    if (is_numeric($this->currentSite)) {
                        $site = $this->getSiteById($this->currentSite);
                    } else {
                        $site = $this->getSiteByHandle($this->currentSite);
                    }

                    if (!$site) {
                        throw new InvalidConfigException('Invalid currentSite config setting value: '.$this->currentSite);
                    }

                    $this->currentSite = $site;
                } else {
                    // Default to the primary site
                    $this->currentSite = $this->getPrimarySite();
                }
            }

            // Is the config overriding the site name/URL?
            $generalConfig = Craft::$app->getConfig()->getGeneral();

            if (is_string($generalConfig->siteName)) {
                $this->getPrimarySite()->overrideName($generalConfig->siteName);
            } else if (is_array($generalConfig->siteName)) {
                foreach ($generalConfig->siteName as $handle => $name) {
                    $site = $this->getSiteByHandle($handle);
                    if ($site) {
                        $site->overrideName($name);
                    } else {
                        Craft::warning('Ignored this invalid site handle when applying the siteName config setting: '.$handle, __METHOD__);
                    }
                }
            }

            if (is_string($generalConfig->siteUrl)) {
                $this->getPrimarySite()->overrideBaseUrl($generalConfig->siteUrl);
            } else if (is_array($generalConfig->siteUrl)) {
                foreach ($generalConfig->siteUrl as $handle => $baseUrl) {
                    $site = $this->getSiteByHandle($handle);
                    if ($site) {
                        $site->overrideBaseUrl($baseUrl);
                    } else {
                        Craft::warning('Ignored this invalid site handle when applying the siteUrl config setting: '.$handle, __METHOD__);
                    }
                }
            }
        } catch (\Throwable $e) {
            // Fail silently if Craft is in the middle of updating
            if (Craft::$app->getUpdates()->getIsCraftDbMigrationNeeded()) {
                $this->currentSite = null;
            } else {
                throw $e;
            }
        }
    }

    /**
     * Returns a Query object prepped for retrieving sites.
     *
     * @return Query
     */
    private function _createSiteQuery(): Query
    {
        return (new Query())
            ->select(['id', 'name', 'handle', 'language', 'hasUrls', 'baseUrl'])
            ->from(['{{%sites}}'])
            ->orderBy(['sortOrder' => SORT_ASC]);
    }

    /**
     * Handles things that happen when there's a new primary site
     *
     * @param int $oldPrimarySiteId
     * @param int $newPrimarySiteId
     */
    private function _processNewPrimarySite(int $oldPrimarySiteId, int $newPrimarySiteId)
    {
        // Set the new primary site
        $this->_primarySite = $this->getSiteById($newPrimarySiteId);

        App::maxPowerCaptain();

        // Update all of the non-localized elements
        $nonLocalizedElementTypes = [];

        foreach (Craft::$app->getElements()->getAllElementTypes() as $elementType) {
            /** @var Element|string $elementType */
            if (!$elementType::isLocalized()) {
                $nonLocalizedElementTypes[] = $elementType;
            }
        }

        if (!empty($nonLocalizedElementTypes)) {
            $elementIds = (new Query())
                ->select(['id'])
                ->from(['{{%elements}}'])
                ->where(['type' => $nonLocalizedElementTypes])
                ->column();

            if (!empty($elementIds)) {
                // To be sure we don't hit any unique constraint database errors, first make sure there are no rows for
                // these elements that don't currently use the old primary site ID
                $deleteCondition = [
                    'and',
                    ['elementId' => $elementIds],
                    ['not', ['siteId' => $oldPrimarySiteId]]
                ];

                $db = Craft::$app->getDb();

                $db->createCommand()
                    ->delete('{{%elements_sites}}', $deleteCondition)
                    ->execute();
                $db->createCommand()
                    ->delete('{{%content}}', $deleteCondition)
                    ->execute();

                // Now swap the sites
                $updateColumns = ['siteId' => $newPrimarySiteId];
                $updateCondition = ['elementId' => $elementIds];

                $db->createCommand()
                    ->update('{{%elements_sites}}', $updateColumns, $updateCondition)
                    ->execute();
                $db->createCommand()
                    ->update('{{%content}}', $updateColumns, $updateCondition)
                    ->execute();
            }
        }

        // Fire an afterChangePrimarySite event
        if ($this->hasEventHandlers(self::EVENT_AFTER_CHANGE_PRIMARY_SITE)) {
            $this->trigger(self::EVENT_AFTER_CHANGE_PRIMARY_SITE, new SiteEvent([
                'site' => $this->_primarySite,
            ]));
        }
    }
}
