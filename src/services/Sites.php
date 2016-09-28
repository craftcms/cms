<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\app\services;

use Craft;
use craft\app\db\Query;
use craft\app\errors\DbConnectException;
use craft\app\errors\SiteNotFoundException;
use craft\app\events\DeleteSiteEvent;
use craft\app\events\ReorderSitesEvent;
use craft\app\events\SiteEvent;
use craft\app\models\Site;
use craft\app\records\Site as SiteRecord;
use craft\app\tasks\ResaveAllElements;
use yii\base\Component;
use yii\base\Exception;
use yii\base\InvalidConfigException;

/**
 * Class Sites service.
 *
 * An instance of the Sites service is globally accessible in Craft via [[Application::sites `Craft::$app->getSites()`]].
 *
 * @property integer[] $allSiteIds         All of the site IDs
 * @property integer[] $editableSiteIds    All of the site IDs that are editable by the current user
 * @property Site      $primarySite        The primary site
 * @property integer   $totalSites         The total number of sites
 * @property integer   $totalEditableSites The total number of sites that are editable by the current user
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
     *
     * You may set [[SiteEvent::isValid]] to `false` to prevent the sites from getting reordered.
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
     * @var integer[]
     */
    private $_editableSiteIds;

    /**
     * @var Site[]
     */
    private $_sitesById;

    /**
     * @var Site[]
     */
    private $_sitesByHandle;

    /**
     * @var Site
     */
    private $_primarySite;

    /**
     * @var boolean
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

        // Fetch all the sites
        $this->_loadAllSites();

        // Set $this->currentSite to an actual Site model if it's not already
        if (!($this->currentSite instanceof Site)) {
            if (isset($this->currentSite)) {
                if (is_numeric($this->currentSite)) {
                    $site = $this->getSiteById($this->currentSite);
                } else {
                    $site = $this->getSiteByHandle($this->currentSite);
                }

                if (!$site) {
                    throw new InvalidConfigException('Invalid currentSite config setting value: '.$this->currentSite);
                }

                $this->currentSite = $site;
            } else if (Craft::$app->getIsInstalled() && !Craft::$app->getIsUpdating()) {
                // Default to the primary site
                $this->currentSite = $this->getPrimarySite();
            } else {
                $this->currentSite = null;
            }
        }

        if (Craft::$app->getIsInstalled() && !Craft::$app->getIsUpdating()) {
            // Is the config overriding the site URL?
            $siteUrl = Craft::$app->getConfig()->get('siteUrl');

            if ($siteUrl === null && defined('CRAFT_SITE_URL')) {
                Craft::$app->getDeprecator()->log('CRAFT_SITE_URL', 'The CRAFT_SITE_URL constant has been deprecated. Set the "siteUrl" config setting in craft/config/general.php instead.');
                $siteUrl = CRAFT_SITE_URL;
            }

            if (is_string($siteUrl)) {
                $this->getPrimarySite()->overrideBaseUrl($siteUrl);
            } else if (is_array($siteUrl)) {
                foreach ($siteUrl as $handle => $url) {
                    $site = $this->getSiteByHandle($handle);
                    if ($site) {
                        $site->overrideBaseUrl($url);
                    } else {
                        Craft::warning('Ignored this invalid site handle when applying the siteUrl config setting: '.$handle, __METHOD__);
                    }
                }
            }
        }
    }

    // Sites
    // -------------------------------------------------------------------------

    /**
     * Returns all of the site IDs.
     *
     * @return array All the sites’ IDs
     */
    public function getAllSiteIds()
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
    public function getPrimarySite()
    {
        $this->_loadAllSites();

        if (!isset($this->_primarySite)) {
            throw new SiteNotFoundException('No sites exist');
        }

        return $this->_primarySite;
    }

    /**
     * Returns all of the site IDs that are editable by the current user.
     *
     * @return array All the editable sites’ IDs
     */
    public function getEditableSiteIds()
    {
        if (!isset($this->_editableSiteIds)) {
            $this->_editableSiteIds = [];

            foreach ($this->getAllSiteIds() as $siteId) {
                if (Craft::$app->getUser()->checkPermission('editSite:'.$siteId)) {
                    $this->_editableSiteIds[] = $siteId;
                }
            }
        }

        return $this->_editableSiteIds;
    }

    /**
     * Returns all sites.
     *
     * @param string|null $indexBy
     *
     * @return Site[] All the sites
     */
    public function getAllSites($indexBy = null)
    {
        $this->_loadAllSites();

        if ($indexBy == 'id') {
            $sites = $this->_sitesById;
        } else if ($indexBy == 'handle') {
            $sites = $this->_sitesByHandle;
        } else if (!$indexBy) {
            $sites = array_values($this->_sitesById);
        } else {
            $sites = [];

            foreach ($this->_sitesById as $site) {
                $sites[$site->$indexBy] = $site;
            }
        }

        return $sites;
    }

    /**
     * Returns all editable sites.
     *
     * @param string|null $indexBy
     *
     * @return Site[] All the editable sites
     */
    public function getEditableSites($indexBy = null)
    {
        $editableSiteIds = $this->getEditableSiteIds();
        $editableSites = [];

        foreach ($this->getAllSites() as $site) {
            if (in_array($site->id, $editableSiteIds)) {
                if ($indexBy) {
                    $editableSites[$site->$indexBy] = $site;
                } else {
                    $editableSites[] = $site;
                }
            }
        }

        return $editableSites;
    }

    /**
     * Gets the total number of sites.
     *
     * @return integer
     */
    public function getTotalSites()
    {
        return count($this->getAllSites());
    }

    /**
     * Gets the total number of sites that are editable by the current user.
     *
     * @return integer
     */
    public function getTotalEditableSites()
    {
        return count($this->getEditableSiteIds());
    }

    /**
     * Returns a site by its ID.
     *
     * @param integer $siteId
     *
     * @return Site|null
     */
    public function getSiteById($siteId)
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
    public function getSiteByHandle($siteHandle)
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
     * @param Site    $site          The site to be saved
     * @param boolean $runValidation Whether the site should be validated
     *
     * @return boolean
     * @throws SiteNotFoundException if $site->id is invalid
     * @throws \Exception if reasons
     */
    public function saveSite(Site $site, $runValidation = true)
    {
        if ($runValidation && !$site->validate()) {
            Craft::info('Site not saved due to validation error.', __METHOD__);

            return false;
        }

        if ($site->id) {
            $siteRecord = SiteRecord::find()
                ->where(['id' => $site->id])
                ->one();

            if (!$siteRecord) {
                throw new SiteNotFoundException("No site exists with the ID '{$site->id}'");
            }

            $isNewSite = false;
        } else {
            $siteRecord = new SiteRecord();
            $isNewSite = true;

            // Get the next biggest sort order
            $maxSortOrder = (new Query())
                ->select('max(sortOrder)')
                ->from('{{%sites}}')
                ->scalar();

            $siteRecord->sortOrder = $maxSortOrder ? $maxSortOrder + 1 : 1;
        }

        // Shared attributes
        $siteRecord->name = $site->name;
        $siteRecord->handle = $site->handle;
        $siteRecord->language = $site->language;
        $siteRecord->hasUrls = $site->hasUrls;
        $siteRecord->baseUrl = $site->baseUrl;

        // Fire a 'beforeSaveSite' event
        $this->trigger(self::EVENT_BEFORE_SAVE_SITE, new SiteEvent([
            'site' => $site,
            'isNew' => $isNewSite,
        ]));

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
        } catch (\Exception $e) {
            $transaction->rollBack();

            throw $e;
        }

        if (Craft::$app->getIsInstalled() && $isNewSite) {
            // TODO: Move this code into element/category modules
            // Create site settings for each of the category groups
            $allSiteSettings = (new Query())
                ->select(['groupId', 'uriFormat'])
                ->from('{{%categorygroups_i18n}}')
                ->where(['siteId' => $this->getPrimarySite()->id])
                ->all();

            if ($allSiteSettings) {
                $newSiteSettings = [];

                foreach ($allSiteSettings as $siteSettings) {
                    $newSiteSettings[] = [
                        $siteSettings['groupId'],
                        $site->id,
                        $siteSettings['uriFormat'],
                    ];
                }

                Craft::$app->getDb()->createCommand()
                    ->batchInsert(
                        '{{%categorygroups_i18n}}',
                        ['groupId', 'siteId', 'uriFormat'],
                        $newSiteSettings)
                    ->execute();
            }

            // Re-save all of the localizable elements
            if (!Craft::$app->getTasks()->areTasksPending(ResaveAllElements::class)) {
                Craft::$app->getTasks()->queueTask([
                    'type' => ResaveAllElements::class,
                    'siteId' => $this->getPrimarySite()->id,
                    'localizableOnly' => true,
                ]);
            }
        }

        // Fire an 'afterSaveSite' event
        $this->trigger(self::EVENT_AFTER_SAVE_SITE, new SiteEvent([
            'site' => $site,
            'isNew' => $isNewSite,
        ]));

        return true;
    }

    /**
     * Reorders sites.
     *
     * @param integer[] $siteIds The site IDs in their new order
     *
     * @return boolean Whether the sites were reordered successfthe sites are reorderedy
     * @throws \Exception if reasons
     */
    public function reorderSites($siteIds)
    {
        // Fire a 'beforeSaveSite' event
        $this->trigger(self::EVENT_BEFORE_REORDER_SITES, new ReorderSitesEvent([
            'siteIds' => $siteIds,
        ]));

        $this->_loadAllSites();

        $transaction = Craft::$app->getDb()->beginTransaction();

        try {
            foreach ($siteIds as $sortOrder => $siteId) {
                $siteRecord = SiteRecord::findOne($siteId);
                $siteRecord->sortOrder = $sortOrder + 1;
                $siteRecord->save();
            }

            $transaction->commit();
        } catch (\Exception $e) {
            $transaction->rollBack();

            throw $e;
        }

        // Did the primary site just change?
        $oldPrimarySiteId = $this->getPrimarySite()->id;
        $newPrimarySiteId = $siteIds[0];

        if ($newPrimarySiteId != $oldPrimarySiteId) {
            $this->_processNewPrimarySite($oldPrimarySiteId, $newPrimarySiteId);
        }

        $this->trigger(self::EVENT_AFTER_REORDER_SITES, new ReorderSitesEvent([
            'siteIds' => $siteIds,
        ]));

        return true;
    }

    /**
     * Deletes a site by its ID.
     *
     * @param integer      $siteId            The site ID to be deleted
     * @param integer|null $transferContentTo The site ID that should take over the deleted site’s contents
     *
     * @return boolean Whether the site was deleted successfully
     * @throws \Exception if reasons
     */
    public function deleteSiteById($siteId, $transferContentTo = null)
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
     * @param Site         $site              The site to be deleted
     * @param integer|null $transferContentTo The site ID that should take over the deleted site’s contents
     *
     * @return boolean Whether the site was deleted successfully
     * @throws \Exception if reasons
     */
    public function deleteSite(Site $site, $transferContentTo = null)
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
            ->select('sectionId')
            ->from('{{%sections_i18n}}')
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
        if ($soloSectionIds) {
            // Should we enable those for a different site?
            if ($transferContentTo) {
                Craft::$app->getDb()->createCommand()
                    ->update(
                        '{{%sections_i18n}}',
                        ['siteId' => $transferContentTo],
                        ['in', 'sectionId', $soloSectionIds])
                    ->execute();

                // Get all of the entry IDs in those sections
                $entryIds = (new Query())
                    ->select('id')
                    ->from('{{%entries}}')
                    ->where(['in', 'sectionId', $soloSectionIds])
                    ->column();

                if ($entryIds) {
                    // Delete their template caches
                    Craft::$app->getTemplateCaches()->deleteCachesByElementId($entryIds);

                    // Update the entry tables
                    Craft::$app->getDb()->createCommand()
                        ->update(
                            '{{%content}}',
                            ['siteId' => $transferContentTo],
                            ['in', 'elementId', $entryIds])
                        ->execute();

                    Craft::$app->getDb()->createCommand()
                        ->update(
                            '{{%elements_i18n}}',
                            ['siteId' => $transferContentTo],
                            ['in', 'elementId', $entryIds])
                        ->execute();

                    Craft::$app->getDb()->createCommand()
                        ->update(
                            '{{%entrydrafts}}',
                            ['siteId' => $transferContentTo],
                            ['in', 'entryId', $entryIds])
                        ->execute();

                    Craft::$app->getDb()->createCommand()
                        ->update(
                            '{{%entryversions}}',
                            ['siteId' => $transferContentTo],
                            ['in', 'entryId', $entryIds])
                        ->execute();

                    Craft::$app->getDb()->createCommand()
                        ->update(
                            '{{%relations}}',
                            ['sourceSiteId' => $transferContentTo],
                            [
                                'and',
                                ['in', 'sourceId', $entryIds],
                                'sourceSiteId is not null'
                            ])
                        ->execute();

                    // All the Matrix tables
                    $blockIds = (new Query())
                        ->select('id')
                        ->from('{{%matrixblocks}}')
                        ->where(['in', 'ownerId', $entryIds])
                        ->column();

                    if ($blockIds) {
                        Craft::$app->getDb()->createCommand()
                            ->update(
                                '{{%matrixblocks}}',
                                ['ownerSiteId' => $transferContentTo],
                                [
                                    'and',
                                    ['in', 'id', $blockIds],
                                    'ownerSiteId is not null'
                                ])
                            ->execute();

                        Craft::$app->getDb()->createCommand()
                            ->delete(
                                '{{%elements_i18n}}',
                                [
                                    'and',
                                    ['in', 'elementId', $blockIds],
                                    'siteId = :transferContentTo'
                                ],
                                [':transferContentTo' => $transferContentTo])
                            ->execute();

                        Craft::$app->getDb()->createCommand()
                            ->update(
                                '{{%elements_i18n}}',
                                ['siteId' => $transferContentTo],
                                [
                                    'and',
                                    ['in', 'elementId', $blockIds],
                                    'siteId = :siteId'
                                ],
                                [':siteId' => $site->id])
                            ->execute();

                        $matrixTablePrefix = Craft::$app->getDb()->getSchema()->getRawTableName('{{%matrixcontent_}}');
                        $matrixTablePrefixLength = strlen($matrixTablePrefix);
                        $tablePrefixLength = strlen(Craft::$app->getDb()->tablePrefix);

                        foreach (Craft::$app->getDb()->getSchema()->getTableNames() as $tableName) {
                            if (strncmp($tableName, $matrixTablePrefix, $matrixTablePrefixLength) === 0) {
                                $tableName = substr($tableName, $tablePrefixLength);

                                Craft::$app->getDb()->createCommand()
                                    ->delete(
                                        $tableName,
                                        [
                                            'and',
                                            ['in', 'elementId', $blockIds],
                                            'siteId = :transferContentTo'
                                        ],
                                        [':transferContentTo' => $transferContentTo])
                                    ->execute();

                                Craft::$app->getDb()->createCommand()
                                    ->update(
                                        $tableName,
                                        ['siteId' => $transferContentTo],
                                        [
                                            'and',
                                            ['in', 'elementId', $blockIds],
                                            'siteId = :siteId'
                                        ],
                                        [':siteId' => $site->id])
                                    ->execute();
                            }
                        }

                        Craft::$app->getDb()->createCommand()
                            ->update(
                                '{{%relations}}',
                                ['sourceSiteId' => $transferContentTo],
                                [
                                    'and',
                                    ['in', 'sourceId', $blockIds],
                                    'sourceSiteId is not null'
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
                ->offset(1)
                ->scalar('id');

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
        } catch (\Exception $e) {
            $transaction->rollBack();

            throw $e;
        }

        // Fire an 'afterDeleteSite' event
        $this->trigger(self::EVENT_AFTER_DELETE_SITE, new DeleteSiteEvent([
            'site' => $site,
            'transferContentTo' => $transferContentTo,
        ]));

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
        if (!$this->_fetchedAllSites) {
            $this->_sitesById = [];
            $this->_sitesByHandle = [];

            try {
                $results = $this->_createSiteQuery()->all();

                // Check for results because during installation, then transaction
                // hasn't been committed yet.
                if ($results) {
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
                // TODO: Maybe MySQL specific?
                // If the error code is 42S02, the sites table probably doesn't exist yet
                if (isset($e->errorInfo[0]) && $e->errorInfo[0] == '42S02') {
                    return;
                }

                throw $e;
            } catch (DbConnectException $e) {
                // We couldn't connect to the database and Craft isn't installed yet, so swallow this exception, too.
                if (!Craft::$app->getIsInstalled()) {
                    return;
                }

                throw $e;
            }
        }
    }

    /**
     * Returns a Query object prepped for retrieving sites.
     *
     * @return Query
     */
    private function _createSiteQuery()
    {
        return (new Query())
            ->select('id, name, handle, language, hasUrls, baseUrl')
            ->from('{{%sites}}')
            ->orderBy('sortOrder');
    }

    /**
     * Handles things that happen when there's a new primary site
     *
     * @param integer $oldPrimarySiteId
     * @param integer $newPrimaryStieId
     */
    private function _processNewPrimarySite($oldPrimarySiteId, $newPrimaryStieId)
    {
        // Set the new primary site
        $this->_primarySite = $this->getSiteById($newPrimaryStieId);

        Craft::$app->getConfig()->maxPowerCaptain();

        // Update all of the non-localized elements
        $nonLocalizedElementTypes = [];

        foreach (Craft::$app->getElements()->getAllElementTypes() as $elementType) {
            /** Element $elementType */
            if (!$elementType::isLocalized()) {
                $nonLocalizedElementTypes[] = $elementType::className();
            }
        }

        if ($nonLocalizedElementTypes) {
            $elementIds = (new Query())
                ->select('id')
                ->from('{{%elements}}')
                ->where(['in', 'type', $nonLocalizedElementTypes])
                ->column();

            if ($elementIds) {
                // To be sure we don't hit any unique constraint MySQL errors, first make sure there are no rows for
                // these elements that don't currently use the old primary site ID
                $deleteConditions = [
                    'and',
                    ['in', 'elementId', $elementIds],
                    'siteId != :siteId'
                ];
                $deleteParams = [':siteId' => $oldPrimarySiteId];

                $db = Craft::$app->getDb();

                $db->createCommand()
                    ->delete('{{%elements_i18n}}', $deleteConditions, $deleteParams)
                    ->execute();
                $db->createCommand()
                    ->delete('{{%content}}', $deleteConditions, $deleteParams)
                    ->execute();

                // Now swap the sites
                $updateColumns = ['siteId' => $newPrimaryStieId];
                $updateConditions = ['in', 'elementId', $elementIds];

                $db->createCommand()
                    ->update('{{%elements_i18n}}', $updateColumns, $updateConditions)
                    ->execute();
                $db->createCommand()
                    ->update('{{%content}}', $updateColumns, $updateConditions)
                    ->execute();
            }
        }

        // Fire an afterChangePrimarySite event
        $this->trigger(self::EVENT_AFTER_CHANGE_PRIMARY_SITE, new SiteEvent([
            'site' => $this->_primarySite,
        ]));
    }
}
