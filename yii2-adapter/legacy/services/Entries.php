<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\services;

use Craft;
use craft\elements\Entry;
use craft\errors\EntryTypeNotFoundException;
use craft\errors\InvalidElementException;
use craft\errors\SectionNotFoundException;
use craft\errors\UnsupportedSiteException;
use craft\events\DeleteSiteEvent;
use craft\events\EntryTypeEvent;
use craft\events\MoveEntryEvent;
use craft\events\SectionEvent;
use craft\models\EntryType;
use craft\models\Section;
use craft\models\Section_SiteSettings;
use CraftCms\Cms\Entry\Events\ApplyingDeleteEntryType;
use CraftCms\Cms\Entry\Events\DeletingEntryType;
use CraftCms\Cms\Entry\Events\EntryMovedToSection;
use CraftCms\Cms\Entry\Events\EntryTypeDeleted;
use CraftCms\Cms\Entry\Events\EntryTypeSaved;
use CraftCms\Cms\Entry\Events\MovingEntryToSection;
use CraftCms\Cms\Entry\Events\SavingEntryType;
use CraftCms\Cms\Field\Enums\TranslationMethod;
use CraftCms\Cms\ProjectConfig\Events\ConfigEvent;
use CraftCms\Cms\Section\Data\SectionSiteSettings;
use CraftCms\Cms\Section\Enums\SectionType;
use CraftCms\Cms\Section\Events\ApplyingSectionDelete;
use CraftCms\Cms\Section\Events\DeletingSection;
use CraftCms\Cms\Section\Events\SavingSection;
use CraftCms\Cms\Section\Events\SectionDeleted;
use CraftCms\Cms\Section\Events\SectionSaved;
use CraftCms\Cms\Shared\Enums\Color;
use CraftCms\Cms\Site\Data\Site;
use CraftCms\Cms\Site\Events\SiteDeleted;
use CraftCms\Cms\Support\Facades\Entries as EntriesFacade;
use CraftCms\Cms\Support\Facades\EntryTypes;
use CraftCms\Cms\Support\Facades\Sections;
use CraftCms\Cms\Support\Utils;
use Illuminate\Support\Facades\Event;
use Throwable;
use yii\base\Component;
use yii\base\Exception;

/**
 * The Entries service provides APIs for managing entries in Craft.
 *
 * An instance of the service is available via [[\craft\base\ApplicationTrait::getEntries()|`Craft::$app->getEntries()`]].
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 * @deprecated 6.0.0. Use {@see \CraftCms\Cms\Entry\EntryTypes}, {@see \CraftCms\Cms\Section\Sections} or {@see \CraftCms\Cms\Entry\Entries} instead.
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
     * @event MoveEntryEvent The event that is triggered before an entry is move to a different section.
     * @since 5.3.0
     */
    public const EVENT_BEFORE_MOVE_TO_SECTION = 'beforeMoveToSection';

    /**
     * @event MoveEntryEvent The event that is triggered before an entry is move to a different section.
     * @since 5.3.0
     */
    public const EVENT_AFTER_MOVE_TO_SECTION = 'afterMoveToSection';

    // Sections
    // -------------------------------------------------------------------------

    /**
     * Returns all of the section IDs.
     *
     * ---
     *
     * ```php
     * $sectionIds = Craft::$app->entries->allSectionIds;
     * ```
     * ```twig
     * {% set sectionIds = craft.app.entries.allSectionIds %}
     * ```
     *
     * @return int[] All the sections’ IDs.
     * @since 5.0.0
     */
    public function getAllSectionIds(): array
    {
        return Sections::getAllSectionIds()->values()->all();
    }

    /**
     * Returns all of the section IDs that are editable by the current user.
     *
     * ---
     *
     * ```php
     * $sectionIds = Craft::$app->entries->editableSectionIds;
     * ```
     * ```twig
     * {% set sectionIds = craft.app.entries.editableSectionIds %}
     * ```
     *
     * @return int[] All the editable sections’ IDs.
     * @since 5.0.0
     */
    public function getEditableSectionIds(): array
    {
        return Sections::getEditableSectionIds()->values()->all();
    }

    /**
     * Returns all sections.
     *
     * ---
     *
     * ```php
     * $sections = Craft::$app->entries->allSections;
     * ```
     * ```twig
     * {% set sections = craft.app.entries.allSections %}
     * ```
     *
     * @return Section[] All the sections.
     * @since 5.0.0
     */
    public function getAllSections(): array
    {
        return Sections::getAllSections()->values()
            ->map(fn($sectionData) => self::sectionFromSectionData($sectionData))
            ->all();
    }

    /**
     * Returns all editable sections.
     *
     * ---
     *
     * ```php
     * $sections = Craft::$app->entries->editableSections;
     * ```
     * ```twig
     * {% set sections = craft.app.entries.editableSections %}
     * ```
     *
     * @return Section[] All the editable sections.
     * @since 5.0.0
     */
    public function getEditableSections(): array
    {
        return Sections::geteditableSections()->values()
            ->map(fn($sectionData) => self::sectionFromSectionData($sectionData))
            ->all();
    }

    /**
     * Returns all sections of a given type.
     *
     * ---
     *
     * ```php
     * use craft\models\Section;
     *
     * $singles = Craft::$app->entries->getSectionsByType(Section::TYPE_SINGLE);
     * ```
     * ```twig
     * {% set singles = craft.app.entries.getSectionsByType('single') %}
     * ```
     *
     * @param string $type The section type (`single`, `channel`, or `structure`)
     *
     * @return Section[] All the sections of the given type.
     * @since 5.0.0
     */
    public function getSectionsByType(string $type): array
    {
        return Sections::getSectionsByType(SectionType::from($type))
            ->values()
            ->map(fn($sectionData) => self::sectionFromSectionData($sectionData))
            ->all();
    }

    /**
     * Gets the total number of sections.
     *
     * ---
     *
     * ```php
     * $total = Craft::$app->entries->totalSections;
     * ```
     * ```twig
     * {% set total = craft.app.entries.totalSections %}
     * ```
     *
     * @return int
     * @since 5.0.0
     */
    public function getTotalSections(): int
    {
        return Sections::getTotalSections();
    }

    /**
     * Gets the total number of sections that are editable by the current user.
     *
     * ---
     *
     * ```php
     * $total = Craft::$app->entries->totalEditableSections;
     * ```
     * ```twig
     * {% set total = craft.app.entries.totalEditableSections %}
     * ```
     *
     * @return int
     * @since 5.0.0
     */
    public function getTotalEditableSections(): int
    {
        return Sections::getTotalEditableSections();
    }

    /**
     * Returns a section by its ID.
     *
     * ---
     *
     * ```php
     * $section = Craft::$app->entries->getSectionById(1);
     * ```
     * ```twig
     * {% set section = craft.app.entries.getSectionById(1) %}
     * ```
     *
     * @param int $sectionId
     *
     * @return Section|null
     * @since 5.0.0
     */
    public function getSectionById(int $sectionId): ?Section
    {
        $sectionData = Sections::getSectionById($sectionId);

        if (!$sectionData) {
            return null;
        }

        return self::sectionFromSectionData($sectionData);
    }

    /**
     * Gets a section by its UID.
     *
     * ---
     *
     * ```php
     * $section = Craft::$app->entries->getSectionByUid('b3a9eef3-9444-4995-84e2-6dc6b60aebd2');
     * ```
     * ```twig
     * {% set section = craft.app.entries.getSectionByUid('b3a9eef3-9444-4995-84e2-6dc6b60aebd2') %}
     * ```
     *
     * @param string $uid
     *
     * @return Section|null
     * @since 5.0.0
     */
    public function getSectionByUid(string $uid): ?Section
    {
        $sectionData = Sections::getSectionByUid($uid);

        if (!$sectionData) {
            return null;
        }

        return self::sectionFromSectionData($sectionData);
    }

    /**
     * Gets a section by its handle.
     *
     * ---
     *
     * ```php
     * $section = Craft::$app->entries->getSectionByHandle('news');
     * ```
     * ```twig
     * {% set section = craft.app.entries.getSectionByHandle('news') %}
     * ```
     *
     * @param string $sectionHandle
     *
     * @return Section|null
     * @since 5.0.0
     */
    public function getSectionByHandle(string $sectionHandle): ?Section
    {
        $sectionData = Sections::getSectionByHandle($sectionHandle);

        if (!$sectionData) {
            return null;
        }

        return self::sectionFromSectionData($sectionData);
    }

    /**
     * Returns a section’s site-specific settings.
     *
     * @param int $sectionId
     *
     * @return Section_SiteSettings[] The section’s site-specific settings.
     * @since 5.0.0
     */
    public function getSectionSiteSettings(int $sectionId): array
    {
        return array_map(function(SectionSiteSettings $data) {
            return self::sectionSiteSettingsFromSiteSettingsData($data);
        }, Sections::getSectionSiteSettings($sectionId));
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
     * $success = Craft::$app->entries->saveSection($section);
     * ```
     *
     * @param Section $section The section to be saved
     * @param bool $runValidation Whether the section should be validated
     *
     * @return bool
     * @throws SectionNotFoundException if $section->id is invalid
     * @throws Throwable if reasons
     * @since 5.0.0
     */
    public function saveSection(Section $section, bool $runValidation = true): bool
    {
        if ($runValidation) {
            $section->validate();
        }

        $section = $this->sectionDataFromSection($section);

        return Sections::saveSection($section);
    }

    /**
     * Handle section change
     *
     * @param ConfigEvent $event
     *
     * @since 5.0.0
     */
    public function handleChangedSection(ConfigEvent $event): void
    {
        Sections::handleChangedSection($event);
    }

    /**
     * Deletes a section by its ID.
     *
     * ---
     *
     * ```php
     * $success = Craft::$app->entries->deleteSectionById(1);
     * ```
     *
     * @param int $sectionId
     *
     * @return bool Whether the section was deleted successfully
     * @throws Throwable if reasons
     * @since 5.0.0
     */
    public function deleteSectionById(int $sectionId): bool
    {
        return Sections::deleteSectionById($sectionId);
    }

    /**
     * Deletes a section.
     *
     * ---
     *
     * ```php
     * $success = Craft::$app->entries->deleteSection($section);
     * ```
     *
     * @param Section $section
     *
     * @return bool Whether the section was deleted successfully
     * @throws Throwable if reasons
     * @since 5.0.0
     */
    public function deleteSection(Section $section): bool
    {
        $section = $this->sectionDataFromSection($section);

        return Sections::deleteSection($section);
    }

    /**
     * Handle a section getting deleted
     *
     * @param ConfigEvent $event
     *
     * @since 5.0.0
     */
    public function handleDeletedSection(ConfigEvent $event): void
    {
        Sections::handleDeletedSection($event);
    }

    /**
     * Prune a deleted site from section site settings.
     *
     * @param DeleteSiteEvent $event
     *
     * @since 5.0.0
     */
    public function pruneDeletedSite(DeleteSiteEvent $event): void
    {
        $event = new SiteDeleted(Site::from($event->site->toArray()));

        Sections::pruneDeletedSite($event);
    }

    /**
     * @deprecated in 4.0.5. Unused fields will be pruned automatically as field layouts are resaved.
     */
    public function pruneDeletedField(): void
    {
    }

    /**
     * Returns data for the Sections index page in the control panel.
     *
     * @param int $page
     * @param int $limit
     * @param string|null $searchTerm
     * @param string $orderBy
     * @param int $sortDir
     *
     * @return array
     * @since 5.5.0
     */
    public function getSectionTableData(
        int $page,
        int $limit,
        ?string $searchTerm,
        string $orderBy = 'name',
        int $sortDir = SORT_ASC,
    ): array {
        return Sections::getSectionTableData($page, $limit, $searchTerm, $orderBy, $sortDir);
    }

    // Entry Types
    // -------------------------------------------------------------------------

    /**
     * Returns a section’s entry types.
     *
     * ---
     *
     * ```php
     * $entryTypes = Craft::$app->entries->getEntryTypesBySectionId(1);
     * ```
     *
     * @param int $sectionId
     *
     * @return EntryType[]
     * @since 5.0.0
     */
    public function getEntryTypesBySectionId(int $sectionId): array
    {
        return EntryTypes::getEntryTypesBySectionId($sectionId)
            ->map(fn(\CraftCms\Cms\Entry\Data\EntryType $entryType) => self::entryTypeFromEntryTypeData($entryType))
            ->all();
    }

    /**
     * Returns all entry types.
     *
     * ---
     *
     * ```php
     * $entryTypes = Craft::$app->entries->getAllEntryTypes();
     * ```
     *
     * @return EntryType[]
     * @since 5.0.0
     */
    public function getAllEntryTypes(): array
    {
        return EntryTypes::getAllEntryTypes()
            ->map(fn(\CraftCms\Cms\Entry\Data\EntryType $entryType) => self::entryTypeFromEntryTypeData($entryType))
            ->all();
    }

    /**
     * Returns an entry type by its ID.
     *
     * ---
     *
     * ```php
     * $entryType = Craft::$app->entries->getEntryTypeById(1);
     * ```
     *
     * @param int $entryTypeId
     * @param bool $withTrashed
     *
     * @return EntryType|null
     * @since 5.0.0
     */
    public function getEntryTypeById(int $entryTypeId, bool $withTrashed = false): ?EntryType
    {
        $entryType = EntryTypes::getEntryTypeById($entryTypeId, $withTrashed);

        if (!$entryType) {
            return null;
        }

        return self::entryTypeFromEntryTypeData($entryType);
    }

    /**
     * Returns an entry type by its UID.
     *
     * @param string $uid
     *
     * @return EntryType|null
     * @since 5.0.0
     */
    public function getEntryTypeByUid(string $uid): ?EntryType
    {
        $entryType = EntryTypes::getEntryTypeByUid($uid);

        if (!$entryType) {
            return null;
        }

        return self::entryTypeFromEntryTypeData($entryType);
    }

    /**
     * Returns an entry type by its handle.
     *
     * ---
     *
     * ```php
     * $entryType = Craft::$app->entries->getEntryTypeByHandle('article');
     * ```
     *
     * @param string $entryTypeHandle
     *
     * @return EntryType|null
     * @since 5.0.0
     */
    public function getEntryTypeByHandle(string $entryTypeHandle): ?EntryType
    {
        $entryType = EntryTypes::getEntryTypeByHandle($entryTypeHandle);

        if (!$entryType) {
            return null;
        }

        return self::entryTypeFromEntryTypeData($entryType);
    }

    /**
     * Returns an entry type by its usage config.
     *
     * @param EntryType|int|string|array{id?:int,uid?:string,name?:string,handle?:string} $entryType
     *
     * @return EntryType|null
     * @since 5.6.0
     */
    public function getEntryType(mixed $entryType): ?EntryType
    {
        $entryType = EntryTypes::getEntryType($entryType);

        if (!$entryType) {
            return null;
        }

        return self::entryTypeFromEntryTypeData($entryType);
    }

    /**
     * Saves an entry type.
     *
     * @param EntryType $entryType The entry type to be saved
     * @param bool $runValidation Whether the entry type should be validated
     *
     * @return bool Whether the entry type was saved successfully
     * @throws EntryTypeNotFoundException if $entryType->id is invalid
     * @throws Throwable if reasons
     * @since 5.0.0
     */
    public function saveEntryType(EntryType $entryType, bool $runValidation = true): bool
    {
        if ($runValidation && !$entryType->validate()) {
            return false;
        }

        $data = $entryType->toArray();
        $data['titleTranslationMethod'] = TranslationMethod::from($data['titleTranslationMethod']);
        $data['slugTranslationMethod'] = TranslationMethod::from($data['slugTranslationMethod']);
        $data['color'] = Color::tryFrom($data['color']['value'] ?? null);
        $entryTypeData = new \CraftCms\Cms\Entry\Data\EntryType(...$data);
        $entryTypeData->setFieldLayout($entryType->getFieldLayout());

        return EntryTypes::saveEntryType($entryTypeData);
    }

    /**
     * Handle entry type change
     *
     * @param ConfigEvent $event
     *
     * @since 5.0.0
     */
    public function handleChangedEntryType(ConfigEvent $event): void
    {
        EntryTypes::handleChangedEntryType($event);
    }

    /**
     * Deletes an entry type by its ID.
     *
     * ---
     *
     * ```php
     * $success = Craft::$app->entries->deleteEntryTypeById(1);
     * ```
     *
     * @param int $entryTypeId
     *
     * @return bool Whether the entry type was deleted successfully
     * @throws Throwable if reasons
     * @since 5.0.0
     */
    public function deleteEntryTypeById(int $entryTypeId): bool
    {
        return EntryTypes::deleteEntryTypeById($entryTypeId);
    }

    /**
     * Deletes an entry type.
     *
     * ---
     *
     * ```php
     * $success = Craft::$app->entries->deleteEntryType($entryType);
     * ```
     *
     * @param EntryType $entryType
     *
     * @return bool Whether the entry type was deleted successfully
     * @throws Throwable if reasons
     * @since 5.0.0
     */
    public function deleteEntryType(EntryType $entryType): bool
    {
        $data = $entryType->toArray();
        $data['titleTranslationMethod'] = TranslationMethod::from($data['titleTranslationMethod']);
        $data['slugTranslationMethod'] = TranslationMethod::from($data['slugTranslationMethod']);
        $data['color'] = Color::tryFrom($data['color']['value'] ?? null);
        $entryTypeData = new \CraftCms\Cms\Entry\Data\EntryType(...$data);

        return EntryTypes::deleteEntryType($entryTypeData);
    }

    /**
     * Handle an entry type getting deleted
     *
     * @param ConfigEvent $event
     *
     * @since 5.0.0
     */
    public function handleDeletedEntryType(ConfigEvent $event): void
    {
        EntryTypes::handleDeletedEntryType($event);
    }

    /**
     * Refreshes the internal entry type cache.
     *
     * @since 5.0.0
     */
    public function refreshEntryTypes(): void
    {
        EntryTypes::refreshEntryTypes();
    }

    /**
     * Returns data for the Entry Types index page in the control panel.
     *
     * @param int $page
     * @param int $limit
     * @param string|null $searchTerm
     * @param string $orderBy
     * @param int $sortDir
     *
     * @return array
     * @since 5.0.0
     * @internal
     */
    public function getTableData(
        int $page,
        int $limit,
        ?string $searchTerm,
        string $orderBy = 'name',
        int $sortDir = SORT_ASC,
    ): array {
        return EntryTypes::getTableData($page, $limit, $searchTerm, $orderBy, $sortDir);
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
     *
     * @return Entry|null The entry with the given ID, or `null` if an entry could not be found.
     */
    public function getEntryById(int $entryId, array|int|string|null $siteId = null, array $criteria = []): ?Entry
    {
        return EntriesFacade::getEntryById($entryId, $siteId, $criteria);
    }

    /**
     * Returns an array of Single section entries which match a given list of section handles.
     *
     * @param string[] $handles
     *
     * @return array<string,Entry>
     * @since 4.4.0
     */
    public function getSingleEntriesByHandle(array $handles): array
    {
        return EntriesFacade::getSingleEntriesByHandle($handles);
    }

    /**
     * Move entry to a different section.
     *
     * @param Entry $entry
     * @param Section $section
     *
     * @return bool
     * @throws Exception
     * @throws InvalidElementException
     * @throws Throwable
     * @throws UnsupportedSiteException
     * @since 5.3.0
     */
    public function moveEntryToSection(Entry $entry, Section|\CraftCms\Cms\Section\Data\Section $section): bool
    {
        if ($section instanceof Section) {
            $section = self::sectionDataFromSection($section);
        }

        $entry = self::newEntryFromEntry($entry);

        return EntriesFacade::moveEntryToSection($entry, $section);
    }

    private static function newEntryFromEntry(Entry $entry): \CraftCms\Cms\Element\Elements\Entry
    {
        return new \CraftCms\Cms\Element\Elements\Entry($entry->toArray());
    }

    private static function sectionFromSectionData(\CraftCms\Cms\Section\Data\Section $section): Section
    {
        $yiiSection = new Section(Utils::getPublicProperties($section));
        $yiiSection->setSiteSettings(array_map(function(SectionSiteSettings $sectionSiteSettings) {
            return self::sectionSiteSettingsFromSiteSettingsData($sectionSiteSettings);
        }, $section->getSiteSettings()));
        $yiiSection->setEntryTypes(array_map(function(\CraftCms\Cms\Entry\Data\EntryType $entryTypeData) {
            return self::entryTypeFromEntryTypeData($entryTypeData);
        }, $section->getEntryTypes()));

        return $yiiSection;
    }

    private static function sectionDataFromSection(Section $section): \CraftCms\Cms\Section\Data\Section
    {
        $data = $section->toArray();
        if (is_array($data['propagationMethod'])) {
            $data['propagationMethod'] = $data['propagationMethod']['value'];
        }

        return \CraftCms\Cms\Section\Data\Section::from($data);
    }

    private static function sectionSiteSettingsFromSiteSettingsData(SectionSiteSettings $siteSettings): Section_SiteSettings
    {
        return new Section_SiteSettings(Utils::getPublicProperties($siteSettings));
    }

    private static function entryTypeFromEntryTypeData(\CraftCms\Cms\Entry\Data\EntryType $entryTypeData): EntryType
    {
        $data = Utils::getPublicProperties($entryTypeData);
        $data['titleTranslationMethod'] = $data['titleTranslationMethod']->value;
        $data['slugTranslationMethod'] = $data['slugTranslationMethod']->value;
        $data['original'] = isset($data['original'])
            ? self::entryTypeFromEntryTypeData($data['original'])
            : null;

        $entryType = new EntryType($data);
        $entryType->setFieldLayout($entryTypeData->getFieldLayout());

        return $entryType;
    }

    public static function registerEvents(): void
    {
        Event::listen(SavingSection::class, function(SavingSection $event) {
            if (Craft::$app->getEntries()->hasEventHandlers(self::EVENT_BEFORE_SAVE_SECTION)) {
                Craft::$app->getEntries()->trigger(self::EVENT_BEFORE_SAVE_SECTION, new SectionEvent([
                    'section' => self::sectionFromSectionData($event->section),
                    'isNew' => $event->isNew,
                ]));
            }
        });

        Event::listen(SectionSaved::class, function(SectionSaved $event) {
            if (Craft::$app->getEntries()->hasEventHandlers(self::EVENT_AFTER_SAVE_SECTION)) {
                Craft::$app->getEntries()->trigger(self::EVENT_AFTER_SAVE_SECTION, new SectionEvent([
                    'section' => self::sectionFromSectionData($event->section),
                    'isNew' => $event->isNew,
                ]));
            }
        });

        Event::listen(DeletingSection::class, function(DeletingSection $event) {
            if (Craft::$app->getEntries()->hasEventHandlers(self::EVENT_BEFORE_DELETE_SECTION)) {
                Craft::$app->getEntries()->trigger(self::EVENT_BEFORE_DELETE_SECTION, new SectionEvent([
                    'section' => self::sectionFromSectionData($event->section),
                ]));
            }
        });

        Event::listen(ApplyingSectionDelete::class, function(ApplyingSectionDelete $event) {
            if (Craft::$app->getEntries()->hasEventHandlers(self::EVENT_BEFORE_APPLY_SECTION_DELETE)) {
                Craft::$app->getEntries()->trigger(self::EVENT_BEFORE_APPLY_SECTION_DELETE, new SectionEvent([
                    'section' => self::sectionFromSectionData($event->section),
                ]));
            }
        });

        Event::listen(SectionDeleted::class, function(SectionDeleted $event) {
            if (Craft::$app->getEntries()->hasEventHandlers(self::EVENT_AFTER_DELETE_SECTION)) {
                Craft::$app->getEntries()->trigger(self::EVENT_AFTER_DELETE_SECTION, new SectionEvent([
                    'section' => self::sectionFromSectionData($event->section),
                ]));
            }
        });

        Event::listen(SavingEntryType::class, function(SavingEntryType $event) {
            if (Craft::$app->getEntries()->hasEventHandlers(self::EVENT_BEFORE_SAVE_ENTRY_TYPE)) {
                Craft::$app->getEntries()->trigger(self::EVENT_BEFORE_SAVE_ENTRY_TYPE, new EntryTypeEvent([
                    'entryType' => self::entryTypeFromEntryTypeData($event->entryType),
                    'isNew' => $event->isNew,
                ]));
            }
        });

        Event::listen(EntryTypeSaved::class, function(EntryTypeSaved $event) {
            if (Craft::$app->getEntries()->hasEventHandlers(self::EVENT_AFTER_SAVE_ENTRY_TYPE)) {
                Craft::$app->getEntries()->trigger(self::EVENT_AFTER_SAVE_ENTRY_TYPE, new EntryTypeEvent([
                    'entryType' => self::entryTypeFromEntryTypeData($event->entryType),
                    'isNew' => $event->isNew,
                ]));
            }
        });

        Event::listen(DeletingEntryType::class, function(DeletingEntryType $event) {
            if (Craft::$app->getEntries()->hasEventHandlers(self::EVENT_BEFORE_DELETE_ENTRY_TYPE)) {
                Craft::$app->getEntries()->trigger(self::EVENT_BEFORE_DELETE_ENTRY_TYPE, new EntryTypeEvent([
                    'entryType' => self::entryTypeFromEntryTypeData($event->entryType),
                ]));
            }
        });

        Event::listen(ApplyingDeleteEntryType::class, function(ApplyingDeleteEntryType $event) {
            if (Craft::$app->getEntries()->hasEventHandlers(self::EVENT_BEFORE_APPLY_ENTRY_TYPE_DELETE)) {
                Craft::$app->getEntries()->trigger(self::EVENT_BEFORE_APPLY_ENTRY_TYPE_DELETE, new EntryTypeEvent([
                    'entryType' => self::entryTypeFromEntryTypeData($event->entryType),
                ]));
            }
        });

        Event::listen(EntryTypeDeleted::class, function(EntryTypeDeleted $event) {
            if (Craft::$app->getEntries()->hasEventHandlers(self::EVENT_AFTER_DELETE_ENTRY_TYPE)) {
                Craft::$app->getEntries()->trigger(self::EVENT_AFTER_DELETE_ENTRY_TYPE, new EntryTypeEvent([
                    'entryType' => self::entryTypeFromEntryTypeData($event->entryType),
                ]));
            }
        });

        Event::listen(MovingEntryToSection::class, function(MovingEntryToSection $event) {
            if (Craft::$app->getEntries()->hasEventHandlers(self::EVENT_BEFORE_MOVE_TO_SECTION)) {
                Craft::$app->getEntries()->trigger(self::EVENT_BEFORE_MOVE_TO_SECTION, new MoveEntryEvent([
                    'entry' => $event->entry,
                    'section' => self::sectionFromSectionData($event->section),
                ]));
            }
        });

        Event::listen(EntryMovedToSection::class, function(EntryMovedToSection $event) {
            if (Craft::$app->getEntries()->hasEventHandlers(self::EVENT_AFTER_MOVE_TO_SECTION)) {
                Craft::$app->getEntries()->trigger(self::EVENT_AFTER_MOVE_TO_SECTION, new MoveEntryEvent([
                    'entry' => $event->entry,
                    'section' => self::sectionFromSectionData($event->section),
                ]));
            }
        });
    }
}
