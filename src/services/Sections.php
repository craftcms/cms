<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\services;

use Craft;
use craft\base\Element;
use craft\base\MemoizableArray;
use craft\db\Query;
use craft\db\Table;
use craft\elements\Entry;
use craft\errors\EntryTypeNotFoundException;
use craft\errors\SectionNotFoundException;
use craft\events\ConfigEvent;
use craft\events\DeleteSiteEvent;
use craft\events\EntryTypeEvent;
use craft\events\FieldEvent;
use craft\events\SectionEvent;
use craft\helpers\ArrayHelper;
use craft\helpers\Db;
use craft\helpers\Json;
use craft\helpers\ProjectConfig as ProjectConfigHelper;
use craft\helpers\Queue;
use craft\helpers\StringHelper;
use craft\models\EntryType;
use craft\models\FieldLayout;
use craft\models\Section;
use craft\models\Section_SiteSettings;
use craft\models\Site;
use craft\models\Structure;
use craft\queue\jobs\ApplyNewPropagationMethod;
use craft\queue\jobs\ResaveElements;
use craft\records\EntryType as EntryTypeRecord;
use craft\records\Section as SectionRecord;
use craft\records\Section_SiteSettings as Section_SiteSettingsRecord;
use craft\web\View;
use yii\base\Component;
use yii\base\Exception;

/**
 * Sections service.
 * An instance of the Sections service is globally accessible in Craft via [[\craft\base\ApplicationTrait::getSections()|`Craft::$app->sections`]].
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class Sections extends Component
{
    /**
     * @event SectionEvent The event that is triggered before a section is saved.
     */
    const EVENT_BEFORE_SAVE_SECTION = 'beforeSaveSection';

    /**
     * @event SectionEvent The event that is triggered after a section is saved.
     */
    const EVENT_AFTER_SAVE_SECTION = 'afterSaveSection';

    /**
     * @event SectionEvent The event that is triggered before a section is deleted.
     */
    const EVENT_BEFORE_DELETE_SECTION = 'beforeDeleteSection';

    /**
     * @event SectionEvent The event that is triggered before a section delete is applied to the database.
     * @since 3.1.0
     */
    const EVENT_BEFORE_APPLY_SECTION_DELETE = 'beforeApplySectionDelete';

    /**
     * @event SectionEvent The event that is triggered after a section is deleted.
     */
    const EVENT_AFTER_DELETE_SECTION = 'afterDeleteSection';

    /**
     * @event EntryTypeEvent The event that is triggered before an entry type is saved.
     */
    const EVENT_BEFORE_SAVE_ENTRY_TYPE = 'beforeSaveEntryType';

    /**
     * @event EntryTypeEvent The event that is triggered after an entry type is saved.
     */
    const EVENT_AFTER_SAVE_ENTRY_TYPE = 'afterSaveEntryType';

    /**
     * @event EntryTypeEvent The event that is triggered before an entry type is deleted.
     */
    const EVENT_BEFORE_DELETE_ENTRY_TYPE = 'beforeDeleteEntryType';

    /**
     * @event EntryTypeEvent The event that is triggered before an entry type delete is applied to the database.
     * @since 3.1.0
     */
    const EVENT_BEFORE_APPLY_ENTRY_TYPE_DELETE = 'beforeApplyEntryTypeDelete';

    /**
     * @event EntryTypeEvent The event that is triggered after an entry type is deleted.
     */
    const EVENT_AFTER_DELETE_ENTRY_TYPE = 'afterDeleteEntryType';

    const CONFIG_SECTIONS_KEY = 'sections';

    const CONFIG_ENTRYTYPES_KEY = 'entryTypes';

    /**
     * @var bool Whether entries should be resaved after a section or entry type has been updated.
     *
     * ::: tip
     * Entries will be resaved regardless of what this is set to, when a section’s Propagation Method setting changes.
     * :::
     *
     * ::: warning
     * Don’t disable this unless you know what you’re doing, as entries won’t reflect section/entry type changes until
     * they’ve been resaved. (You can resave entries manually by running the `resave/entries` console command.)
     * :::
     *
     * @since 3.1.21
     */
    public $autoResaveEntries = true;

    /**
     * @var MemoizableArray|null
     * @see _sections()
     */
    private $_sections;

    /**
     * @var
     */
    private $_entryTypesById;

    /**
     * Serializer
     *
     * @since 3.5.14
     */
    public function __serialize()
    {
        $vars = get_object_vars($this);
        unset($vars['_sections']);
        return $vars;
    }

    // Sections
    // -------------------------------------------------------------------------

    /**
     * Returns all of the section IDs.
     *
     * ---
     *
     * ```php
     * $sectionIds = Craft::$app->sections->allSectionIds;
     * ```
     * ```twig
     * {% set sectionIds = craft.app.sections.allSectionIds %}
     * ```
     *
     * @return int[] All the sections’ IDs.
     */
    public function getAllSectionIds(): array
    {
        return ArrayHelper::getColumn($this->getAllSections(), 'id', false);
    }

    /**
     * Returns all of the section IDs that are editable by the current user.
     *
     * ---
     *
     * ```php
     * $sectionIds = Craft::$app->sections->editableSectionIds;
     * ```
     * ```twig
     * {% set sectionIds = craft.app.sections.editableSectionIds %}
     * ```
     *
     * @return int[] All the editable sections’ IDs.
     */
    public function getEditableSectionIds(): array
    {
        return ArrayHelper::getColumn($this->getEditableSections(), 'id', false);
    }

    /**
     * Returns a memoizable array of all sections.
     *
     * @return MemoizableArray
     */
    private function _sections(): MemoizableArray
    {
        if ($this->_sections === null) {
            $sections = [];
            foreach ($this->_createSectionQuery()->all() as $result) {
                if (!empty($result['previewTargets'])) {
                    $result['previewTargets'] = Json::decode($result['previewTargets']);
                } else {
                    $result['previewTargets'] = [];
                }
                $sections[] = new Section($result);
            }
            $this->_sections = new MemoizableArray($sections);
        }

        return $this->_sections;
    }

    /**
     * Returns all sections.
     *
     * ---
     *
     * ```php
     * $sections = Craft::$app->sections->allSections;
     * ```
     * ```twig
     * {% set sections = craft.app.sections.allSections %}
     * ```
     *
     * @return Section[] All the sections.
     */
    public function getAllSections(): array
    {
        return $this->_sections()->all();
    }

    /**
     * Returns all editable sections.
     *
     * ---
     *
     * ```php
     * $sections = Craft::$app->sections->editableSections;
     * ```
     * ```twig
     * {% set sections = craft.app.sections.editableSections %}
     * ```
     *
     * @return Section[] All the editable sections.
     */
    public function getEditableSections(): array
    {
        if (Craft::$app->getRequest()->getIsConsoleRequest()) {
            return $this->getAllSections();
        }

        $userSession = Craft::$app->getUser();
        return ArrayHelper::where($this->getAllSections(), function(Section $section) use ($userSession) {
            return $userSession->checkPermission('editEntries:' . $section->uid);
        }, true, true, false);
    }

    /**
     * Returns all sections of a given type.
     *
     * ---
     *
     * ```php
     * use craft\models\Section;
     *
     * $singles = Craft::$app->sections->getSectionsByType(Section::TYPE_SINGLE);
     * ```
     * ```twig
     * {% set singles = craft.app.sections.getSectionsByType('single') %}
     * ```
     *
     * @param string $type The section type (`single`, `channel`, or `structure`)
     * @return Section[] All the sections of the given type.
     */
    public function getSectionsByType(string $type): array
    {
        return $this->_sections()->where('type', $type, true)->all();
    }

    /**
     * Gets the total number of sections.
     *
     * ---
     *
     * ```php
     * $total = Craft::$app->sections->totalSections;
     * ```
     * ```twig
     * {% set total = craft.app.sections.totalSections %}
     * ```
     *
     * @return int
     */
    public function getTotalSections(): int
    {
        return count($this->getAllSections());
    }

    /**
     * Gets the total number of sections that are editable by the current user.
     *
     * ---
     *
     * ```php
     * $total = Craft::$app->sections->totalEditableSections;
     * ```
     * ```twig
     * {% set total = craft.app.sections.totalEditableSections %}
     * ```
     *
     * @return int
     */
    public function getTotalEditableSections(): int
    {
        return count($this->getEditableSections());
    }

    /**
     * Returns a section by its ID.
     *
     * ---
     *
     * ```php
     * $section = Craft::$app->sections->getSectionById(1);
     * ```
     * ```twig
     * {% set section = craft.app.sections.getSectionById(1) %}
     * ```
     *
     * @param int $sectionId
     * @return Section|null
     */
    public function getSectionById(int $sectionId)
    {
        return $this->_sections()->firstWhere('id', $sectionId);
    }

    /**
     * Gets a section by its UID.
     *
     * ---
     *
     * ```php
     * $section = Craft::$app->sections->getSectionByUid('b3a9eef3-9444-4995-84e2-6dc6b60aebd2');
     * ```
     * ```twig
     * {% set section = craft.app.sections.getSectionByUid('b3a9eef3-9444-4995-84e2-6dc6b60aebd2') %}
     * ```
     *
     * @param string $uid
     * @return Section|null
     * @since 3.1.0
     */
    public function getSectionByUid(string $uid)
    {
        return $this->_sections()->firstWhere('uid', $uid, true);
    }

    /**
     * Gets a section by its handle.
     *
     * ---
     *
     * ```php
     * $section = Craft::$app->sections->getSectionByHandle('news');
     * ```
     * ```twig
     * {% set section = craft.app.sections.getSectionByHandle('news') %}
     * ```
     *
     * @param string $sectionHandle
     * @return Section|null
     */
    public function getSectionByHandle(string $sectionHandle)
    {
        return $this->_sections()->firstWhere('handle', $sectionHandle, true);
    }

    /**
     * Returns a section’s site-specific settings.
     *
     * @param int $sectionId
     * @return Section_SiteSettings[] The section’s site-specific settings.
     */
    public function getSectionSiteSettings(int $sectionId): array
    {
        $siteSettings = (new Query())
            ->select([
                'sections_sites.id',
                'sections_sites.sectionId',
                'sections_sites.siteId',
                'sections_sites.enabledByDefault',
                'sections_sites.hasUrls',
                'sections_sites.uriFormat',
                'sections_sites.template',
            ])
            ->from(['sections_sites' => Table::SECTIONS_SITES])
            ->innerJoin(['sites' => Table::SITES], '[[sites.id]] = [[sections_sites.siteId]]')
            ->where(['sections_sites.sectionId' => $sectionId])
            ->orderBy(['sites.sortOrder' => SORT_ASC])
            ->all();

        foreach ($siteSettings as $key => $value) {
            $siteSettings[$key] = new Section_SiteSettings($value);
        }

        return $siteSettings;
    }

    /**
     * Saves a section.
     *
     * ---
     *
     * ```php
     * use craft\models\Section;
     * use craft\models\Section_SiteSettings;
     *
     * $section = new Section([
     *     'name' => 'News',
     *     'handle' => 'news',
     *     'type' => Section::TYPE_CHANNEL,
     *     'siteSettings' => [
     *         new Section_SiteSettings([
     *             'siteId' => Craft::$app->sites->getPrimarySite()->id,
     *             'enabledByDefault' => true,
     *             'hasUrls' => true,
     *             'uriFormat' => 'foo/{slug}',
     *             'template' => 'foo/_entry',
     *         ]),
     *     ]
     * ]);
     *
     * $success = Craft::$app->sections->saveSection($section);
     * ```
     *
     * @param Section $section The section to be saved
     * @param bool $runValidation Whether the section should be validated
     * @return bool
     * @throws SectionNotFoundException if $section->id is invalid
     * @throws \Throwable if reasons
     */
    public function saveSection(Section $section, bool $runValidation = true): bool
    {
        $isNewSection = !$section->id;

        // Fire a 'beforeSaveSection' event
        if ($this->hasEventHandlers(self::EVENT_BEFORE_SAVE_SECTION)) {
            $this->trigger(self::EVENT_BEFORE_SAVE_SECTION, new SectionEvent([
                'section' => $section,
                'isNew' => $isNewSection
            ]));
        }

        if ($runValidation && !$section->validate()) {
            Craft::info('Section not saved due to validation error.', __METHOD__);
            return false;
        }

        if ($isNewSection) {
            $section->uid = StringHelper::UUID();
        } else if (!$section->uid) {
            $section->uid = Db::uidById(Table::SECTIONS, $section->id);
        }

        // Main section settings
        if ($section->type === Section::TYPE_SINGLE) {
            $section->propagationMethod = Section::PROPAGATION_METHOD_ALL;
        }

        // Assemble the section config
        // -----------------------------------------------------------------

        // Do everything that follows in a transaction so no DB changes will be
        // saved if an exception occurs that ends up preventing the project config
        // changes from getting saved
        $transaction = Craft::$app->getDb()->beginTransaction();

        try {
            // Save the section config
            // -----------------------------------------------------------------

            $configPath = self::CONFIG_SECTIONS_KEY . '.' . $section->uid;
            $configData = $section->getConfig();
            Craft::$app->getProjectConfig()->set($configPath, $configData, "Save section “{$section->handle}”");

            if ($isNewSection) {
                $section->id = Db::idByUid(Table::SECTIONS, $section->uid);
            }

            // Make sure there's at least one entry type for this section
            // -----------------------------------------------------------------

            if (!$isNewSection) {
                $entryTypeExists = (new Query())
                    ->select(['id'])
                    ->from([Table::ENTRYTYPES])
                    ->where(['sectionId' => $section->id])
                    ->exists();
            } else {
                $entryTypeExists = false;
            }

            if (!$entryTypeExists) {
                $entryType = new EntryType();
                $entryType->sectionId = $section->id;
                $entryType->name = $section->name;
                $entryType->handle = $section->handle;

                if ($section->type === Section::TYPE_SINGLE) {
                    $entryType->hasTitleField = false;
                    $entryType->titleFormat = '{section.name|raw}';
                } else {
                    $entryType->hasTitleField = true;
                    $entryType->titleFormat = null;
                }

                $this->saveEntryType($entryType);
                $section->setEntryTypes([$entryType]);
            }

            // Special handling for Single sections
            // -----------------------------------------------------------------

            if ($section->type === Section::TYPE_SINGLE) {
                // Ensure & get the single entry
                $entry = $this->_ensureSingleEntry($section, $configData['siteSettings']);

                // Deal with the section's entry types
                if (!$isNewSection) {
                    foreach ($this->getEntryTypesBySectionId($section->id) as $entryType) {
                        if ($entryType->id == $entry->typeId) {
                            // This is *the* entry's type. Make sure its name & handle match the section's
                            if ($entryType->name !== $section->name || $entryType->handle !== $section->handle) {
                                $entryType->name = $section->name;
                                $entryType->handle = $section->handle;
                                $this->saveEntryType($entryType);
                            }

                            $section->setEntryTypes([$entryType]);
                        } else {
                            // We don't need this one anymore
                            $this->deleteEntryType($entryType);
                        }
                    }
                }
            }

            $transaction->commit();
        } catch (\Throwable $e) {
            $transaction->rollBack();
            throw $e;
        }

        return true;
    }

    /**
     * Handle section change
     *
     * @param ConfigEvent $event
     */
    public function handleChangedSection(ConfigEvent $event)
    {
        ProjectConfigHelper::ensureAllSitesProcessed();
        ProjectConfigHelper::ensureAllFieldsProcessed();

        $sectionUid = $event->tokenMatches[0];
        $data = $event->newValue;

        $db = Craft::$app->getDb();
        $transaction = $db->beginTransaction();

        try {
            $siteSettingData = $data['siteSettings'];

            // Basic data
            $sectionRecord = $this->_getSectionRecord($sectionUid, true);
            $sectionRecord->uid = $sectionUid;
            $sectionRecord->name = $data['name'];
            $sectionRecord->handle = $data['handle'];
            $sectionRecord->type = $data['type'];
            $sectionRecord->enableVersioning = (bool)$data['enableVersioning'];
            $sectionRecord->propagationMethod = $data['propagationMethod'] ?? Section::PROPAGATION_METHOD_ALL;
            $sectionRecord->previewTargets = isset($data['previewTargets']) && is_array($data['previewTargets'])
                ? ProjectConfigHelper::unpackAssociativeArray($data['previewTargets'])
                : null;

            $isNewSection = $sectionRecord->getIsNewRecord();
            $propagationMethodChanged = $sectionRecord->propagationMethod != $sectionRecord->getOldAttribute('propagationMethod');

            if ($data['type'] === Section::TYPE_STRUCTURE) {
                // Save the structure
                $structureUid = $data['structure']['uid'];
                $structure = Craft::$app->getStructures()->getStructureByUid($structureUid, true) ?? new Structure(['uid' => $structureUid]);
                $isNewStructure = empty($structure->id);
                $structure->maxLevels = $data['structure']['maxLevels'];
                Craft::$app->getStructures()->saveStructure($structure);
                $sectionRecord->structureId = $structure->id;
            } else if (!$isNewSection && $sectionRecord->structureId) {
                // Delete the old one
                Craft::$app->getStructures()->deleteStructureById($sectionRecord->structureId);
                $sectionRecord->structureId = null;
                $isNewStructure = false;
            }

            $resaveEntries = (
                $sectionRecord->handle !== $sectionRecord->getOldAttribute('handle') ||
                $sectionRecord->type !== $sectionRecord->getOldAttribute('type') ||
                $propagationMethodChanged ||
                $sectionRecord->structureId != $sectionRecord->getOldAttribute('structureId')
            );

            if ($sectionRecord->dateDeleted) {
                $sectionRecord->restore();
                $resaveEntries = true;
            } else {
                $sectionRecord->save(false);
            }

            // Update the site settings
            // -----------------------------------------------------------------

            if (!$isNewSection) {
                // Get the old section site settings
                $allOldSiteSettingsRecords = Section_SiteSettingsRecord::find()
                    ->where(['sectionId' => $sectionRecord->id])
                    ->indexBy('siteId')
                    ->all();
            } else {
                $allOldSiteSettingsRecords = [];
            }

            $siteIdMap = Db::idsByUids(Table::SITES, array_keys($siteSettingData));
            $hasNewSite = false;

            foreach ($siteSettingData as $siteUid => $siteSettings) {
                $siteId = $siteIdMap[$siteUid];

                // Was this already selected?
                if (!$isNewSection && isset($allOldSiteSettingsRecords[$siteId])) {
                    $siteSettingsRecord = $allOldSiteSettingsRecords[$siteId];
                } else {
                    $siteSettingsRecord = new Section_SiteSettingsRecord();
                    $siteSettingsRecord->sectionId = $sectionRecord->id;
                    $siteSettingsRecord->siteId = $siteId;
                    $resaveEntries = true;
                    $hasNewSite = true;
                }

                $siteSettingsRecord->enabledByDefault = $siteSettings['enabledByDefault'];

                if ($siteSettingsRecord->hasUrls = $siteSettings['hasUrls']) {
                    $siteSettingsRecord->uriFormat = $siteSettings['uriFormat'];
                    $siteSettingsRecord->template = $siteSettings['template'];
                } else {
                    $siteSettingsRecord->uriFormat = $siteSettings['uriFormat'] = null;
                    $siteSettingsRecord->template = $siteSettings['template'] = null;
                }

                $resaveEntries = (
                    $resaveEntries ||
                    $siteSettingsRecord->hasUrls != $siteSettingsRecord->getOldAttribute('hasUrls') ||
                    $siteSettingsRecord->uriFormat !== $siteSettingsRecord->getOldAttribute('uriFormat')
                );

                $siteSettingsRecord->save(false);
            }

            if (!$isNewSection) {
                // Drop any sites that are no longer being used, as well as the associated entry/element site
                // rows
                $affectedSiteUids = array_keys($siteSettingData);

                /** @noinspection PhpUndefinedVariableInspection */
                foreach ($allOldSiteSettingsRecords as $siteId => $siteSettingsRecord) {
                    $siteUid = array_search($siteId, $siteIdMap, false);
                    if (!in_array($siteUid, $affectedSiteUids, false)) {
                        $siteSettingsRecord->delete();
                        $resaveEntries = true;
                    }
                }
            }

            // If the section was just converted to a Structure,
            // add the existing entries to the structure
            // -----------------------------------------------------------------

            if (
                $sectionRecord->type === Section::TYPE_STRUCTURE &&
                !$isNewSection &&
                $isNewStructure
            ) {
                $this->_populateNewStructure($sectionRecord);
            }

            // Finally, deal with the existing entries...
            // -----------------------------------------------------------------

            if (!$isNewSection && $resaveEntries) {
                // If the propagation method just changed, we definitely need to update entries for that
                if ($propagationMethodChanged) {
                    Queue::push(new ApplyNewPropagationMethod([
                        'description' => Craft::t('app', 'Applying new propagation method to {section} entries', [
                            'section' => $sectionRecord->name,
                        ]),
                        'elementType' => Entry::class,
                        'criteria' => [
                            'sectionId' => $sectionRecord->id,
                            'structureId' => $sectionRecord->structureId,
                        ],
                    ]));
                } else if ($this->autoResaveEntries) {
                    Queue::push(new ResaveElements([
                        'description' => Craft::t('app', 'Resaving {section} entries', [
                            'section' => $sectionRecord->name,
                        ]),
                        'elementType' => Entry::class,
                        'criteria' => [
                            'sectionId' => $sectionRecord->id,
                            'siteId' => array_values($siteIdMap),
                            'unique' => true,
                            'status' => null,
                        ],
                        'updateSearchIndex' => $hasNewSite,
                    ]));
                }
            }

            $transaction->commit();
        } catch (\Throwable $e) {
            $transaction->rollBack();
            throw $e;
        }

        // Clear caches
        $this->_sections = null;

        /** @var Section $section */
        $section = $this->getSectionById($sectionRecord->id);

        // If this is a Single and no entry type changes need to be processed,
        // ensure that the section has its one and only entry
        if (
            !$isNewSection &&
            $section->type === Section::TYPE_SINGLE &&
            !Craft::$app->getProjectConfig()->getIsApplyingYamlChanges()
        ) {
            $this->_ensureSingleEntry($section, $siteSettingData);
        }

        // Fire an 'afterSaveSection' event
        if ($this->hasEventHandlers(self::EVENT_AFTER_SAVE_SECTION)) {
            $this->trigger(self::EVENT_AFTER_SAVE_SECTION, new SectionEvent([
                'section' => $section,
                'isNew' => $isNewSection
            ]));
        }

        // Invalidate entry caches
        Craft::$app->getElements()->invalidateCachesForElementType(Entry::class);
    }

    /**
     * Deletes a section by its ID.
     *
     * ---
     *
     * ```php
     * $success = Craft::$app->sections->deleteSectionById(1);
     * ```
     *
     * @param int $sectionId
     * @return bool Whether the section was deleted successfully
     * @throws \Throwable if reasons
     */
    public function deleteSectionById(int $sectionId): bool
    {
        $section = $this->getSectionById($sectionId);

        if (!$section) {
            return false;
        }

        return $this->deleteSection($section);
    }

    /**
     * Deletes a section.
     *
     * ---
     *
     * ```php
     * $success = Craft::$app->sections->deleteSection($section);
     * ```
     *
     * @param Section $section
     * @return bool Whether the section was deleted successfully
     * @throws \Throwable if reasons
     */
    public function deleteSection(Section $section): bool
    {
        // Fire a 'beforeDeleteSection' event
        if ($this->hasEventHandlers(self::EVENT_BEFORE_DELETE_SECTION)) {
            $this->trigger(self::EVENT_BEFORE_DELETE_SECTION, new SectionEvent([
                'section' => $section,
            ]));
        }

        // Delete the entry types first
        $entryTypes = $this->getEntryTypesBySectionId($section->id);
        foreach ($entryTypes as $entryType) {
            $this->deleteEntryType($entryType);
        }

        // Remove the section from the project config
        Craft::$app->getProjectConfig()->remove(self::CONFIG_SECTIONS_KEY . '.' . $section->uid, "Delete the “{$section->handle}” section");
        return true;
    }

    /**
     * Handle a section getting deleted
     *
     * @param ConfigEvent $event
     */
    public function handleDeletedSection(ConfigEvent $event)
    {
        $uid = $event->tokenMatches[0];
        $sectionRecord = $this->_getSectionRecord($uid);

        if (!$sectionRecord->id) {
            return;
        }

        /** @var Section $section */
        $section = $this->getSectionById($sectionRecord->id);

        // Fire a 'beforeApplySectionDelete' event
        if ($this->hasEventHandlers(self::EVENT_BEFORE_APPLY_SECTION_DELETE)) {
            $this->trigger(self::EVENT_BEFORE_APPLY_SECTION_DELETE, new SectionEvent([
                'section' => $section,
            ]));
        }

        $transaction = Craft::$app->getDb()->beginTransaction();
        try {
            // All entries *should* be deleted by now via their entry types, but loop through all the sites in case
            // there are any lingering entries from unsupported sites
            $entryQuery = Entry::find()
                ->anyStatus()
                ->sectionId($sectionRecord->id);
            $elementsService = Craft::$app->getElements();
            foreach (Craft::$app->getSites()->getAllSiteIds() as $siteId) {
                foreach ($entryQuery->siteId($siteId)->each() as $entry) {
                    $elementsService->deleteElement($entry);
                }
            }

            // Delete the structure
            if ($sectionRecord->structureId) {
                Craft::$app->getStructures()->deleteStructureById($sectionRecord->structureId);
            }

            // Delete the section
            Craft::$app->getDb()->createCommand()
                ->softDelete(Table::SECTIONS, ['id' => $sectionRecord->id])
                ->execute();

            $transaction->commit();
        } catch (\Throwable $e) {
            $transaction->rollBack();
            throw $e;
        }

        // Clear caches
        $this->_sections = null;

        // Fire an 'afterDeleteSection' event
        if ($this->hasEventHandlers(self::EVENT_AFTER_DELETE_SECTION)) {
            $this->trigger(self::EVENT_AFTER_DELETE_SECTION, new SectionEvent([
                'section' => $section,
            ]));
        }

        // Invalidate entry caches
        Craft::$app->getElements()->invalidateCachesForElementType(Entry::class);
    }

    /**
     * Prune a deleted site from section site settings.
     *
     * @param DeleteSiteEvent $event
     */
    public function pruneDeletedSite(DeleteSiteEvent $event)
    {
        $siteUid = $event->site->uid;

        $projectConfig = Craft::$app->getProjectConfig();
        $sections = $projectConfig->get(self::CONFIG_SECTIONS_KEY);

        // Loop through the sections and prune the UID from field layouts.
        if (is_array($sections)) {
            foreach ($sections as $sectionUid => $sectionGroup) {
                $projectConfig->remove(self::CONFIG_SECTIONS_KEY . '.' . $sectionUid . '.siteSettings.' . $siteUid, 'Remove section settings that belong to a site being deleted');
            }
        }
    }

    /**
     * Prune a deleted field from entry type layouts.
     *
     * @param FieldEvent $event
     * @since 3.1.20
     */
    public function pruneDeletedField(FieldEvent $event)
    {
        $field = $event->field;
        $fieldUid = $field->uid;

        $projectConfig = Craft::$app->getProjectConfig();
        $entryTypes = $projectConfig->get(self::CONFIG_ENTRYTYPES_KEY);

        // Engage stealth mode
        $projectConfig->muteEvents = true;

        // Loop through the tag groups and prune the UID from field layouts.
        if (is_array($entryTypes)) {
            foreach ($entryTypes as $entryTypeUid => $entryType) {
                if (!empty($entryType['fieldLayouts'])) {
                    foreach ($entryType['fieldLayouts'] as $layoutUid => $layout) {
                        if (!empty($layout['tabs'])) {
                            foreach ($layout['tabs'] as $tabUid => $tab) {
                                $projectConfig->remove(self::CONFIG_ENTRYTYPES_KEY . '.' . $entryTypeUid . '.fieldLayouts.' . $layoutUid . '.tabs.' . $tabUid . '.fields.' . $fieldUid, 'Prune deleted field');
                            }
                        }
                    }
                }
            }
        }

        // Nuke all the layout fields from the DB
        Db::delete(Table::FIELDLAYOUTFIELDS, [
            'fieldId' => $field->id,
        ]);

        // Allow events again
        $projectConfig->muteEvents = false;
    }

    /**
     * Returns whether a section’s entries have URLs for the given site ID, and if the section’s template path is valid.
     *
     * @param Section $section
     * @param int $siteId
     * @return bool
     * @deprecated in 3.3.0
     */
    public function isSectionTemplateValid(Section $section, int $siteId): bool
    {
        $sectionSiteSettings = $section->getSiteSettings();

        if (!isset($sectionSiteSettings[$siteId]) || !$sectionSiteSettings[$siteId]->hasUrls) {
            return false;
        }

        $template = (string)$sectionSiteSettings[$siteId]->template;
        return Craft::$app->getView()->doesTemplateExist($template, View::TEMPLATE_MODE_SITE);
    }

    // Entry Types
    // -------------------------------------------------------------------------

    /**
     * Returns a section’s entry types.
     *
     * ---
     *
     * ```php
     * $entryTypes = Craft::$app->sections->getEntryTypesBySectionId(1);
     * ```
     *
     * @param int $sectionId
     * @return EntryType[]
     */
    public function getEntryTypesBySectionId(int $sectionId): array
    {
        $results = $this->_createEntryTypeQuery()
            ->andWhere(['sectionId' => $sectionId])
            ->orderBy(['sortOrder' => SORT_ASC])
            ->all();

        foreach ($results as $key => $result) {
            $results[$key] = new EntryType($result);
        }

        return $results;
    }

    /**
     * Returns all entry types.
     *
     * ---
     *
     * ```php
     * $entryTypes = Craft::$app->sections->getAllEntryTypes(1);
     * ```
     *
     * @return EntryType[]
     * @since 3.3.0
     */
    public function getAllEntryTypes(): array
    {
        $results = $this->_createEntryTypeQuery()
            ->all();

        foreach ($results as $key => $result) {
            $results[$key] = new EntryType($result);
        }

        return $results;
    }

    /**
     * Returns an entry type by its ID.
     *
     * ---
     *
     * ```php
     * $entryType = Craft::$app->sections->getEntryTypeById(1);
     * ```
     *
     * @param int $entryTypeId
     * @return EntryType|null
     */
    public function getEntryTypeById(int $entryTypeId)
    {
        if (!$entryTypeId) {
            return null;
        }

        if ($this->_entryTypesById !== null && array_key_exists($entryTypeId, $this->_entryTypesById)) {
            return $this->_entryTypesById[$entryTypeId];
        }

        $result = $this->_createEntryTypeQuery()
            ->andWhere(['id' => $entryTypeId])
            ->one();

        return $this->_entryTypesById[$entryTypeId] = $result ? new EntryType($result) : null;
    }

    /**
     * Returns entry types that have a given handle.
     *
     * ---
     *
     * ```php
     * $entryTypes = Craft::$app->sections->getEntryTypesByHandle('article');
     * ```
     *
     * @param string $entryTypeHandle
     * @return EntryType[]
     */
    public function getEntryTypesByHandle(string $entryTypeHandle): array
    {
        $results = $this->_createEntryTypeQuery()
            ->andWhere(['handle' => $entryTypeHandle])
            ->all();

        foreach ($results as $key => $result) {
            $results[$key] = new EntryType($result);
        }

        return $results;
    }

    /**
     * Saves an entry type.
     *
     * @param EntryType $entryType The entry type to be saved
     * @param bool $runValidation Whether the entry type should be validated
     * @return bool Whether the entry type was saved successfully
     * @throws EntryTypeNotFoundException if $entryType->id is invalid
     * @throws \Throwable if reasons
     */
    public function saveEntryType(EntryType $entryType, bool $runValidation = true): bool
    {
        $isNewEntryType = !$entryType->id;

        // Fire a 'beforeSaveEntryType' event
        if ($this->hasEventHandlers(self::EVENT_BEFORE_SAVE_ENTRY_TYPE)) {
            $this->trigger(self::EVENT_BEFORE_SAVE_ENTRY_TYPE, new EntryTypeEvent([
                'entryType' => $entryType,
                'isNew' => $isNewEntryType,
            ]));
        }

        if ($runValidation && !$entryType->validate()) {
            Craft::info('Entry type not saved due to validation error.', __METHOD__);
            return false;
        }

        if ($isNewEntryType) {
            $entryType->uid = StringHelper::UUID();

            $maxSortOrder = (new Query())
                ->from([Table::ENTRYTYPES])
                ->where([
                    'sectionId' => $entryType->sectionId,
                    'dateDeleted' => null,
                ])
                ->max('[[sortOrder]]');
            $entryType->sortOrder = $maxSortOrder ? $maxSortOrder + 1 : 1;
        }

        $configPath = self::CONFIG_ENTRYTYPES_KEY . '.' . $entryType->uid;
        $configData = $entryType->getConfig();
        Craft::$app->getProjectConfig()->set($configPath, $configData, "Save entry type “{$entryType->handle}”");

        if ($isNewEntryType) {
            $entryType->id = Db::idByUid(Table::ENTRYTYPES, $entryType->uid);
        }

        return true;
    }

    /**
     * Handle entry type change
     *
     * @param ConfigEvent $event
     */
    public function handleChangedEntryType(ConfigEvent $event)
    {
        $entryTypeUid = $event->tokenMatches[0];
        $data = $event->newValue;

        // Make sure fields are processed
        ProjectConfigHelper::ensureAllSitesProcessed();
        ProjectConfigHelper::ensureAllFieldsProcessed();

        $section = $this->getSectionByUid($data['section']);
        $entryTypeRecord = $this->_getEntryTypeRecord($entryTypeUid, true);

        if (!$section || !$entryTypeRecord) {
            return;
        }

        $transaction = Craft::$app->getDb()->beginTransaction();

        try {
            $isNewEntryType = $entryTypeRecord->getIsNewRecord();

            $entryTypeRecord->name = $data['name'];
            $entryTypeRecord->handle = $data['handle'];
            $entryTypeRecord->hasTitleField = $data['hasTitleField'];
            $entryTypeRecord->titleTranslationMethod = $data['titleTranslationMethod'] ?? '';
            $entryTypeRecord->titleTranslationKeyFormat = $data['titleTranslationKeyFormat'] ?? null;
            $entryTypeRecord->titleFormat = $data['titleFormat'];
            $entryTypeRecord->sortOrder = $data['sortOrder'];
            $entryTypeRecord->sectionId = $section->id;
            $entryTypeRecord->uid = $entryTypeUid;

            if (!empty($data['fieldLayouts'])) {
                // Save the field layout
                $layout = FieldLayout::createFromConfig(reset($data['fieldLayouts']));
                $layout->id = $entryTypeRecord->fieldLayoutId;
                $layout->type = Entry::class;
                $layout->uid = key($data['fieldLayouts']);
                Craft::$app->getFields()->saveLayout($layout);
                $entryTypeRecord->fieldLayoutId = $layout->id;
            } else if ($entryTypeRecord->fieldLayoutId) {
                // Delete the field layout
                Craft::$app->getFields()->deleteLayoutById($entryTypeRecord->fieldLayoutId);
                $entryTypeRecord->fieldLayoutId = null;
            }

            $resaveEntries = (
                $entryTypeRecord->handle !== $entryTypeRecord->getOldAttribute('handle') ||
                $entryTypeRecord->hasTitleField != $entryTypeRecord->getOldAttribute('hasTitleField') ||
                $entryTypeRecord->titleFormat !== $entryTypeRecord->getOldAttribute('titleFormat') ||
                $entryTypeRecord->fieldLayoutId != $entryTypeRecord->getOldAttribute('fieldLayoutId')
            );

            // Save the entry type
            if ($wasTrashed = (bool)$entryTypeRecord->dateDeleted) {
                $entryTypeRecord->restore();
                $resaveEntries = true;
            } else {
                $entryTypeRecord->save(false);
            }

            $transaction->commit();
        } catch (\Throwable $e) {
            $transaction->rollBack();
            throw $e;
        }

        // Clear caches
        unset($this->_entryTypesById[$entryTypeRecord->id]);

        if ($wasTrashed) {
            // Restore the entries that were deleted with the entry type
            $entries = Entry::find()
                ->sectionId($entryTypeRecord->sectionId)
                ->typeId($entryTypeRecord->id)
                ->anyStatus()
                ->trashed()
                ->siteId('*')
                ->unique()
                ->andWhere(['entries.deletedWithEntryType' => true])
                ->all();
            Craft::$app->getElements()->restoreElements($entries);
        }

        /** @var EntryType $entryType */
        $entryType = $this->getEntryTypeById($entryTypeRecord->id);

        // If this is for a Single section, ensure its entry exists
        if ($section->type === Section::TYPE_SINGLE) {
            $this->_ensureSingleEntry($section);
        } else if (!$isNewEntryType && $resaveEntries && $this->autoResaveEntries) {
            // Re-save the entries of this type
            Queue::push(new ResaveElements([
                'description' => Craft::t('app', 'Resaving {type} entries', [
                    'type' => ($section->type !== Section::TYPE_SINGLE ? $section->name . ' - ' : '') . $entryType->name,
                ]),
                'elementType' => Entry::class,
                'criteria' => [
                    'sectionId' => $section->id,
                    'typeId' => $entryType->id,
                    'siteId' => '*',
                    'unique' => true,
                    'status' => null,
                ]
            ]));
        }

        // Fire an 'afterSaveEntryType' event
        if ($this->hasEventHandlers(self::EVENT_AFTER_SAVE_ENTRY_TYPE)) {
            $this->trigger(self::EVENT_AFTER_SAVE_ENTRY_TYPE, new EntryTypeEvent([
                'entryType' => $entryType,
                'isNew' => $isNewEntryType,
            ]));
        }

        // Invalidate entry caches
        Craft::$app->getElements()->invalidateCachesForElementType(Entry::class);
    }

    /**
     * Reorders entry types.
     *
     * @param array $entryTypeIds
     * @return bool Whether the entry types were reordered successfully
     * @throws \Throwable if reasons
     */
    public function reorderEntryTypes(array $entryTypeIds): bool
    {
        $projectConfig = Craft::$app->getProjectConfig();

        $sectionRecord = null;

        $uidsByIds = Db::uidsByIds(Table::ENTRYTYPES, $entryTypeIds);

        foreach ($entryTypeIds as $entryTypeOrder => $entryTypeId) {
            if (!empty($uidsByIds[$entryTypeId])) {
                $entryTypeUid = $uidsByIds[$entryTypeId];
                $entryTypeRecord = $this->_getEntryTypeRecord($entryTypeUid);

                if (!$sectionRecord) {
                    $sectionRecord = SectionRecord::findOne($entryTypeRecord->sectionId);
                }

                $configPath = self::CONFIG_ENTRYTYPES_KEY . '.' . $entryTypeUid . '.sortOrder';
                $projectConfig->set($configPath, $entryTypeOrder + 1, 'Reorder entry types');
            }
        }

        return true;
    }

    /**
     * Deletes an entry type by its ID.
     *
     * ---
     *
     * ```php
     * $success = Craft::$app->sections->deleteEntryTypeById(1);
     * ```
     *
     * @param int $entryTypeId
     * @return bool Whether the entry type was deleted successfully
     * @throws \Throwable if reasons
     */
    public function deleteEntryTypeById(int $entryTypeId): bool
    {
        $entryType = $this->getEntryTypeById($entryTypeId);

        if (!$entryType) {
            return false;
        }

        return $this->deleteEntryType($entryType);
    }

    /**
     * Deletes an entry type.
     *
     * ---
     *
     * ```php
     * $success = Craft::$app->sections->deleteEntry($entryType);
     * ```
     *
     * @param EntryType $entryType
     * @return bool Whether the entry type was deleted successfully
     * @throws \Throwable if reasons
     */
    public function deleteEntryType(EntryType $entryType): bool
    {
        // Fire a 'beforeSaveEntryType' event
        if ($this->hasEventHandlers(self::EVENT_BEFORE_DELETE_ENTRY_TYPE)) {
            $this->trigger(self::EVENT_BEFORE_DELETE_ENTRY_TYPE, new EntryTypeEvent([
                'entryType' => $entryType,
            ]));
        }

        Craft::$app->getProjectConfig()->remove(self::CONFIG_ENTRYTYPES_KEY . '.' . $entryType->uid, "Delete the “{$entryType->handle}” entry type");
        return true;
    }

    /**
     * Handle an entry type getting deleted
     *
     * @param ConfigEvent $event
     */
    public function handleDeletedEntryType(ConfigEvent $event)
    {
        $uid = $event->tokenMatches[0];
        $entryTypeRecord = $this->_getEntryTypeRecord($uid);

        if (!$entryTypeRecord->id) {
            return;
        }

        /** @var EntryType $entryType */
        $entryType = $this->getEntryTypeById($entryTypeRecord->id);

        // Fire a 'beforeApplyEntryTypeDelete' event
        if ($this->hasEventHandlers(self::EVENT_BEFORE_APPLY_ENTRY_TYPE_DELETE)) {
            $this->trigger(self::EVENT_BEFORE_APPLY_ENTRY_TYPE_DELETE, new EntryTypeEvent([
                'entryType' => $entryType,
            ]));
        }

        $transaction = Craft::$app->getDb()->beginTransaction();

        try {
            // Delete the entries
            // (loop through all the sites in case there are any lingering entries from unsupported sites
            $entryQuery = Entry::find()
                ->anyStatus()
                ->typeId($entryTypeRecord->id);

            $elementsService = Craft::$app->getElements();
            foreach (Craft::$app->getSites()->getAllSiteIds() as $siteId) {
                foreach ($entryQuery->siteId($siteId)->each() as $entry) {
                    /** @var Entry $entry */
                    $entry->deletedWithEntryType = true;
                    $elementsService->deleteElement($entry);
                }
            }

            // Delete the field layout
            if ($entryTypeRecord->fieldLayoutId) {
                Craft::$app->getFields()->deleteLayoutById($entryTypeRecord->fieldLayoutId);
            }

            // Delete the entry type.
            Craft::$app->getDb()->createCommand()
                ->softDelete(Table::ENTRYTYPES, ['id' => $entryTypeRecord->id])
                ->execute();

            $transaction->commit();
        } catch (\Throwable $e) {
            $transaction->rollBack();
            throw $e;
        }

        // Clear caches
        unset($this->_entryTypesById[$entryType->id]);

        // Fire an 'afterDeleteEntryType' event
        if ($this->hasEventHandlers(self::EVENT_AFTER_DELETE_ENTRY_TYPE)) {
            $this->trigger(self::EVENT_AFTER_DELETE_ENTRY_TYPE, new EntryTypeEvent([
                'entryType' => $entryType,
            ]));
        }

        // Invalidate entry caches
        Craft::$app->getElements()->invalidateCachesForElementType(Entry::class);
    }

    /**
     * Returns a Query object prepped for retrieving sections.
     *
     * @return Query
     */
    private function _createSectionQuery(): Query
    {
        // todo: remove schema version condition after next beakpoint
        $condition = null;
        $joinCondition = '[[structures.id]] = [[sections.structureId]]';
        $schemaVersion = Craft::$app->getInstalledSchemaVersion();
        if (version_compare($schemaVersion, '3.1.19', '>=')) {
            $condition = ['sections.dateDeleted' => null];
            $joinCondition = [
                'and',
                $joinCondition,
                ['structures.dateDeleted' => null]
            ];
        }

        $query = (new Query())
            ->select([
                'sections.id',
                'sections.structureId',
                'sections.name',
                'sections.handle',
                'sections.type',
                'sections.enableVersioning',
                'sections.uid',
                'structures.maxLevels',
            ])
            ->leftJoin(['structures' => Table::STRUCTURES], $joinCondition)
            ->from(['sections' => Table::SECTIONS])
            ->where($condition)
            ->orderBy(['name' => SORT_ASC]);

        // todo: remove schema version conditions after next beakpoint
        if (version_compare($schemaVersion, '3.2.1', '>=')) {
            $query->addSelect('sections.propagationMethod');
        }
        if (version_compare($schemaVersion, '3.2.6', '>=')) {
            $query->addSelect('sections.previewTargets');
        }

        return $query;
    }

    /**
     * Ensures that the given Single section has its one and only entry, and returns it.
     *
     * @param Section $section
     * @param array|null $siteSettings
     * @return Entry The
     * @throws Exception if reasons
     * @see saveSection()
     */
    private function _ensureSingleEntry(Section $section, array $siteSettings = null): Entry
    {
        // Get the section's supported sites
        // ---------------------------------------------------------------------

        if ($siteSettings === null) {
            $siteSettings = Craft::$app->getProjectConfig()->get(self::CONFIG_SECTIONS_KEY . '.' . $section->uid . '.siteSettings');
        }

        if (empty($siteSettings)) {
            throw new Exception('No site settings exist for section ' . $section->id);
        }

        $sites = ArrayHelper::where(Craft::$app->getSites()->getAllSites(), function(Site $site) use ($siteSettings) {
            // Only include it if it's one of this section's sites
            return isset($siteSettings[$site->uid]);
        }, true, true, false);

        $siteIds = ArrayHelper::getColumn($sites, 'id');

        // Get the section's entry types
        // ---------------------------------------------------------------------

        $entryTypeIds = ArrayHelper::getColumn($this->getEntryTypesBySectionId($section->id), 'id', false);

        // There should always be at least one entry type by the time this is called
        if (empty($entryTypeIds)) {
            throw new Exception('No entry types exist for section ' . $section->id);
        }

        // Get/save the entry with updated title, slug, and URI format
        // ---------------------------------------------------------------------

        // If there are any existing entries, find the first one with a valid typeId
        $entry = Entry::find()
            ->typeId($entryTypeIds)
            ->siteId($siteIds)
            ->anyStatus()
            ->one();

        // Otherwise create a new one
        if ($entry === null) {
            // Create one
            $entry = new Entry();
            $entry->siteId = $siteIds[0];
            $entry->sectionId = $section->id;
            $entry->typeId = $entryTypeIds[0];
            $entry->title = $section->name;
        }

        // Validate first
        $entry->setScenario(Element::SCENARIO_ESSENTIALS);
        $entry->validate();

        // If there are any errors on the URI, re-validate as disabled
        if ($entry->hasErrors('uri') && $entry->enabled) {
            $entry->enabled = false;
            $entry->validate();
        }

        if (
            $entry->hasErrors() ||
            !Craft::$app->getElements()->saveElement($entry, false)
        ) {
            throw new Exception("Couldn’t save single entry for section $section->name due to validation errors: " . implode(', ', $entry->getFirstErrors()));
        }

        // Delete any other entries in the section
        // ---------------------------------------------------------------------

        $elementsService = Craft::$app->getElements();
        $otherEntriesQuery = Entry::find()
            ->sectionId($section->id)
            ->siteId('*')
            ->unique()
            ->id(['not', $entry->id])
            ->anyStatus();

        foreach ($otherEntriesQuery->each() as $entry) {
            $elementsService->deleteElement($entry, true);
        }

        return $entry;
    }

    /**
     * Adds existing entries to a newly-created structure, if the section type was just converted to Structure.
     *
     * @param SectionRecord $sectionRecord
     * @throws Exception if reasons
     * @see saveSection()
     */
    private function _populateNewStructure(SectionRecord $sectionRecord)
    {
        // Add all of the entries to the structure
        $query = Entry::find()
            ->sectionId($sectionRecord->id)
            ->siteId('*')
            ->unique()
            ->anyStatus()
            ->orderBy(['elements.id' => SORT_ASC])
            ->withStructure(false);

        $structuresService = Craft::$app->getStructures();

        /** @var Entry $entry */
        foreach ($query->each() as $entry) {
            $structuresService->appendToRoot($sectionRecord->structureId, $entry, Structures::MODE_INSERT);
        }
    }

    /**
     * @return Query
     */
    private function _createEntryTypeQuery()
    {
        $query = (new Query())
            ->select([
                'id',
                'sectionId',
                'fieldLayoutId',
                'name',
                'handle',
                'sortOrder',
                'hasTitleField',
                'titleFormat',
                'uid',
            ])
            ->from([Table::ENTRYTYPES]);

        // todo: remove schema version conditions after next beakpoint
        $schemaVersion = Craft::$app->getInstalledSchemaVersion();
        if (version_compare($schemaVersion, '3.1.19', '>=')) {
            $query->where(['dateDeleted' => null]);
        }
        if (version_compare($schemaVersion, '3.5.4', '>=')) {
            $query->addSelect([
                'titleTranslationMethod',
                'titleTranslationKeyFormat',
            ]);
        }

        return $query;
    }

    /**
     * Gets a sections's record by uid.
     *
     * @param string $uid
     * @param bool $withTrashed Whether to include trashed sections in search
     * @return SectionRecord
     */
    private function _getSectionRecord(string $uid, bool $withTrashed = false): SectionRecord
    {
        $query = $withTrashed ? SectionRecord::findWithTrashed() : SectionRecord::find();
        $query->andWhere(['uid' => $uid]);
        return $query->one() ?? new SectionRecord();
    }

    /**
     * Gets an entry type's record by uid.
     *
     * @param string $uid
     * @param bool $withTrashed Whether to include trashed entry types in search
     * @return EntryTypeRecord
     */
    private function _getEntryTypeRecord(string $uid, bool $withTrashed = false): EntryTypeRecord
    {
        $query = $withTrashed ? EntryTypeRecord::findWithTrashed() : EntryTypeRecord::find();
        $query->andWhere(['uid' => $uid]);
        return $query->one() ?? new EntryTypeRecord();
    }
}
