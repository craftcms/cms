<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\services;

use Craft;
use craft\base\Element;
use craft\db\Query;
use craft\elements\Entry;
use craft\errors\EntryTypeNotFoundException;
use craft\errors\SectionNotFoundException;
use craft\events\EntryTypeEvent;
use craft\events\SectionEvent;
use craft\helpers\ArrayHelper;
use craft\models\EntryType;
use craft\models\Section;
use craft\models\Section_SiteSettings;
use craft\models\Structure;
use craft\queue\jobs\ResaveElements;
use craft\records\EntryType as EntryTypeRecord;
use craft\records\Section as SectionRecord;
use craft\records\Section_SiteSettings as Section_SiteSettingsRecord;
use yii\base\Component;
use yii\base\Exception;

/**
 * Sections service.
 * An instance of the Sections service is globally accessible in Craft via [[\craft\base\ApplicationTrait::getSections()|`Craft::$app->sections`]].
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class Sections extends Component
{
    // Constants
    // =========================================================================

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
     * @event EntryTypeEvent The event that is triggered after an entry type is deleted.
     */
    const EVENT_AFTER_DELETE_ENTRY_TYPE = 'afterDeleteEntryType';

    // Properties
    // =========================================================================

    /**
     * @var
     */
    private $_allSectionIds;

    /**
     * @var
     */
    private $_editableSectionIds;

    /**
     * @var
     */
    private $_sectionsById;

    /**
     * @var bool
     */
    private $_fetchedAllSections = false;

    /**
     * @var
     */
    private $_entryTypesById;

    // Public Methods
    // =========================================================================

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
        if ($this->_allSectionIds !== null) {
            return $this->_allSectionIds;
        }

        $this->_allSectionIds = [];

        foreach ($this->getAllSections() as $section) {
            $this->_allSectionIds[] = $section->id;
        }

        return $this->_allSectionIds;
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
     * @return array All the editable sections’ IDs.
     */
    public function getEditableSectionIds(): array
    {
        if ($this->_editableSectionIds !== null) {
            return $this->_editableSectionIds;
        }

        $this->_editableSectionIds = [];

        foreach ($this->getAllSectionIds() as $sectionId) {
            if (Craft::$app->getUser()->checkPermission('editEntries:' . $sectionId)) {
                $this->_editableSectionIds[] = $sectionId;
            }
        }

        return $this->_editableSectionIds;
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
        if ($this->_fetchedAllSections) {
            return array_values($this->_sectionsById);
        }

        $results = $this->_createSectionQuery()
            ->all();

        $this->_sectionsById = [];

        foreach ($results as $result) {
            $section = new Section($result);
            $this->_sectionsById[$section->id] = $section;
        }

        $this->_fetchedAllSections = true;

        return array_values($this->_sectionsById);
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
        $editableSectionIds = $this->getEditableSectionIds();
        $editableSections = [];

        foreach ($this->getAllSections() as $section) {
            if (in_array($section->id, $editableSectionIds, false)) {
                $editableSections[] = $section;
            }
        }

        return $editableSections;
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
        $sections = [];

        foreach ($this->getAllSections() as $section) {
            if ($section->type == $type) {
                $sections[] = $section;
            }
        }

        return $sections;
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
        return count($this->getAllSectionIds());
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
        return count($this->getEditableSectionIds());
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
        if (!$sectionId) {
            return null;
        }

        if ($this->_sectionsById !== null && array_key_exists($sectionId, $this->_sectionsById)) {
            return $this->_sectionsById[$sectionId];
        }

        // If we've already fetched all sections we can save ourselves a trip to
        // the DB for section IDs that don't exist
        if ($this->_fetchedAllSections) {
            return null;
        }

        $result = $this->_createSectionQuery()
            ->where(['sections.id' => $sectionId])
            ->one();

        return $this->_sectionsById[$sectionId] = $result ? new Section($result) : null;
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
        $result = $this->_createSectionQuery()
            ->where(['sections.handle' => $sectionHandle])
            ->one();

        if (!$result) {
            return null;
        }

        $section = new Section($result);
        $this->_sectionsById[$section->id] = $section;
        return $section;
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
            ->from(['{{%sections_sites}} sections_sites'])
            ->innerJoin('{{%sites}} sites', '[[sites.id]] = [[sections_sites.siteId]]')
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

        if (!$isNewSection) {
            $sectionRecord = SectionRecord::find()
                ->where(['id' => $section->id])
                ->with('structure')
                ->one();

            if (!$sectionRecord) {
                throw new SectionNotFoundException("No section exists with the ID '{$section->id}'");
            }

            $oldSection = new Section($sectionRecord->toArray([
                'id',
                'structureId',
                'name',
                'handle',
                'type',
                'enableVersioning',
                'propagateEntries',
            ]));
        } else {
            $sectionRecord = new SectionRecord();
        }

        // Main section settings
        if ($section->type !== Section::TYPE_CHANNEL) {
            $section->propagateEntries = true;
        }

        /** @var SectionRecord $sectionRecord */
        $sectionRecord->name = $section->name;
        $sectionRecord->handle = $section->handle;
        $sectionRecord->type = $section->type;
        $sectionRecord->enableVersioning = (bool)$section->enableVersioning;
        $sectionRecord->propagateEntries = (bool)$section->propagateEntries;

        // Get the site settings
        $allSiteSettings = $section->getSiteSettings();

        if (empty($allSiteSettings)) {
            throw new Exception('Tried to save a section without any site settings');
        }

        $db = Craft::$app->getDb();
        $transaction = $db->beginTransaction();

        try {
            // Do we need to create a structure?
            if ($section->type === Section::TYPE_STRUCTURE) {
                /** @noinspection PhpUndefinedVariableInspection */
                if (!$isNewSection && $oldSection->type === Section::TYPE_STRUCTURE) {
                    $structure = Craft::$app->getStructures()->getStructureById($oldSection->structureId);
                    $isNewStructure = false;
                } else {
                    $structure = new Structure();
                    $isNewStructure = true;
                }

                // If they've set maxLevels to 0 (don't ask why), then pretend like there are none.
                if ((int)$section->maxLevels === 0) {
                    $section->maxLevels = null;
                }

                $structure->maxLevels = $section->maxLevels;
                Craft::$app->getStructures()->saveStructure($structure);

                $sectionRecord->structureId = $structure->id;
                $section->structureId = $structure->id;
            } else {
                /** @noinspection PhpUndefinedVariableInspection */
                if (!$isNewSection && $oldSection->structureId) {
                    // Delete the old one
                    Craft::$app->getStructures()->deleteStructureById($oldSection->structureId);
                    $sectionRecord->structureId = null;
                }
            }

            $sectionRecord->save(false);

            // Now that we have a section ID, save it on the model
            if ($isNewSection) {
                $section->id = $sectionRecord->id;
            }

            // Might as well update our cache of the section while we have it. (It's possible that the URL format
            //includes {section.handle} or something...)
            $this->_sectionsById[$section->id] = $section;

            // Update the site settings
            // -----------------------------------------------------------------

            if (!$isNewSection) {
                // Get the old section site settings
                $allOldSiteSettingsRecords = Section_SiteSettingsRecord::find()
                    ->where(['sectionId' => $section->id])
                    ->indexBy('siteId')
                    ->all();
            } else {
                $allOldSiteSettingsRecords = [];
            }

            foreach ($allSiteSettings as $siteId => $siteSettings) {
                // Was this already selected?
                if (!$isNewSection && isset($allOldSiteSettingsRecords[$siteId])) {
                    $siteSettingsRecord = $allOldSiteSettingsRecords[$siteId];
                } else {
                    $siteSettingsRecord = new Section_SiteSettingsRecord();
                    $siteSettingsRecord->sectionId = $section->id;
                    $siteSettingsRecord->siteId = $siteId;
                }

                $siteSettingsRecord->enabledByDefault = $siteSettings->enabledByDefault;

                if ($siteSettingsRecord->hasUrls = $siteSettings->hasUrls) {
                    $siteSettingsRecord->uriFormat = $siteSettings->uriFormat;
                    $siteSettingsRecord->template = $siteSettings->template;
                } else {
                    $siteSettingsRecord->uriFormat = $siteSettings->uriFormat = null;
                    $siteSettingsRecord->template = $siteSettings->template = null;
                }

                $siteSettingsRecord->save(false);

                // Set the ID on the model
                $siteSettings->id = $siteSettingsRecord->id;
            }

            if (!$isNewSection) {
                // Drop any sites that are no longer being used, as well as the associated entry/element site
                // rows
                $siteIds = array_keys($allSiteSettings);

                /** @noinspection PhpUndefinedVariableInspection */
                foreach ($allOldSiteSettingsRecords as $siteId => $siteSettingsRecord) {
                    if (!in_array($siteId, $siteIds, false)) {
                        $siteSettingsRecord->delete();
                    }
                }
            }

            // Make sure there's at least one entry type for this section
            // -----------------------------------------------------------------

            if (!$isNewSection) {
                $entryTypeExists = (new Query())
                    ->select(['id'])
                    ->from(['{{%entrytypes}}'])
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
                    $entryType->titleLabel = null;
                    $entryType->titleFormat = '{section.name|raw}';
                } else {
                    $entryType->hasTitleField = true;
                    $entryType->titleLabel = Craft::t('app', 'Title');
                    $entryType->titleFormat = null;
                }

                $this->saveEntryType($entryType);
            }

            // Now, regardless of whether the section type changed or not, let the section type make sure
            // everything is cool
            // -----------------------------------------------------------------

            switch ($section->type) {
                case Section::TYPE_SINGLE:
                    $this->_onSaveSingle($section, $isNewSection, $allSiteSettings);
                    break;
                case Section::TYPE_STRUCTURE:
                    /** @noinspection PhpUndefinedVariableInspection */
                    $this->_onSaveStructure($section, $isNewSection, $isNewStructure, $allOldSiteSettingsRecords);
                    break;
            }

            // Finally, deal with the existing entries...
            // -----------------------------------------------------------------

            if (!$isNewSection) {
                if ($section->propagateEntries) {
                    // Find a site that the section was already enabled in, and still is
                    $oldSiteIds = array_keys($allOldSiteSettingsRecords);
                    $newSiteIds = array_keys($allSiteSettings);
                    $persistentSiteIds = array_values(array_intersect($newSiteIds, $oldSiteIds));

                    // Try to make that the primary site, if it's in the list
                    $siteId = Craft::$app->getSites()->getPrimarySite()->id;
                    if (!in_array($siteId, $persistentSiteIds, false)) {
                        $siteId = $persistentSiteIds[0];
                    }

                    Craft::$app->getQueue()->push(new ResaveElements([
                        'description' => Craft::t('app', 'Resaving {section} entries', [
                            'section' => $section->name,
                        ]),
                        'elementType' => Entry::class,
                        'criteria' => [
                            'siteId' => $siteId,
                            'sectionId' => $section->id,
                            'status' => null,
                            'enabledForSite' => false,
                        ]
                    ]));
                } else {
                    // Resave entries for each site
                    foreach ($allSiteSettings as $siteId => $siteSettings) {
                        Craft::$app->getQueue()->push(new ResaveElements([
                            'description' => Craft::t('app', 'Resaving {section} entries ({site})', [
                                'section' => $section->name,
                                'site' => $siteSettings->getSite()->name,
                            ]),
                            'elementType' => Entry::class,
                            'criteria' => [
                                'siteId' => $siteId,
                                'sectionId' => $section->id,
                                'status' => null,
                                'enabledForSite' => false,
                            ]
                        ]));
                    }
                }
            }

            $transaction->commit();
        } catch (\Throwable $e) {
            $transaction->rollBack();

            throw $e;
        }

        // Fire an 'afterSaveSection' event
        if ($this->hasEventHandlers(self::EVENT_AFTER_SAVE_SECTION)) {
            $this->trigger(self::EVENT_AFTER_SAVE_SECTION, new SectionEvent([
                'section' => $section,
                'isNew' => $isNewSection
            ]));
        }

        return true;
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
                'section' => $section
            ]));
        }

        $transaction = Craft::$app->getDb()->beginTransaction();
        try {
            // Nuke the field layouts first.
            $entryTypeIds = [];
            $entryTypes = $this->getEntryTypesBySectionId($section->id);

            foreach ($entryTypes as $entryType) {
                $entryTypeIds[] = $entryType->id;
            }

            // Delete the field layout(s)
            $fieldLayoutIds = (new Query())
                ->select(['fieldLayoutId'])
                ->from(['{{%entrytypes}}'])
                ->where(['id' => $entryTypeIds])
                ->column();

            if (!empty($fieldLayoutIds)) {
                Craft::$app->getFields()->deleteLayoutById($fieldLayoutIds);
            }

            // Delete the entries
            // (loop through all the sites in case there are any lingering entries from unsupported sites)
            $entryQuery = Entry::find()
                ->anyStatus()
                ->sectionId($section->id);
            $elementsService = Craft::$app->getElements();
            foreach (Craft::$app->getSites()->getAllSiteIds() as $siteId) {
                foreach ($entryQuery->siteId($siteId)->each() as $entry) {
                    $elementsService->deleteElement($entry);
                }
            }

            // Delete the structure, if there is one
            $structureId = (new Query())
                ->select(['structureId'])
                ->from(['{{%sections}}'])
                ->where(['id' => $section->id])
                ->scalar();

            if ($structureId) {
                Craft::$app->getStructures()->deleteStructureById($structureId);
            }

            // Delete the section.
            Craft::$app->getDb()->createCommand()
                ->delete('{{%sections}}', ['id' => $section->id])
                ->execute();

            $transaction->commit();
        } catch (\Throwable $e) {
            $transaction->rollBack();

            throw $e;
        }

        // Fire an 'afterDeleteSection' event
        if ($this->hasEventHandlers(self::EVENT_AFTER_DELETE_SECTION)) {
            $this->trigger(self::EVENT_AFTER_DELETE_SECTION, new SectionEvent([
                'section' => $section
            ]));
        }

        return true;
    }

    /**
     * Returns whether a section’s entries have URLs for the given site ID, and if the section’s template path is valid.
     *
     * @param Section $section
     * @param int $siteId
     * @return bool
     */
    public function isSectionTemplateValid(Section $section, int $siteId): bool
    {
        $sectionSiteSettings = $section->getSiteSettings();

        if (isset($sectionSiteSettings[$siteId]) && $sectionSiteSettings[$siteId]->hasUrls) {
            // Set Craft to the site template mode
            $view = Craft::$app->getView();
            $oldTemplateMode = $view->getTemplateMode();
            $view->setTemplateMode($view::TEMPLATE_MODE_SITE);

            // Does the template exist?
            $templateExists = Craft::$app->getView()->doesTemplateExist((string)$sectionSiteSettings[$siteId]->template);

            // Restore the original template mode
            $view->setTemplateMode($oldTemplateMode);

            if ($templateExists) {
                return true;
            }
        }

        return false;
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
            ->where(['sectionId' => $sectionId])
            ->orderBy(['sortOrder' => SORT_ASC])
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
            ->where(['id' => $entryTypeId])
            ->one();

        return $this->_entryTypesById[$entryTypeId] = $result ? new EntryType($result) : null;
    }

    /**
     * Returns entry types that have a given handle.
     *
     * ---
     *
     * ```php
     * $entryType = Craft::$app->sections->getEntryTypeByHandle('article');
     * ```
     *
     * @param string $entryTypeHandle
     * @return EntryType[]
     */
    public function getEntryTypesByHandle(string $entryTypeHandle): array
    {
        $results = $this->_createEntryTypeQuery()
            ->where(['handle' => $entryTypeHandle])
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

        if ($entryType->id) {
            $entryTypeRecord = EntryTypeRecord::findOne($entryType->id);

            if (!$entryTypeRecord) {
                throw new EntryTypeNotFoundException("No entry type exists with the ID '{$entryType->id}'");
            }
        } else {
            $entryTypeRecord = new EntryTypeRecord();

            // Get the next biggest sort order
            $maxSortOrder = (new Query())
                ->from(['{{%entrytypes}}'])
                ->where(['sectionId' => $entryType->sectionId])
                ->max('[[sortOrder]]');

            $entryTypeRecord->sortOrder = $maxSortOrder ? $maxSortOrder + 1 : 1;
        }

        $entryTypeRecord->sectionId = $entryType->sectionId;
        $entryTypeRecord->name = $entryType->name;
        $entryTypeRecord->handle = $entryType->handle;
        $entryTypeRecord->hasTitleField = $entryType->hasTitleField;
        $entryTypeRecord->titleLabel = ($entryType->hasTitleField ? $entryType->titleLabel : null);
        $entryTypeRecord->titleFormat = (!$entryType->hasTitleField ? $entryType->titleFormat : null);

        $transaction = Craft::$app->getDb()->beginTransaction();

        try {
            // Save the field layout
            $fieldLayout = $entryType->getFieldLayout();
            Craft::$app->getFields()->saveLayout($fieldLayout);
            $entryType->fieldLayoutId = $fieldLayout->id;
            $entryTypeRecord->fieldLayoutId = $fieldLayout->id;

            // Save the entry type
            $entryTypeRecord->save(false);

            // Now that we have an entry type ID, save it on the model
            if (!$entryType->id) {
                $entryType->id = $entryTypeRecord->id;
            }

            // Might as well update our cache of the entry type while we have it.
            $this->_entryTypesById[$entryType->id] = $entryType;

            $transaction->commit();
        } catch (\Throwable $e) {
            $transaction->rollBack();

            throw $e;
        }

        // Fire an 'afterSaveEntryType' event
        if ($this->hasEventHandlers(self::EVENT_AFTER_SAVE_ENTRY_TYPE)) {
            $this->trigger(self::EVENT_AFTER_SAVE_ENTRY_TYPE, new EntryTypeEvent([
                'entryType' => $entryType,
                'isNew' => $isNewEntryType,
            ]));
        }

        if (!$isNewEntryType) {
            // Re-save the entries of this type
            $section = $entryType->getSection();
            $allSiteSettings = $section->getSiteSettings();

            if ($section->propagateEntries) {
                $siteIds = array_keys($allSiteSettings);

                Craft::$app->getQueue()->push(new ResaveElements([
                    'description' => Craft::t('app', 'Resaving {type} entries', [
                        'type' => ($section->type !== Section::TYPE_SINGLE ? $section->name . ' - ' : '') . $entryType->name,
                    ]),
                    'elementType' => Entry::class,
                    'criteria' => [
                        'siteId' => $siteIds[0],
                        'sectionId' => $section->id,
                        'typeId' => $entryType->id,
                        'status' => null,
                        'enabledForSite' => false,
                    ]
                ]));
            } else {
                foreach ($allSiteSettings as $siteId => $siteSettings) {
                    Craft::$app->getQueue()->push(new ResaveElements([
                        'description' => Craft::t('app', 'Resaving {type} entries ({site})', [
                            'type' => $entryType->name,
                            'site' => $siteSettings->getSite()->name,
                        ]),
                        'elementType' => Entry::class,
                        'criteria' => [
                            'siteId' => $siteId,
                            'sectionId' => $section->id,
                            'typeId' => $entryType->id,
                            'status' => null,
                            'enabledForSite' => false,
                        ]
                    ]));
                }
            }
        }

        return true;
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
        $transaction = Craft::$app->getDb()->beginTransaction();

        try {
            foreach ($entryTypeIds as $entryTypeOrder => $entryTypeId) {
                $entryTypeRecord = EntryTypeRecord::findOne($entryTypeId);
                $entryTypeRecord->sortOrder = $entryTypeOrder + 1;
                $entryTypeRecord->save();
            }

            $transaction->commit();
        } catch (\Throwable $e) {
            $transaction->rollBack();

            throw $e;
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

        $transaction = Craft::$app->getDb()->beginTransaction();
        try {
            // Delete the field layout
            $fieldLayoutId = (new Query())
                ->select(['fieldLayoutId'])
                ->from(['{{%entrytypes}}'])
                ->where(['id' => $entryType->id])
                ->scalar();

            if ($fieldLayoutId) {
                Craft::$app->getFields()->deleteLayoutById($fieldLayoutId);
            }

            // Delete the entries
            // (loop through all the sites in case there are any lingering entries from unsupported sites)
            $entryQuery = Entry::find()
                ->anyStatus()
                ->typeId($entryType->id);
            $elementsService = Craft::$app->getElements();
            foreach (Craft::$app->getSites()->getAllSiteIds() as $siteId) {
                foreach ($entryQuery->siteId($siteId)->each() as $entry) {
                    $elementsService->deleteElement($entry);
                }
            }

            // Delete the entry type.
            Craft::$app->getDb()->createCommand()
                ->delete('{{%entrytypes}}', ['id' => $entryType->id])
                ->execute();

            $transaction->commit();
        } catch (\Throwable $e) {
            $transaction->rollBack();

            throw $e;
        }

        // Fire an 'afterDeleteEntryType' event
        if ($this->hasEventHandlers(self::EVENT_AFTER_DELETE_ENTRY_TYPE)) {
            $this->trigger(self::EVENT_AFTER_DELETE_ENTRY_TYPE, new EntryTypeEvent([
                'entryType' => $entryType,
            ]));
        }

        return true;
    }

    // Private Methods
    // =========================================================================

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
                'sections.propagateEntries',
                'structures.maxLevels',
            ])
            ->leftJoin('{{%structures}} structures', '[[structures.id]] = [[sections.structureId]]')
            ->from(['{{%sections}} sections'])
            ->orderBy(['name' => SORT_ASC]);
    }

    /**
     * Performs some Single-specific tasks when a section is saved.
     *
     * @param Section $section
     * @param bool $isNewSection
     * @param Section_SiteSettings[] $allSiteSettings
     * @see saveSection()
     * @throws Exception if reasons
     */
    private function _onSaveSingle(Section $section, bool $isNewSection, array $allSiteSettings)
    {
        // Get all the entries that currently exist for this section
        // ---------------------------------------------------------------------

        if (!$isNewSection) {
            $entryData = (new Query())
                ->select([
                    'e.id',
                    'typeId',
                    'siteId' => (new Query())
                        ->select('es.siteId')
                        ->from('{{%elements_sites}} es')
                        ->where('[[es.elementId]] = [[e.id]]')
                        ->andWhere(['in', 'es.siteId', ArrayHelper::getColumn($allSiteSettings, 'siteId')])
                        ->limit(1)
                ])
                ->from(['{{%entries}} e'])
                ->where(['e.sectionId' => $section->id])
                ->orderBy(['e.id' => SORT_ASC])
                ->all();
        } else {
            $entryData = [];
        }

        // Get the section's entry types
        // ---------------------------------------------------------------------

        /** @var EntryType[] $entryTypes */
        $entryTypes = ArrayHelper::index($this->getEntryTypesBySectionId($section->id), 'id');

        if (empty($entryTypes)) {
            throw new Exception('Couldn’t find any entry types for the section: ' . $section->id);
        }

        // Get/save the entry
        // ---------------------------------------------------------------------

        $entry = null;

        // If there are any existing entries, find the first one with a valid typeId
        foreach ($entryData as $data) {
            if (isset($entryTypes[$data['typeId']])) {
                $entry = Entry::find()
                    ->id($data['id'])
                    ->siteId($data['siteId'])
                    ->anyStatus()
                    ->one();
                break;
            }
        }

        // Otherwise create a new one
        if ($entry === null) {
            // Create one
            $firstSiteSettings = reset($allSiteSettings);
            $firstEntryType = reset($entryTypes);

            $entry = new Entry();
            $entry->siteId = $firstSiteSettings->siteId;
            $entry->sectionId = $section->id;
            $entry->typeId = $firstEntryType->id;
            $entry->title = $section->name;
        }

        // (Re)save it with an updated title, slug, and URI format.
        $entry->setScenario(Element::SCENARIO_ESSENTIALS);
        if (!Craft::$app->getElements()->saveElement($entry)) {
            throw new Exception('Couldn’t save single entry due to validation errors on the slug and/or URI');
        }

        // Delete any other entries in the section
        // ---------------------------------------------------------------------

        foreach ($entryData as $data) {
            if ($data['id'] != $entry->id) {
                Craft::$app->getElements()->deleteElementById($data['id'], Entry::class, $data['siteId']);
            }
        }

        // Delete any other entry types in the section
        // ---------------------------------------------------------------------

        foreach ($entryTypes as $entryType) {
            if ($entryType->id != $entry->typeId) {
                $this->deleteEntryType($entryType);
            }
        }

        // Update the remaining entry type's name and handle, if this isn't a new section
        // ---------------------------------------------------------------------

        if (!$isNewSection) {
            $entryTypes[$entry->typeId]->name = $section->name;
            $entryTypes[$entry->typeId]->handle = $section->handle;
            $this->saveEntryType($entryTypes[$entry->typeId]);
        }
    }

    /**
     * Performs some Structure-specific tasks when a section is saved.
     *
     * @param Section $section
     * @param bool $isNewSection
     * @param bool $isNewStructure
     * @param Section_SiteSettingsRecord[] $allOldSiteSettingsRecords
     * @see saveSection()
     * @throws Exception if reasons
     */
    private function _onSaveStructure(Section $section, bool $isNewSection, bool $isNewStructure, array $allOldSiteSettingsRecords)
    {
        if (!$isNewSection && $isNewStructure) {
            // Add all of the entries to the structure
            $query = Entry::find();
            /** @noinspection PhpUndefinedVariableInspection */
            $query->siteId(ArrayHelper::firstKey($allOldSiteSettingsRecords));
            $query->sectionId($section->id);
            $query->anyStatus();
            $query->orderBy('elements.id');
            $query->withStructure(false);
            /** @var Entry $entry */
            foreach ($query->each() as $entry) {
                Craft::$app->getStructures()->appendToRoot($section->structureId, $entry, 'insert');
            }
        }
    }

    /**
     * @return Query
     */
    private function _createEntryTypeQuery()
    {
        return (new Query())
            ->select([
                'id',
                'sectionId',
                'fieldLayoutId',
                'name',
                'handle',
                'hasTitleField',
                'titleLabel',
                'titleFormat',
            ])
            ->from(['{{%entrytypes}}']);
    }
}
