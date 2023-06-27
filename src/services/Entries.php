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
use craft\events\SectionEvent;
use craft\helpers\ArrayHelper;
use craft\helpers\Db;
use craft\helpers\Json;
use craft\helpers\ProjectConfig as ProjectConfigHelper;
use craft\helpers\Queue;
use craft\helpers\StringHelper;
use craft\i18n\Translation;
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
use DateTime;
use Illuminate\Support\Collection;
use Throwable;
use yii\base\Component;
use yii\base\Exception;

/**
 * The Entries service provides APIs for managing entries in Craft.
 *
 * An instance of the service is available via [[\craft\base\ApplicationTrait::getEntries()|`Craft::$app->entries`]].
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class Entries extends Component
{
    /**
     * @event SectionEvent The event that is triggered before a section is saved.
     * @since 5.0.0
     */
    public const EVENT_BEFORE_SAVE_SECTION = 'beforeSaveSection';

    /**
     * @event SectionEvent The event that is triggered after a section is saved.
     * @since 5.0.0
     */
    public const EVENT_AFTER_SAVE_SECTION = 'afterSaveSection';

    /**
     * @event SectionEvent The event that is triggered before a section is deleted.
     * @since 5.0.0
     */
    public const EVENT_BEFORE_DELETE_SECTION = 'beforeDeleteSection';

    /**
     * @event SectionEvent The event that is triggered before a section delete is applied to the database.
     * @since 5.0.0
     */
    public const EVENT_BEFORE_APPLY_SECTION_DELETE = 'beforeApplySectionDelete';

    /**
     * @event SectionEvent The event that is triggered after a section is deleted.
     * @since 5.0.0
     */
    public const EVENT_AFTER_DELETE_SECTION = 'afterDeleteSection';

    /**
     * @event EntryTypeEvent The event that is triggered before an entry type is saved.
     * @since 5.0.0
     */
    public const EVENT_BEFORE_SAVE_ENTRY_TYPE = 'beforeSaveEntryType';

    /**
     * @event EntryTypeEvent The event that is triggered after an entry type is saved.
     * @since 5.0.0
     */
    public const EVENT_AFTER_SAVE_ENTRY_TYPE = 'afterSaveEntryType';

    /**
     * @event EntryTypeEvent The event that is triggered before an entry type is deleted.
     * @since 5.0.0
     */
    public const EVENT_BEFORE_DELETE_ENTRY_TYPE = 'beforeDeleteEntryType';

    /**
     * @event EntryTypeEvent The event that is triggered before an entry type delete is applied to the database.
     * @since 5.0.0
     */
    public const EVENT_BEFORE_APPLY_ENTRY_TYPE_DELETE = 'beforeApplyEntryTypeDelete';

    /**
     * @event EntryTypeEvent The event that is triggered after an entry type is deleted.
     * @since 5.0.0
     */
    public const EVENT_AFTER_DELETE_ENTRY_TYPE = 'afterDeleteEntryType';

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
     * @since 5.0.0
     */
    public bool $autoResaveEntries = true;

    /**
     * @var MemoizableArray<Section>|null
     * @see _sections()
     */
    private ?MemoizableArray $_sections = null;

    /**
     * @var MemoizableArray<EntryType>|null
     * @see _entryTypes()
     */
    private ?MemoizableArray $_entryTypes = null;

    /**
     * @var array<int,array<string,Entry|false>>
     */
    private array $_singleEntries = [];

    /**
     * Serializer
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
     * @since 5.0.0
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
     * @since 5.0.0
     */
    public function getEditableSectionIds(): array
    {
        return ArrayHelper::getColumn($this->getEditableSections(), 'id', false);
    }

    /**
     * Returns a memoizable array of all sections.
     *
     * @return MemoizableArray<Section>
     */
    private function _sections(): MemoizableArray
    {
        if (!isset($this->_sections)) {
            $sections = [];

            foreach ($this->_createSectionQuery()->all() as $result) {
                if (!empty($result['previewTargets'])) {
                    $result['previewTargets'] = Json::decode($result['previewTargets']);
                } else {
                    $result['previewTargets'] = [];
                }
                $sections[$result['id']] = new Section($result);
            }

            $this->_sections = new MemoizableArray(array_values($sections));

            if (!empty($sections) && Craft::$app->getRequest()->getIsCpRequest()) {
                // Eager load the site settings
                $allSiteSettings = $this->_createSectionSiteSettingsQuery()
                    ->where(['sections_sites.sectionId' => array_keys($sections)])
                    ->all();

                $siteSettingsBySection = [];
                foreach ($allSiteSettings as $siteSettings) {
                    $siteSettingsBySection[$siteSettings['sectionId']][] = new Section_SiteSettings($siteSettings);
                }

                foreach ($siteSettingsBySection as $sectionId => $sectionSiteSettings) {
                    $sections[$sectionId]->setSiteSettings($sectionSiteSettings);
                }
            }
        }

        return $this->_sections;
    }

    /**
     * Returns a Query object prepped for retrieving sections.
     *
     * @return Query
     */
    private function _createSectionQuery(): Query
    {
        return (new Query())
            ->select([
                'sections.id',
                'sections.structureId',
                'sections.name',
                'sections.handle',
                'sections.type',
                'sections.enableVersioning',
                'sections.defaultPlacement',
                'sections.propagationMethod',
                'sections.previewTargets',
                'sections.uid',
                'structures.maxLevels',
            ])
            ->leftJoin(['structures' => Table::STRUCTURES], [
                'and',
                '[[structures.id]] = [[sections.structureId]]',
                ['structures.dateDeleted' => null],
            ])
            ->from(['sections' => Table::SECTIONS])
            ->where(['sections.dateDeleted' => null])
            ->orderBy(['name' => SORT_ASC]);
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
     * @since 5.0.0
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
     * @since 5.0.0
     */
    public function getEditableSections(): array
    {
        if (Craft::$app->getRequest()->getIsConsoleRequest()) {
            return $this->getAllSections();
        }

        $user = Craft::$app->getUser()->getIdentity();

        if (!$user) {
            return [];
        }

        return ArrayHelper::where($this->getAllSections(), function(Section $section) use ($user) {
            return $user->can("viewEntries:$section->uid");
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
     * @since 5.0.0
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
     * @since 5.0.0
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
     * @since 5.0.0
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
     * @since 5.0.0
     */
    public function getSectionById(int $sectionId): ?Section
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
     * @since 5.0.0
     */
    public function getSectionByUid(string $uid): ?Section
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
     * @since 5.0.0
     */
    public function getSectionByHandle(string $sectionHandle): ?Section
    {
        return $this->_sections()->firstWhere('handle', $sectionHandle, true);
    }

    /**
     * Returns a section’s site-specific settings.
     *
     * @param int $sectionId
     * @return Section_SiteSettings[] The section’s site-specific settings.
     * @since 5.0.0
     */
    public function getSectionSiteSettings(int $sectionId): array
    {
        $siteSettings = $this->_createSectionSiteSettingsQuery()
            ->where(['sections_sites.sectionId' => $sectionId])
            ->all();

        foreach ($siteSettings as $key => $value) {
            $siteSettings[$key] = new Section_SiteSettings($value);
        }

        return $siteSettings;
    }

    /**
     * Returns a new section site settings query.
     *
     * @return Query
     */
    private function _createSectionSiteSettingsQuery(): Query
    {
        return (new Query())
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
            ->orderBy(['sites.sortOrder' => SORT_ASC]);
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
     * @throws Throwable if reasons
     * @since 5.0.0
     */
    public function saveSection(Section $section, bool $runValidation = true): bool
    {
        $isNewSection = !$section->id;

        // Fire a 'beforeSaveSection' event
        if ($this->hasEventHandlers(self::EVENT_BEFORE_SAVE_SECTION)) {
            $this->trigger(self::EVENT_BEFORE_SAVE_SECTION, new SectionEvent([
                'section' => $section,
                'isNew' => $isNewSection,
            ]));
        }

        if ($runValidation && !$section->validate()) {
            Craft::info('Section not saved due to validation error.', __METHOD__);
            return false;
        }

        if ($isNewSection) {
            $section->uid = StringHelper::UUID();
        } elseif (!$section->uid) {
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

            $configPath = ProjectConfig::PATH_SECTIONS . '.' . $section->uid;
            $configData = $section->getConfig();
            Craft::$app->getProjectConfig()->set($configPath, $configData, "Save section “{$section->handle}”");

            if ($isNewSection) {
                $section->id = Db::idByUid(Table::SECTIONS, $section->uid);
            }

            // Special handling for Single sections
            // -----------------------------------------------------------------

            if ($section->type === Section::TYPE_SINGLE) {
                // Ensure & get the single entry
                $entry = $this->_ensureSingleEntry($section, $configData['siteSettings']);

                // Deal with the section's entry types
                if (!$isNewSection) {
                    foreach ($this->getEntryTypesBySectionId($section->id) as $entryType) {
                        if ($entryType->id === $entry->getTypeId()) {
                            // This is *the* entry’s type. Make sure its name & handle match the section’s
                            if ($entryType->name !== $section->name || $entryType->handle !== $section->handle) {
                                $entryType->name = $section->name;
                                $entryType->handle = $section->handle;
                                $this->saveEntryType($entryType);
                            }

                            $section->setEntryTypes([$entryType]);
                        } else {
                            // We don’t need this one anymore
                            $this->deleteEntryType($entryType);
                        }
                    }
                }
            }

            $transaction->commit();
        } catch (Throwable $e) {
            $transaction->rollBack();
            throw $e;
        }

        return true;
    }

    /**
     * Handle section change
     *
     * @param ConfigEvent $event
     * @since 5.0.0
     */
    public function handleChangedSection(ConfigEvent $event): void
    {
        ProjectConfigHelper::ensureAllSitesProcessed();
        ProjectConfigHelper::ensureAllFieldsProcessed();
        ProjectConfigHelper::ensureAllEntryTypesProcessed();

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
            $sectionRecord->defaultPlacement = $data['defaultPlacement'] ?? Section::DEFAULT_PLACEMENT_END;
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
            } else {
                if ($sectionRecord->structureId) {
                    // Delete the old one
                    Craft::$app->getStructures()->deleteStructureById($sectionRecord->structureId);
                }

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

            // Update the entry type relations
            // -----------------------------------------------------------------

            $entryTypeIds = array_filter(array_map(
                fn(string $uid) => $this->getEntryTypeByUid($uid)?->id,
                $data['entryTypes'] ?? [],
            ));

            Db::delete(Table::SECTIONS_ENTRYTYPES, ['sectionId' => $sectionRecord->id]);
            Db::batchInsert(
                Table::SECTIONS_ENTRYTYPES,
                ['sectionId', 'typeId', 'sortOrder'],
                Collection::make($entryTypeIds)->map(fn(int $id, int $i) => [
                    $sectionRecord->id,
                    $id,
                    $i + 1,
                ])->all(),
            );

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
                    /** @var Section_SiteSettingsRecord $siteSettingsRecord */
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
                        'description' => Translation::prep('app', 'Applying new propagation method to {name} entries', [
                            'name' => $sectionRecord->name,
                        ]),
                        'elementType' => Entry::class,
                        'criteria' => [
                            'sectionId' => $sectionRecord->id,
                            'structureId' => $sectionRecord->structureId,
                        ],
                    ]));
                } elseif ($this->autoResaveEntries) {
                    Queue::push(new ResaveElements([
                        'description' => Translation::prep('app', 'Resaving {section} entries', [
                            'section' => $sectionRecord->name,
                        ]),
                        'elementType' => Entry::class,
                        'criteria' => [
                            'sectionId' => $sectionRecord->id,
                            'siteId' => array_values($siteIdMap),
                            'preferSites' => [Craft::$app->getSites()->getPrimarySite()->id],
                            'unique' => true,
                            'status' => null,
                            'drafts' => null,
                            'provisionalDrafts' => null,
                            'revisions' => null,
                        ],
                        'updateSearchIndex' => $hasNewSite,
                    ]));
                }
            }

            $transaction->commit();
        } catch (Throwable $e) {
            $transaction->rollBack();
            throw $e;
        }

        // Clear caches
        $this->_sections = null;

        /** @var Section $section */
        $section = $this->getSectionById($sectionRecord->id);

        // If this is a Single, ensure that the section has its one and only entry
        if (!$isNewSection && $section->type === Section::TYPE_SINGLE) {
            $this->_ensureSingleEntry($section, $siteSettingData);
        }

        // Fire an 'afterSaveSection' event
        if ($this->hasEventHandlers(self::EVENT_AFTER_SAVE_SECTION)) {
            $this->trigger(self::EVENT_AFTER_SAVE_SECTION, new SectionEvent([
                'section' => $section,
                'isNew' => $isNewSection,
            ]));
        }

        // Invalidate entry caches
        Craft::$app->getElements()->invalidateCachesForElementType(Entry::class);
    }

    /**
     * Adds existing entries to a newly-created structure, if the section type was just converted to Structure.
     *
     * @param SectionRecord $sectionRecord
     * @throws Exception if reasons
     * @see saveSection()
     */
    private function _populateNewStructure(SectionRecord $sectionRecord): void
    {
        // Add all of the entries to the structure
        $query = Entry::find()
            ->sectionId($sectionRecord->id)
            ->drafts(null)
            ->draftOf(false)
            ->site('*')
            ->unique()
            ->status(null)
            ->orderBy(['id' => SORT_ASC])
            ->withStructure(false);

        $structuresService = Craft::$app->getStructures();

        foreach (Db::each($query) as $entry) {
            /** @var Entry $entry */
            $structuresService->appendToRoot($sectionRecord->structureId, $entry, Structures::MODE_INSERT);
        }
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
    private function _ensureSingleEntry(Section $section, ?array $siteSettings = null): Entry
    {
        // Get the section's supported sites
        // ---------------------------------------------------------------------

        if ($siteSettings === null) {
            $siteSettings = Craft::$app->getProjectConfig()->get(ProjectConfig::PATH_SECTIONS . '.' . $section->uid . '.siteSettings');
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
        /** @var Entry|null $entry */
        $entry = Entry::find()
            ->typeId($entryTypeIds)
            ->siteId($siteIds)
            ->status(null)
            ->one();

        // Otherwise create a new one
        if ($entry === null) {
            // Create one
            $entry = new Entry();
            $entry->siteId = $siteIds[0];
            $entry->sectionId = $section->id;
            $entry->setTypeId($entryTypeIds[0]);
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
            ->drafts(null)
            ->provisionalDrafts(null)
            ->site('*')
            ->unique()
            ->id(['not', $entry->id])
            ->status(null);

        foreach (Db::each($otherEntriesQuery) as $entryToDelete) {
            /** @var Entry $entryToDelete */
            if (!$entryToDelete->getIsDraft() || $entry->canonicalId != $entry->id) {
                $elementsService->deleteElement($entryToDelete, true);
            }
        }

        return $entry;
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
     * @throws Throwable if reasons
     * @since 5.0.0
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
     * @throws Throwable if reasons
     * @since 5.0.0
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
        Craft::$app->getProjectConfig()->remove(ProjectConfig::PATH_SECTIONS . '.' . $section->uid, "Delete the “{$section->handle}” section");
        return true;
    }

    /**
     * Handle a section getting deleted
     *
     * @param ConfigEvent $event
     * @since 5.0.0
     */
    public function handleDeletedSection(ConfigEvent $event): void
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
            $elementsTable = Table::ELEMENTS;
            $entriesTable = Table::ENTRIES;
            $now = Db::prepareDateForDb(new DateTime());
            $db = Craft::$app->getDb();

            $conditionSql = <<<SQL
[[entries.sectionId]] = $section->id AND
[[elements.canonicalId]] IS NULL AND
[[elements.revisionId]] IS NULL AND
[[elements.dateDeleted]] IS NULL
SQL;


            if ($db->getIsMysql()) {
                $db->createCommand(<<<SQL
UPDATE $elementsTable [[elements]]
INNER JOIN $entriesTable [[entries]] ON [[entries.id]] = [[elements.id]]
SET [[elements.dateDeleted]] = '$now'
WHERE $conditionSql
SQL)->execute();
            } else {
                $db->createCommand(<<<SQL
UPDATE $elementsTable [[elements]]
SET [[dateDeleted]] = '$now'
FROM $entriesTable [[entries]]
WHERE [[entries.id]] = [[elements.id]] AND $conditionSql
SQL)->execute();
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
        } catch (Throwable $e) {
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
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        /** @var SectionRecord */
        return $query->one() ?? new SectionRecord();
    }

    /**
     * Prune a deleted site from section site settings.
     *
     * @param DeleteSiteEvent $event
     * @since 5.0.0
     */
    public function pruneDeletedSite(DeleteSiteEvent $event): void
    {
        $siteUid = $event->site->uid;

        $projectConfig = Craft::$app->getProjectConfig();
        $sections = $projectConfig->get(ProjectConfig::PATH_SECTIONS);

        // Loop through the sections and prune the UID from field layouts.
        if (is_array($sections)) {
            foreach ($sections as $sectionUid => $sectionGroup) {
                $projectConfig->remove(ProjectConfig::PATH_SECTIONS . '.' . $sectionUid . '.siteSettings.' . $siteUid, 'Remove section settings that belong to a site being deleted');
            }
        }
    }

    /**
     * @deprecated in 4.0.5. Unused fields will be pruned automatically as field layouts are resaved.
     */
    public function pruneDeletedField(): void
    {
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
     * @since 5.0.0
     */
    public function getEntryTypesBySectionId(int $sectionId): array
    {
        // todo: remove this after the next breakpoint
        if (!Craft::$app->getDb()->tableExists(Table::SECTIONS_ENTRYTYPES)) {
            $results = $this->_createEntryTypeQuery()
                ->where(['sectionId' => $sectionId])
                ->orderBy(['sortOrder' => SORT_DESC])
                ->all();
            return array_map(fn(array $result) => new EntryType($result), $results);
        }

        $ids = (new Query())
            ->select('typeId')
            ->from(Table::SECTIONS_ENTRYTYPES)
            ->where(['sectionId' => $sectionId])
            ->orderBy(['sortOrder' => SORT_ASC])
            ->column();

        return array_values(array_filter(
            array_map(fn(int $id) => $this->_entryTypes()->firstWhere('id', $id), $ids),
        ));
    }

    /**
     * Returns a memoizable array of all entry types.
     *
     * @return MemoizableArray<EntryType>
     */
    private function _entryTypes(): MemoizableArray
    {
        if (!isset($this->_entryTypes)) {
            $entryTypes = array_map(
                fn(array $result) => new EntryType($result),
                $this->_createEntryTypeQuery()->all(),
            );
            $this->_entryTypes = new MemoizableArray($entryTypes);

            if (!empty($entryTypes) && Craft::$app->getRequest()->getIsCpRequest()) {
                // Eager load the field layouts
                /** @var EntryType[] $entryTypesByLayoutId */
                $entryTypesByLayoutId = ArrayHelper::index($entryTypes, 'fieldLayoutId');
                $allLayouts = Craft::$app->getFields()->getLayoutsByIds(array_filter(array_keys($entryTypesByLayoutId)));

                foreach ($allLayouts as $layout) {
                    $entryTypesByLayoutId[$layout->id]->setFieldLayout($layout);
                }
            }
        }

        return $this->_entryTypes;
    }

    /**
     * @return Query
     */
    private function _createEntryTypeQuery(): Query
    {
        return (new Query())
            ->select([
                'id',
                'fieldLayoutId',
                'name',
                'handle',
                'hasTitleField',
                'titleTranslationMethod',
                'titleTranslationKeyFormat',
                'titleFormat',
                'uid',
            ])
            ->from([Table::ENTRYTYPES])
            ->where(['dateDeleted' => null]);
    }

    /**
     * Returns all entry types.
     *
     * ---
     *
     * ```php
     * $entryTypes = Craft::$app->sections->getAllEntryTypes();
     * ```
     *
     * @return EntryType[]
     * @since 5.0.0
     */
    public function getAllEntryTypes(): array
    {
        return $this->_entryTypes()->all();
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
     * @since 5.0.0
     */
    public function getEntryTypeById(int $entryTypeId): ?EntryType
    {
        return $this->_entryTypes()->firstWhere('id', $entryTypeId);
    }

    /**
     * Returns an entry type by its UID.
     *
     * @param string $uid
     * @return EntryType|null
     * @since 5.0.0
     */
    public function getEntryTypeByUid(string $uid): ?EntryType
    {
        return $this->_entryTypes()->firstWhere('uid', $uid);
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
     * @since 5.0.0
     */
    public function getEntryTypesByHandle(string $entryTypeHandle): array
    {
        return $this->_entryTypes()->where('handle', $entryTypeHandle, true)->all();
    }

    /**
     * Saves an entry type.
     *
     * @param EntryType $entryType The entry type to be saved
     * @param bool $runValidation Whether the entry type should be validated
     * @return bool Whether the entry type was saved successfully
     * @throws EntryTypeNotFoundException if $entryType->id is invalid
     * @throws Throwable if reasons
     * @since 5.0.0
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

        if ($isNewEntryType && !$entryType->uid) {
            $entryType->uid = StringHelper::UUID();
        }

        $configPath = ProjectConfig::PATH_ENTRY_TYPES . '.' . $entryType->uid;
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
     * @since 5.0.0
     */
    public function handleChangedEntryType(ConfigEvent $event): void
    {
        $entryTypeUid = $event->tokenMatches[0];
        $data = $event->newValue;

        // Make sure fields are processed
        ProjectConfigHelper::ensureAllSitesProcessed();
        ProjectConfigHelper::ensureAllFieldsProcessed();

        $entryTypeRecord = $this->_getEntryTypeRecord($entryTypeUid, true);

        $transaction = Craft::$app->getDb()->beginTransaction();

        try {
            $isNewEntryType = $entryTypeRecord->getIsNewRecord();

            $entryTypeRecord->name = $data['name'];
            $entryTypeRecord->handle = $data['handle'];
            $entryTypeRecord->hasTitleField = $data['hasTitleField'];
            $entryTypeRecord->titleTranslationMethod = $data['titleTranslationMethod'] ?? '';
            $entryTypeRecord->titleTranslationKeyFormat = $data['titleTranslationKeyFormat'] ?? null;
            $entryTypeRecord->titleFormat = $data['titleFormat'];
            $entryTypeRecord->uid = $entryTypeUid;

            if (!empty($data['fieldLayouts'])) {
                // Save the field layout
                $layout = FieldLayout::createFromConfig(reset($data['fieldLayouts']));
                $layout->id = $entryTypeRecord->fieldLayoutId;
                $layout->type = Entry::class;
                $layout->uid = key($data['fieldLayouts']);
                Craft::$app->getFields()->saveLayout($layout, false);
                $entryTypeRecord->fieldLayoutId = $layout->id;
            } elseif ($entryTypeRecord->fieldLayoutId) {
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
        } catch (Throwable $e) {
            $transaction->rollBack();
            throw $e;
        }

        // Clear caches
        $this->_entryTypes = null;

        if ($wasTrashed) {
            // Restore the entries that were deleted with the entry type
            /** @var Entry[] $entries */
            $entries = Entry::find()
                ->typeId($entryTypeRecord->id)
                ->drafts(null)
                ->draftOf(false)
                ->status(null)
                ->trashed()
                ->site('*')
                ->unique()
                ->andWhere(['entries.deletedWithEntryType' => true])
                ->all();
            Craft::$app->getElements()->restoreElements($entries);
        }

        /** @var EntryType $entryType */
        $entryType = $this->getEntryTypeById($entryTypeRecord->id);

        if (!$isNewEntryType && $resaveEntries && $this->autoResaveEntries) {
            // Re-save the entries of this type
            Queue::push(new ResaveElements([
                'description' => Translation::prep('app', 'Resaving {type} entries', [
                    'type' => $entryType->name,
                ]),
                'elementType' => Entry::class,
                'criteria' => [
                    'typeId' => $entryType->id,
                    'siteId' => '*',
                    'unique' => true,
                    'status' => null,
                ],
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
     * @throws Throwable if reasons
     * @since 5.0.0
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
     * $success = Craft::$app->sections->deleteEntryType($entryType);
     * ```
     *
     * @param EntryType $entryType
     * @return bool Whether the entry type was deleted successfully
     * @throws Throwable if reasons
     * @since 5.0.0
     */
    public function deleteEntryType(EntryType $entryType): bool
    {
        // Fire a 'beforeDeleteEntryType' event
        if ($this->hasEventHandlers(self::EVENT_BEFORE_DELETE_ENTRY_TYPE)) {
            $this->trigger(self::EVENT_BEFORE_DELETE_ENTRY_TYPE, new EntryTypeEvent([
                'entryType' => $entryType,
            ]));
        }

        Craft::$app->getProjectConfig()->remove(ProjectConfig::PATH_ENTRY_TYPES . '.' . $entryType->uid, "Delete the “{$entryType->handle}” entry type");
        return true;
    }

    /**
     * Handle an entry type getting deleted
     *
     * @param ConfigEvent $event
     * @since 5.0.0
     */
    public function handleDeletedEntryType(ConfigEvent $event): void
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
            $elementsTable = Table::ELEMENTS;
            $entriesTable = Table::ENTRIES;
            $now = Db::prepareDateForDb(new DateTime());
            $db = Craft::$app->getDb();

            $conditionSql = <<<SQL
[[entries.typeId]] = $entryType->id AND
[[entries.id]] = [[elements.id]] AND
[[elements.canonicalId]] IS NULL AND
[[elements.revisionId]] IS NULL AND
[[elements.dateDeleted]] IS NULL
SQL;

            if ($db->getIsMysql()) {
                $db->createCommand(<<<SQL
UPDATE $elementsTable [[elements]], $entriesTable [[entries]] 
SET [[elements.dateDeleted]] = '$now',
  [[entries.deletedWithEntryType]] = 1
WHERE $conditionSql
SQL)->execute();
            } else {
                // Not possible to update two tables simultaneously with Postgres
                $db->createCommand(<<<SQL
UPDATE $entriesTable [[entries]]
SET [[deletedWithEntryType]] = TRUE
FROM $elementsTable [[elements]]
WHERE $conditionSql
SQL)->execute();
                $db->createCommand(<<<SQL
UPDATE $elementsTable [[elements]]
SET [[dateDeleted]] = '$now'
FROM $entriesTable [[entries]]
WHERE $conditionSql
SQL)->execute();
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
        } catch (Throwable $e) {
            $transaction->rollBack();
            throw $e;
        }

        // Clear caches
        $this->_entryTypes = null;

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
     * Refreshes the internal entry type cache.
     *
     * @since 5.0.0
     */
    public function refreshEntryTypes(): void
    {
        $this->_entryTypes = null;
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
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        /** @var EntryTypeRecord */
        return $query->one() ?? new EntryTypeRecord();
    }

    // Entries
    // -------------------------------------------------------------------------

    /**
     * Returns an entry by its ID.
     *
     * ```php
     * $entry = Craft::$app->entries->getEntryById($entryId);
     * ```
     *
     * @param int $entryId The entry’s ID.
     * @param int|string|int[]|null $siteId The site(s) to fetch the entry in.
     * Defaults to the current site.
     * @param array $criteria
     * @return Entry|null The entry with the given ID, or `null` if an entry could not be found.
     */
    public function getEntryById(int $entryId, array|int|string $siteId = null, array $criteria = []): ?Entry
    {
        if (!$entryId) {
            return null;
        }

        // Get the structure ID
        if (!isset($criteria['structureId'])) {
            $criteria['structureId'] = (new Query())
                ->select(['sections.structureId'])
                ->from(['entries' => Table::ENTRIES])
                ->innerJoin(['sections' => Table::SECTIONS], '[[sections.id]] = [[entries.sectionId]]')
                ->where(['entries.id' => $entryId])
                ->scalar();
        }

        return Craft::$app->getElements()->getElementById($entryId, Entry::class, $siteId, $criteria);
    }

    /**
     * Returns an array of Single section entries which match a given list of section handles.
     *
     * @param string[] $handles
     * @return array<string,Entry>
     * @since 4.4.0
     */
    public function getSingleEntriesByHandle(array $handles): array
    {
        $entries = [];
        $siteId = Craft::$app->getSites()->getCurrentSite()->id;
        $missingEntries = [];

        if (!isset($this->_singleEntries[$siteId])) {
            $this->_singleEntries[$siteId] = [];
        }

        foreach ($handles as $handle) {
            if (isset($this->_singleEntries[$siteId][$handle])) {
                if ($this->_singleEntries[$siteId][$handle] !== false) {
                    $entries[$handle] = $this->_singleEntries[$siteId][$handle];
                }
            } else {
                $missingEntries[] = $handle;
            }
        }

        if (!empty($missingEntries)) {
            /** @var array<string,Section> $singleSections */
            $singleSections = ArrayHelper::index(
                Craft::$app->getEntries()->getSectionsByType(Section::TYPE_SINGLE),
                fn(Section $section) => $section->handle,
            );
            $fetchSectionIds = [];
            $fetchSectionHandles = [];
            foreach ($missingEntries as $handle) {
                if (isset($singleSections[$handle])) {
                    $fetchSectionIds[] = $singleSections[$handle]->id;
                    $fetchSectionHandles[] = $handle;
                } else {
                    $this->_singleEntries[$siteId][$handle] = false;
                }
            }
            if (!empty($fetchSectionIds)) {
                $fetchedEntries = Entry::find()
                    ->sectionId($fetchSectionIds)
                    ->siteId($siteId)
                    ->all();
                /** @var array<string,Entry> $fetchedEntries */
                $fetchedEntries = ArrayHelper::index($fetchedEntries, fn(Entry $entry) => $entry->getSection()->handle);
                foreach ($fetchSectionHandles as $handle) {
                    if (isset($fetchedEntries[$handle])) {
                        $this->_singleEntries[$siteId][$handle] = $fetchedEntries[$handle];
                        $entries[$handle] = $fetchedEntries[$handle];
                    } else {
                        $this->_singleEntries[$siteId][$handle] = false;
                    }
                }
            }
        }

        return $entries;
    }
}
