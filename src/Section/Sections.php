<?php

declare(strict_types=1);

namespace CraftCms\Cms\Section;

use craft\base\Element;
use craft\base\MemoizableArray;
use craft\elements\Entry;
use craft\errors\SectionNotFoundException;
use craft\helpers\AdminTable;
use craft\helpers\Db as DbHelper;
use craft\helpers\Queue;
use craft\models\Structure;
use craft\queue\jobs\ApplyNewPropagationMethod;
use craft\queue\jobs\ResaveElements;
use craft\services\Structures;
use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Element\Enums\PropagationMethod;
use CraftCms\Cms\EntryType\Data\EntryType;
use CraftCms\Cms\ProjectConfig\Events\ConfigEvent;
use CraftCms\Cms\ProjectConfig\ProjectConfig;
use CraftCms\Cms\ProjectConfig\ProjectConfigHelper;
use CraftCms\Cms\Section\Data\Section;
use CraftCms\Cms\Section\Data\SectionSiteSettings;
use CraftCms\Cms\Section\Enums\DefaultPlacement;
use CraftCms\Cms\Section\Enums\SectionType;
use CraftCms\Cms\Section\Events\ApplyingSectionDelete;
use CraftCms\Cms\Section\Events\DeletingSection;
use CraftCms\Cms\Section\Events\SavingSection;
use CraftCms\Cms\Section\Events\SectionDeleted;
use CraftCms\Cms\Section\Events\SectionSaved;
use CraftCms\Cms\Section\Models\Section as SectionModel;
use CraftCms\Cms\Section\Models\SectionSiteSettings as SectionSiteSettingsModel;
use CraftCms\Cms\Site\Data\Site;
use CraftCms\Cms\Site\Events\SiteDeleted;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\Facades\EntryTypes;
use CraftCms\Cms\Support\Facades\I18N;
use CraftCms\Cms\Support\Facades\Sites;
use CraftCms\Cms\Support\Json;
use CraftCms\Cms\Support\Str;
use Exception;
use Illuminate\Container\Attributes\Singleton;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Throwable;
use Tpetry\QueryExpressions\Language\Alias;
use yii\base\InvalidConfigException;

#[Singleton]
final class Sections
{
    /**
     * @var bool Whether entries should be resaved after a section has been updated.
     *
     * ::: tip
     * Entries will be resaved regardless of what this is set to, when a section’s Propagation Method setting changes.
     * :::
     *
     * ::: warning
     * Don’t disable this unless you know what you’re doing, as entries won’t reflect section/entry type changes until
     * they’ve been resaved. (You can resave entries manually by running the `resave/entries` console command.)
     * :::
     */
    public bool $autoResaveEntries = true;

    /**
     * @var MemoizableArray<Section>|null
     *
     * @see _sections()
     */
    private ?MemoizableArray $sections = null;

    public function __construct(
        private readonly ProjectConfig $projectConfig,
    ) {}

    /**
     * Returns all of the section IDs.
     *
     * ---
     *
     * ```php
     * $sectionIds = \CraftCms\Cms\Support\Facades\Sections::allSectionIds;
     * ```
     * ```twig
     * {% set sectionIds = craft.sections.getAllSectionIds %}
     * ```
     *
     * @return Collection<int> All the sections’ IDs.
     */
    public function getAllSectionIds(): Collection
    {
        return $this->getAllSections()->pluck('id');
    }

    /**
     * Returns all of the section IDs that are editable by the current user.
     *
     * ---
     *
     * ```php
     * $sectionIds = \CraftCms\Cms\Support\Facades\Sections::editableSectionIds;
     * ```
     * ```twig
     * {% set sectionIds = craft.sections.getEditableSectionIds %}
     * ```
     *
     * @return Collection<int> All the editable sections’ IDs.
     */
    public function getEditableSectionIds(): Collection
    {
        return $this->getEditableSections()->pluck('id');
    }

    /**
     * Returns a memoizable array of all sections.
     *
     * @return MemoizableArray<Section>
     */
    private function _sections(): MemoizableArray
    {
        if (isset($this->sections)) {
            return $this->sections;
        }

        $results = $this->createSectionQuery()->get();
        $siteSettingsBySection = [];

        if ($results->isNotEmpty() && request()->isCpRequest()) {
            // Eager load the site settings
            $sectionIds = $results->pluck('id')->all();
            $siteSettingsBySection = $this->_createSectionSiteSettingsQuery()
                ->whereIn('sections_sites.sectionId', $sectionIds)
                ->get()
                ->groupBy('sectionId')
                ->map(fn (Collection $collection) => $collection->all())
                ->all();
        }

        /** @var MemoizableArray<Section> $sections */
        $sections = new MemoizableArray(
            elements: $results->all(),
            normalizer: function (object $result) use (&$siteSettingsBySection) {
                if (! empty($result->previewTargets) && is_string($result->previewTargets)) {
                    $result->previewTargets = Json::decode($result->previewTargets);
                } else {
                    $result->previewTargets = [];
                }

                $result->type = SectionType::from($result->type);
                $result->propagationMethod = PropagationMethod::from($result->propagationMethod);
                $result->defaultPlacement = DefaultPlacement::from($result->defaultPlacement);

                $section = Section::from($result);

                if ($siteSettings = Arr::pull($siteSettingsBySection, $section->id)) {
                    $section->setSiteSettings(
                        array_map(fn (object $config) => SectionSiteSettings::from($config), $siteSettings),
                    );
                }

                return $section;
            });

        return $this->sections = $sections;
    }

    private function createSectionQuery(): Builder
    {
        return DB::table(Table::SECTIONS, 'sections')
            ->select([
                'sections.id',
                'sections.structureId',
                'sections.name',
                'sections.handle',
                'sections.type',
                'sections.enableVersioning',
                'sections.maxAuthors',
                'sections.defaultPlacement',
                'sections.propagationMethod',
                'sections.previewTargets',
                'sections.uid',
                'structures.maxLevels',
            ])
            ->leftJoin(new Alias(Table::STRUCTURES, 'structures'), function (JoinClause $join) {
                $join->whereColumn('structures.id', 'sections.structureId')
                    ->whereNull('structures.dateDeleted');
            })
            ->whereNull('sections.dateDeleted')
            ->orderBy('sections.name');
    }

    /**
     * Returns all sections.
     *
     * ---
     *
     * ```php
     * $sections = \CraftCms\Cms\Support\Facades\Sections::allSections;
     * ```
     * ```twig
     * {% set sections = craft.sections.getAllSections %}
     * ```
     *
     * @return Collection<Section> All the sections.
     */
    public function getAllSections(): Collection
    {
        return collect($this->_sections()->all());
    }

    /**
     * Returns all editable sections.
     *
     * ---
     *
     * ```php
     * $sections = \CraftCms\Cms\Support\Facades\Sections::editableSections;
     * ```
     * ```twig
     * {% set sections = craft.sections.getEditableSections %}
     * ```
     *
     * @return Collection<Section> All the editable sections.
     */
    public function getEditableSections(): Collection
    {
        if (app()->runningInConsole()) {
            return $this->getAllSections();
        }

        $user = Auth::user();

        if (! $user) {
            return collect();
        }

        return $this->getAllSections()->filter(
            fn (Section $section) => $user->can("viewEntries:$section->uid"),
        );
    }

    /**
     * Returns all sections of a given type.
     *
     * ---
     *
     * ```php
     * use craft\models\Section;
     *
     * $singles = \CraftCms\Cms\Support\Facades\Sections::getSectionsByType(Section::TYPE_SINGLE);
     * ```
     * ```twig
     * {% set singles = craft.sections.getSectionsByType('single') %}
     * ```
     *
     * @param  SectionType  $type  The section type (`single`, `channel`, or `structure`)
     * @return Collection<Section> All the sections of the given type.
     */
    public function getSectionsByType(SectionType $type): Collection
    {
        return collect($this->_sections()->where('type', $type, true)->all());
    }

    /**
     * Gets the total number of sections.
     *
     * ---
     *
     * ```php
     * $total = \CraftCms\Cms\Support\Facades\Sections::totalSections;
     * ```
     * ```twig
     * {% set total = craft.sections.getTotalSections %}
     * ```
     */
    public function getTotalSections(): int
    {
        return $this->getAllSections()->count();
    }

    /**
     * Gets the total number of sections that are editable by the current user.
     *
     * ---
     *
     * ```php
     * $total = \CraftCms\Cms\Support\Facades\Sections::totalEditableSections;
     * ```
     * ```twig
     * {% set total = craft.sections.getTotalEditableSections %}
     * ```
     */
    public function getTotalEditableSections(): int
    {
        return $this->getEditableSections()->count();
    }

    /**
     * Returns a section by its ID.
     *
     * ---
     *
     * ```php
     * $section = \CraftCms\Cms\Support\Facades\Sections::getSectionById(1);
     * ```
     * ```twig
     * {% set section = craft.sections.getSectionById(1) %}
     * ```
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
     * $section = \CraftCms\Cms\Support\Facades\Sections::getSectionByUid('b3a9eef3-9444-4995-84e2-6dc6b60aebd2');
     * ```
     * ```twig
     * {% set section = craft.sections.getSectionByUid('b3a9eef3-9444-4995-84e2-6dc6b60aebd2') %}
     * ```
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
     * $section = \CraftCms\Cms\Support\Facades\Sections::getSectionByHandle('news');
     * ```
     * ```twig
     * {% set section = craft.sections.getSectionByHandle('news') %}
     * ```
     */
    public function getSectionByHandle(string $sectionHandle): ?Section
    {
        return $this->_sections()->firstWhere('handle', $sectionHandle, true);
    }

    /**
     * Returns a section’s site-specific settings.
     *
     *
     * @return SectionSiteSettings[] The section’s site-specific settings.
     */
    public function getSectionSiteSettings(int $sectionId): array
    {
        return $this->_createSectionSiteSettingsQuery()
            ->where('sections_sites.sectionId', $sectionId)
            ->get()
            ->map(fn (object $result) => SectionSiteSettings::from($result))
            ->all();
    }

    private function _createSectionSiteSettingsQuery(): Builder
    {
        return DB::table(Table::SECTIONS_SITES, 'sections_sites')
            ->select([
                'sections_sites.id',
                'sections_sites.sectionId',
                'sections_sites.siteId',
                'sections_sites.enabledByDefault',
                'sections_sites.hasUrls',
                'sections_sites.uriFormat',
                'sections_sites.template',
            ])
            ->join(new Alias(Table::SITES, 'sites'), function (JoinClause $join) {
                $join->whereColumn('sections_sites.siteId', 'sites.id')
                    ->whereNull('sites.dateDeleted');
            })
            ->orderBy('sites.sortOrder');
    }

    /**
     * Saves a section.
     *
     * ---
     *
     * ```php
     * use CraftCms\Cms\Section\Data\Section;
     * use CraftCms\Cms\Section\Data\SectionSiteSettings;
     * use CraftCms\Cms\Section\Enums\SectionType;
     * use CraftCms\Cms\Support\Facades\Sections;
     * use CraftCms\Cms\Support\Facades\Sites;
     *
     * $section = new Section(
     *     name: 'News',
     *     handle: 'news',
     *     type: SectionType::Channel,
     *     siteSettings: [
     *         new SectionSiteSettings(
     *             siteId: Sites::getPrimarySite()->id,
     *             enabledByDefault: true,
     *             hasUrls: true,
     *             uriFormat: 'foo/{slug}',
     *             template: 'foo/_entry',
     *         ),
     *     ],
     * );
     *
     * $success = Sections::saveSection($section);
     * ```
     *
     * @param  Section  $section  The section to be saved
     *
     * @throws SectionNotFoundException if $section->id is invalid
     * @throws Throwable if reasons
     */
    public function saveSection(Section $section): bool
    {
        $isNewSection = ! $section->id;

        if (Event::hasListeners(SavingSection::class)) {
            Event::dispatch(new SavingSection($section, $isNewSection));
        }

        if ($isNewSection) {
            $section->uid ??= Str::uuid()->toString();
        } elseif (! $section->uid) {
            $section->uid = DB::table(Table::SECTIONS)->uidById($section->id);
        }

        // Main section settings
        if ($section->type === SectionType::Single) {
            $section->propagationMethod = PropagationMethod::All;
        }

        // Assemble the section config
        // -----------------------------------------------------------------

        // Do everything that follows in a transaction so no DB changes will be
        // saved if an exception occurs that ends up preventing the project config
        // changes from getting saved
        DB::beginTransaction();

        try {
            // Save the section config
            // -----------------------------------------------------------------

            $configPath = ProjectConfig::PATH_SECTIONS.'.'.$section->uid;
            $configData = $section->getConfig();
            $this->projectConfig->set($configPath, $configData, "Save section “{$section->handle}”");

            if ($isNewSection) {
                $section->id = DB::table(Table::SECTIONS)->idByUid($section->uid);
            }

            // Special handling for Single sections
            // -----------------------------------------------------------------

            if ($section->type === SectionType::Single) {
                // Ensure single entry
                $this->ensureSingleEntry($section, $configData['siteSettings']);
            }

            DB::commit();
        } catch (Throwable $e) {
            DB::rollBack();
            throw $e;
        }

        return true;
    }

    /**
     * Handle section change
     */
    public function handleChangedSection(ConfigEvent $event): void
    {
        ProjectConfigHelper::ensureAllSitesProcessed();
        ProjectConfigHelper::ensureAllFieldsProcessed();
        ProjectConfigHelper::ensureAllEntryTypesProcessed();

        $sectionUid = $event->tokenMatches[0];
        $data = $event->newValue;

        DB::beginTransaction();

        try {
            $siteSettingData = $data['siteSettings'];

            // Basic data
            $sectionModel = $this->getSectionModel($sectionUid, true);
            $oldPropagationMethod = $sectionModel->propagationMethod;

            $sectionModel->uid = $sectionUid;
            $sectionModel->name = $data['name'];
            $sectionModel->handle = $data['handle'];
            $sectionModel->type = $data['type'];
            $sectionModel->enableVersioning = (bool) $data['enableVersioning'];
            $sectionModel->maxAuthors = $data['maxAuthors'] ?? null;
            $sectionModel->propagationMethod = $data['propagationMethod'] ?? PropagationMethod::All->value;
            $sectionModel->defaultPlacement = $data['defaultPlacement'] ?? DefaultPlacement::End;
            $sectionModel->previewTargets = isset($data['previewTargets']) && is_array($data['previewTargets'])
                ? ProjectConfigHelper::unpackAssociativeArray($data['previewTargets'])
                : null;

            $isNewSection = ! $sectionModel->exists;
            $propagationMethodChanged = $sectionModel->propagationMethod != $oldPropagationMethod;

            if ($data['type'] === SectionType::Structure->value) {
                $structuresService = \Craft::$app->getStructures();

                // Save the structure
                $structureUid = $data['structure']['uid'];
                $structure = $structuresService->getStructureByUid($structureUid, true)
                    ?? new Structure(['uid' => $structureUid]);
                $isNewStructure = empty($structure->id);
                $structure->maxLevels = $data['structure']['maxLevels'];

                // check if we need to soft-delete an old structure
                // see https://github.com/craftcms/cms/issues/16450
                if (
                    $isNewStructure &&
                    ($event->oldValue['type'] ?? null) === SectionType::Structure &&
                    ($event->oldValue['structure']['uid'] ?? null) !== $structureUid &&
                    $sectionModel->structureId
                ) {
                    $structuresService->deleteStructureById($sectionModel->structureId);
                }

                $structuresService->saveStructure($structure);
                $sectionModel->structureId = $structure->id;
            } else {
                if ($sectionModel->structureId) {
                    // Delete the old one
                    \Craft::$app->getStructures()->deleteStructureById($sectionModel->structureId);
                }

                $sectionModel->structureId = null;
                $isNewStructure = false;
            }

            $resaveEntries = (
                $sectionModel->handle !== $sectionModel->getOriginal('handle') ||
                $sectionModel->type !== $sectionModel->getOriginal('type') ||
                $propagationMethodChanged ||
                $sectionModel->structureId !== $sectionModel->getOriginal('structureId')
            );

            if ($wasTrashed = $sectionModel->dateDeleted) {
                $sectionModel->dateDeleted = null;
                $resaveEntries = true;
            }

            $sectionModel->save();

            // Update the entry type relations
            // -----------------------------------------------------------------

            DB::table(Table::SECTIONS_ENTRYTYPES)
                ->where('sectionId', $sectionModel->id)
                ->delete();

            DB::table(Table::SECTIONS_ENTRYTYPES)
                ->insert(Collection::make($data['entryTypes'] ?? [])
                    ->map(fn ($entryType) => EntryTypes::getEntryType($entryType))
                    ->filter()
                    ->map(fn (EntryType $entryType, int $i) => [
                        'sectionId' => $sectionModel->id,
                        'typeId' => $entryType->id,
                        'sortOrder' => $i + 1,
                        'name' => isset($entryType->original) && $entryType->name !== $entryType->original->name ? $entryType->name : null,
                        'handle' => isset($entryType->original) && $entryType->handle !== $entryType->original->handle ? $entryType->handle : null,
                        'description' => isset($entryType->original) && $entryType->description !== $entryType->original->description ? $entryType->description : null,
                    ])
                    ->all(),
                );

            // Update the site settings
            // -----------------------------------------------------------------

            if (! $isNewSection) {
                // Get the old section site settings
                $allOldSiteSettingsModels = SectionSiteSettingsModel::query()
                    ->where('sectionId', $sectionModel->id)
                    ->get()
                    ->keyBy('siteId');
            } else {
                $allOldSiteSettingsModels = [];
            }

            $siteIdMap = DB::table(Table::SITES)
                ->whereIn('uid', array_keys($siteSettingData))
                ->pluck('id', 'uid')
                ->all();

            $hasNewSite = false;

            foreach ($siteSettingData as $siteUid => $siteSettings) {
                $siteId = $siteIdMap[$siteUid];

                // Was this already selected?
                if (! $isNewSection && isset($allOldSiteSettingsModels[$siteId])) {
                    /** @var SectionSiteSettingsModel $siteSettingsModel */
                    $siteSettingsModel = $allOldSiteSettingsModels[$siteId];
                } else {
                    $siteSettingsModel = new SectionSiteSettingsModel;
                    $siteSettingsModel->sectionId = $sectionModel->id;
                    $siteSettingsModel->siteId = $siteId;
                    $resaveEntries = true;
                    $hasNewSite = true;
                }

                $siteSettingsModel->enabledByDefault = $siteSettings['enabledByDefault'];

                if ($siteSettingsModel->hasUrls = $siteSettings['hasUrls']) {
                    $siteSettingsModel->uriFormat = $siteSettings['uriFormat'];
                    $siteSettingsModel->template = $siteSettings['template'];
                } else {
                    $siteSettingsModel->uriFormat = $siteSettings['uriFormat'] = null;
                    $siteSettingsModel->template = $siteSettings['template'] = null;
                }

                $resaveEntries = (
                    $resaveEntries ||
                    $siteSettingsModel->hasUrls !== $siteSettingsModel->getOriginal('hasUrls') ||
                    $siteSettingsModel->uriFormat !== $siteSettingsModel->getOriginal('uriFormat')
                );

                $siteSettingsModel->save();
            }

            if (! $isNewSection) {
                // Drop any sites that are no longer being used, as well as the associated entry/element site
                // rows
                $affectedSiteUids = array_keys($siteSettingData);

                foreach ($allOldSiteSettingsModels as $siteId => $siteSettingsModel) {
                    $siteUid = array_search($siteId, $siteIdMap, false);
                    if (! in_array($siteUid, $affectedSiteUids, false)) {
                        $siteSettingsModel->delete();
                        $resaveEntries = true;
                    }
                }
            }

            // If the section was just converted to a Structure,
            // add the existing entries to the structure
            // -----------------------------------------------------------------

            if (
                $sectionModel->type === SectionType::Structure &&
                ! $isNewSection &&
                $isNewStructure
            ) {
                $this->populateNewStructure($sectionModel);
            }

            // Finally, deal with the existing entries...
            // -----------------------------------------------------------------

            if (! $isNewSection && $resaveEntries) {
                // If the propagation method just changed, we definitely need to update entries for that
                if ($propagationMethodChanged) {
                    Queue::push(new ApplyNewPropagationMethod([
                        'description' => I18N::prep('Applying new propagation method to {name} entries', [
                            'name' => $sectionModel->name,
                        ]),
                        'elementType' => Entry::class,
                        'criteria' => [
                            'sectionId' => $sectionModel->id,
                            'structureId' => $sectionModel->structureId,
                        ],
                    ]));
                } elseif ($this->autoResaveEntries) {
                    Queue::push(new ResaveElements([
                        'description' => I18N::prep('Resaving {name} entries', [
                            'name' => $sectionModel->name,
                        ]),
                        'elementType' => Entry::class,
                        'criteria' => [
                            'sectionId' => $sectionModel->id,
                            'siteId' => array_values($siteIdMap),
                            'preferSites' => [Sites::getPrimarySite()->id],
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

            DB::commit();
        } catch (Throwable $e) {
            DB::rollBack();
            throw $e;
        }

        // Clear caches
        $this->refreshSections();

        if ($wasTrashed) {
            /** @var Entry[] $entries */
            $entries = Entry::find()
                ->sectionId($sectionModel->id)
                ->drafts(null)
                ->draftOf(false)
                ->status(null)
                ->trashed()
                ->site('*')
                ->unique()
                ->andWhere(['entries.deletedWithSection' => true])
                ->all();
            /** @var Entry[][] $entriesByType */
            $entriesByType = Collection::make($entries)->groupBy('typeId')->all();
            foreach ($entriesByType as $typeEntries) {
                try {
                    array_walk($typeEntries, function (Entry $entry) {
                        $entry->deletedWithSection = false;
                    });
                    \Craft::$app->getElements()->restoreElements($typeEntries);
                } catch (InvalidConfigException) {
                    // the entry type probably wasn't restored
                }
            }
        }

        /** @var Section $section */
        $section = $this->getSectionById($sectionModel->id);

        // If this is a Single, ensure that the section has its one and only entry
        if (! $isNewSection && $section->type === SectionType::Single) {
            $this->ensureSingleEntry($section, $siteSettingData);
        }

        if (Event::hasListeners(SectionSaved::class)) {
            Event::dispatch(new SectionSaved($section, $isNewSection));
        }

        // Invalidate entry caches
        \Craft::$app->getElements()->invalidateCachesForElementType(Entry::class);
    }

    public function refreshSections(): void
    {
        $this->sections = null;
        $this->_sections();
    }

    /**
     * Adds existing entries to a newly-created structure, if the section type was just converted to Structure.
     *
     *
     * @throws Exception if reasons
     *
     * @see saveSection()
     */
    private function populateNewStructure(SectionModel $sectionModel): void
    {
        // Add all of the entries to the structure
        $query = Entry::find()
            ->sectionId($sectionModel->id)
            ->drafts(null)
            ->draftOf(false)
            ->site('*')
            ->unique()
            ->status(null)
            ->orderBy(['id' => SORT_ASC])
            ->withStructure(false);

        $structuresService = \Craft::$app->getStructures();

        foreach (DbHelper::each($query) as $entry) {
            /** @var Entry $entry */
            $structuresService->appendToRoot($sectionModel->structureId, $entry, Structures::MODE_INSERT);
        }
    }

    /**
     * Ensures that the given Single section has its one and only entry, and returns it.
     *
     *
     * @return Entry The
     *
     * @throws Exception if reasons
     *
     * @see saveSection()
     */
    private function ensureSingleEntry(Section $section, ?array $siteSettings = null): Entry
    {
        // Get the section's supported sites
        // ---------------------------------------------------------------------

        if ($siteSettings === null) {
            $siteSettings = $this->projectConfig->get(ProjectConfig::PATH_SECTIONS.'.'.$section->uid.'.siteSettings');
        }

        if (empty($siteSettings)) {
            throw new Exception('No site settings exist for section '.$section->id);
        }

        $siteIds = Sites::getAllSites()
            ->filter(fn (Site $site) => isset($siteSettings[$site->uid]))
            ->pluck('id')
            ->values()
            ->all();

        // Get the section's entry types
        // ---------------------------------------------------------------------
        $entryTypeIds = EntryTypes::getEntryTypesBySectionId($section->id)
            ->pluck('id')
            ->values()
            ->all();

        // There should always be at least one entry type by the time this is called
        if (empty($entryTypeIds)) {
            throw new Exception('No entry types exist for section '.$section->id);
        }

        // Get/save the entry with updated title, slug, and URI format
        // ---------------------------------------------------------------------

        $baseEntryQuery = Entry::find()
            ->sectionId($section->id)
            ->siteId($siteIds)
            ->status(null);

        // If there are any existing entries, find the first one with a valid typeId
        /** @var Entry|null $entry */
        $entry = $baseEntryQuery
            ->typeId($entryTypeIds)
            ->one();

        // if we didn't find any, look for any entry in this section
        // regardless of type ID, and potentially even soft-deleted
        if ($entry === null) {
            $entry = $baseEntryQuery
                ->typeId(null)
                ->trashed(null)
                ->one();

            if ($entry !== null) {
                if (isset($entry->dateDeleted)) {
                    \Craft::$app->getElements()->restoreElement($entry);
                }

                $entry->setTypeId($entryTypeIds[0]);
            }
        }

        // if we still don't have any,
        // try without the typeId with trashed where they were deleted with entry type
        if ($entry === null) {
            $entry = $baseEntryQuery
                ->typeId(null)
                ->trashed(null)
                ->where(['entries.deletedWithEntryType' => true])
                ->one();

            if ($entry !== null) {
                $entry->setTypeId($entryTypeIds[0]);
            }
        }

        // Finally, if we still don't have an entry, create a new one
        if ($entry === null) {
            // Create one
            $entry = new Entry;
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
            ! \Craft::$app->getElements()->saveElement($entry, false)
        ) {
            throw new Exception("Couldn’t save single entry for section $section->name due to validation errors: ".implode(', ',
                $entry->getFirstErrors()));
        }

        // Delete any other entries in the section
        // ---------------------------------------------------------------------

        $elementsService = \Craft::$app->getElements();
        $otherEntriesQuery = Entry::find()
            ->sectionId($section->id)
            ->drafts(null)
            ->provisionalDrafts(null)
            ->site('*')
            ->unique()
            ->id(['not', $entry->id])
            ->status(null);

        foreach (DbHelper::each($otherEntriesQuery) as $entryToDelete) {
            /** @var Entry $entryToDelete */
            if (! $entryToDelete->getIsDraft() || $entry->canonicalId != $entry->id) {
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
     * $success = \CraftCms\Cms\Support\Facades\Sections::deleteSectionById(1);
     * ```
     *
     *
     * @return bool Whether the section was deleted successfully
     *
     * @throws Throwable if reasons
     */
    public function deleteSectionById(int $sectionId): bool
    {
        $section = $this->getSectionById($sectionId);

        if (! $section) {
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
     * $success = \CraftCms\Cms\Support\Facades\Sections::deleteSection($section);
     * ```
     *
     *
     * @return bool Whether the section was deleted successfully
     *
     * @throws Throwable if reasons
     */
    public function deleteSection(Section $section): bool
    {
        if (Event::hasListeners(DeletingSection::class)) {
            Event::dispatch(new DeletingSection($section));
        }

        // Remove the section from the project config
        $this->projectConfig->remove(
            path: ProjectConfig::PATH_SECTIONS.'.'.$section->uid,
            message: "Delete the “{$section->handle}” section",
        );

        return true;
    }

    /**
     * Handle a section getting deleted
     */
    public function handleDeletedSection(ConfigEvent $event): void
    {
        $uid = $event->tokenMatches[0];
        $sectionModel = $this->getSectionModel($uid);

        if (! $sectionModel->id) {
            return;
        }

        /** @var Section $section */
        $section = $this->getSectionById($sectionModel->id);

        if (Event::hasListeners(ApplyingSectionDelete::class)) {
            Event::dispatch(new ApplyingSectionDelete($section));
        }

        DB::beginTransaction();
        try {
            // Delete the entries
            $condition = fn (Builder $query) => $query->whereNull([
                'elements.canonicalId',
                'elements.revisionId',
                'elements.dateDeleted',
            ]);

            DB::table(Table::ELEMENTS, 'elements')
                ->whereIn(
                    'elements.id',
                    DB::table(Table::ENTRIES, 'entries')
                        ->where('entries.sectionId', $section->id)
                        ->select('entries.id'),
                )
                ->where($condition)
                ->softDelete();

            DB::table(Table::ENTRIES, 'entries')
                ->whereIn(
                    'entries.id',
                    DB::table(Table::ELEMENTS, 'elements')
                        ->where($condition)
                        ->select('elements.id'),
                )
                ->where('entries.sectionId', $section->id)
                ->update(['deletedWithSection' => true]);

            // Delete the structure
            if ($sectionModel->structureId) {
                \Craft::$app->getStructures()->deleteStructureById($sectionModel->structureId);
            }

            // Delete the section
            DB::table(Table::SECTIONS)->softDelete($sectionModel->id);

            DB::commit();
        } catch (Throwable $e) {
            DB::rollBack();
            throw $e;
        }

        // Clear caches
        $this->refreshSections();

        if (Event::hasListeners(SectionDeleted::class)) {
            Event::dispatch(new SectionDeleted($section));
        }

        // Invalidate entry caches
        \Craft::$app->getElements()->invalidateCachesForElementType(Entry::class);
    }

    /**
     * Gets a sections's model by uid.
     *
     * @param  bool  $withTrashed  Whether to include trashed sections in search
     */
    private function getSectionModel(string $uid, bool $withTrashed = false): SectionModel
    {
        return SectionModel::query()
            ->when($withTrashed, fn (EloquentBuilder $query) => $query->withTrashed())
            ->where('uid', $uid)
            ->first() ?? new SectionModel;
    }

    /**
     * Prune a deleted site from section site settings.
     */
    public function pruneDeletedSite(SiteDeleted $event): void
    {
        $siteUid = $event->site->uid;
        $sections = $this->projectConfig->get(ProjectConfig::PATH_SECTIONS);

        // Loop through the sections and prune the UID from field layouts.
        if (is_array($sections)) {
            foreach ($sections as $sectionUid => $sectionGroup) {
                $this->projectConfig->remove(
                    path: ProjectConfig::PATH_SECTIONS.'.'.$sectionUid.'.siteSettings.'.$siteUid,
                    message: 'Remove section settings that belong to a site being deleted',
                );
            }
        }
    }

    /**
     * Returns data for the Sections index page in the control panel.
     */
    public function getSectionTableData(
        int $page,
        int $limit,
        ?string $searchTerm = null,
        string $orderBy = 'name',
        int $sortDir = SORT_ASC,
    ): array {
        [$results, $total] = $this->prepTableData($this->createSectionQuery(), $page, $limit, $searchTerm, $orderBy,
            $sortDir);

        /** @var Collection<Section> $sections */
        $sections = $results
            ->map(fn (object $result) => $this->_sections()->firstWhere('id', $result->id))
            ->filter()
            ->values();

        $tableData = [];

        foreach ($sections as $section) {
            $label = $section->getUiLabel();
            $tableData[] = [
                'id' => $section->id,
                'title' => $label,
                'name' => $label,
                'url' => $section->getCpEditUrl(),
                'handle' => $section->handle,
                'type' => $section->type->label(),
            ];
        }

        $pagination = AdminTable::paginationLinks($page, $total, $limit);

        return [$pagination, $tableData];
    }

    /**
     * Returns query results needed for the VueAdminTable accounting for the pagination, search terms and sorting options.
     *
     *
     * @return array{0: Collection, 1: int}
     */
    private function prepTableData(
        Builder $query,
        int $page,
        int $limit,
        ?string $searchTerm,
        string $orderBy = 'name',
        int $sortDir = SORT_ASC,
    ): array {
        $sortDir = $sortDir === SORT_DESC ? 'desc' : 'asc';
        $searchTerm = $searchTerm ? trim($searchTerm) : $searchTerm;

        $offset = ($page - 1) * $limit;
        $query = $query->orderBy($orderBy, $sortDir);

        if ($searchTerm !== null && $searchTerm !== '') {
            $searchParams = $this->_getSearchParams($searchTerm);
            if (! empty($searchParams)) {
                $query->where(function (Builder $query) use ($searchParams) {
                    foreach ($searchParams as $param) {
                        $query->orWhere($param[0], $param[1], $param[2]);
                    }
                });
            }
        }

        $total = $query->count();

        $query->limit($limit);
        $query->offset($offset);

        return [$query->get(), $total];
    }

    /**
     * Returns the sql expression to be used in the 'where' param for the query.
     */
    private function _getSearchParams(string $term): array
    {
        $searchParams = ['name', 'handle'];
        $searchQueries = [];

        if ($term !== '') {
            foreach ($searchParams as $param) {
                $searchQueries[] = [$param, 'like', '%'.$term.'%'];
            }
        }

        return $searchQueries;
    }
}
