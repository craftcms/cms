<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\services;

use Craft;
use craft\db\Query;
use craft\elements\Entry;
use craft\errors\EntryTypeNotFoundException;
use craft\errors\SectionNotFoundException;
use craft\events\EntryTypeEvent;
use craft\events\SectionEvent;
use craft\helpers\ArrayHelper;
use craft\helpers\Db;
use craft\models\EntryType;
use craft\models\Section;
use craft\models\Section_SiteSettings;
use craft\models\Structure;
use craft\records\EntryType as EntryTypeRecord;
use craft\records\Section as SectionRecord;
use craft\records\Section_SiteSettings as Section_SiteSettingsRecord;
use craft\tasks\ResaveElements;
use yii\base\Component;
use yii\base\Exception;

/**
 * Class Sections service.
 *
 * An instance of the Sections service is globally accessible in Craft via [[Application::sections `Craft::$app->getSections()`]].
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
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
     * @return array All the editable sections’ IDs.
     */
    public function getEditableSectionIds(): array
    {
        if ($this->_editableSectionIds !== null) {
            return $this->_editableSectionIds;
        }

        $this->_editableSectionIds = [];

        foreach ($this->getAllSectionIds() as $sectionId) {
            if (Craft::$app->getUser()->checkPermission('editEntries:'.$sectionId)) {
                $this->_editableSectionIds[] = $sectionId;
            }
        }

        return $this->_editableSectionIds;
    }

    /**
     * Returns all sections.
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
     * @param string $type
     *
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
     * @return int
     */
    public function getTotalSections(): int
    {
        return count($this->getAllSectionIds());
    }

    /**
     * Gets the total number of sections that are editable by the current user.
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
     * @param int $sectionId
     *
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

        if (!$result) {
            return $this->_sectionsById[$sectionId] = null;
        }

        return $this->_sectionsById[$sectionId] = new Section($result);
    }

    /**
     * Gets a section by its handle.
     *
     * @param string $sectionHandle
     *
     * @return Section|null
     */
    public function getSectionByHandle(string $sectionHandle)
    {
        $result = $this->_createSectionQuery()
            ->where(['sections.handle' => $sectionHandle])
            ->one();

        if ($result) {
            $section = new Section($result);
            $this->_sectionsById[$section->id] = $section;

            return $section;
        }

        return null;
    }

    /**
     * Returns a section’s site-specific settings.
     *
     * @param int $sectionId
     *
     * @return Section_SiteSettings[] The section’s site-specific settings.
     */
    public function getSectionSiteSettings(int $sectionId): array
    {
        $siteSettings = (new Query())
            ->select([
                'sections_i18n.id',
                'sections_i18n.sectionId',
                'sections_i18n.siteId',
                'sections_i18n.enabledByDefault',
                'sections_i18n.hasUrls',
                'sections_i18n.uriFormat',
                'sections_i18n.template',
            ])
            ->from(['{{%sections_i18n}} sections_i18n'])
            ->innerJoin('{{%sites}} sites', '[[sites.id]] = [[sections_i18n.siteId]]')
            ->where(['sections_i18n.sectionId' => $sectionId])
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
     * @param Section $section       The section to be saved
     * @param bool    $runValidation Whether the section should be validated
     *
     * @return bool
     * @throws SectionNotFoundException if $section->id is invalid
     * @throws \Exception if reasons
     */
    public function saveSection(Section $section, bool $runValidation = true): bool
    {
        if ($runValidation && !$section->validate()) {
            Craft::info('Section not saved due to validation error.', __METHOD__);

            return false;
        }

        if ($section->id) {
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
            ]));
            $isNewSection = false;
        } else {
            $sectionRecord = new SectionRecord();
            $isNewSection = true;
        }

        // Main section settings
        /** @var SectionRecord $sectionRecord */
        $sectionRecord->name = $section->name;
        $sectionRecord->handle = $section->handle;
        $sectionRecord->type = $section->type;
        $sectionRecord->enableVersioning = $section->enableVersioning ? 1 : 0;

        // Get the site settings
        $allSiteSettings = $section->getSiteSettings();

        if (empty($allSiteSettings)) {
            throw new Exception('Tried to save a section without any site settings');
        }

        // Fire a 'beforeSaveSection' event
        $this->trigger(self::EVENT_BEFORE_SAVE_SECTION, new SectionEvent([
            'section' => $section,
            'isNew' => $isNewSection
        ]));

        $db = Craft::$app->getDb();
        $transaction = $db->beginTransaction();

        try {
            // Do we need to create a structure?
            if ($section->type == Section::TYPE_STRUCTURE) {
                /** @noinspection PhpUndefinedVariableInspection */
                if (!$isNewSection && $oldSection->type === Section::TYPE_STRUCTURE) {
                    $structure = Craft::$app->getStructures()->getStructureById($oldSection->structureId);
                    $isNewStructure = false;
                } else {
                    $structure = new Structure();
                    $isNewStructure = true;
                }

                // If they've set maxLevels to 0 (don't ask why), then pretend like there are none.
                if ($section->maxLevels === 0) {
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
                $siteSettingsRecord->hasUrls = $siteSettings->hasUrls;
                $siteSettingsRecord->uriFormat = $siteSettings->uriFormat;
                $siteSettingsRecord->template = $siteSettings->template;

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

            $entryTypeId = null;

            if (!$isNewSection) {
                // Let's grab all of the entry type IDs to save ourselves a query down the road if this is a Single
                $entryTypeIds = (new Query())
                    ->select(['id'])
                    ->from(['{{%entrytypes}}'])
                    ->where(['sectionId' => $section->id])
                    ->column();

                if (!empty($entryTypeIds)) {
                    $entryTypeId = array_shift($entryTypeIds);
                }
            }

            if (!$entryTypeId) {
                $entryType = new EntryType();

                $entryType->sectionId = $section->id;
                $entryType->name = $section->name;
                $entryType->handle = $section->handle;

                if ($section->type == Section::TYPE_SINGLE) {
                    $entryType->hasTitleField = false;
                    $entryType->titleLabel = null;
                    $entryType->titleFormat = '{section.name|raw}';
                } else {
                    $entryType->hasTitleField = true;
                    $entryType->titleLabel = Craft::t('app', 'Title');
                    $entryType->titleFormat = null;
                }

                $this->saveEntryType($entryType);

                $entryTypeId = $entryType->id;
            }

            // Now, regardless of whether the section type changed or not, let the section type make sure
            // everything is cool
            // -----------------------------------------------------------------

            switch ($section->type) {
                case Section::TYPE_SINGLE:
                    // Make sure that there is one and only one Entry Type and Entry for this section.
                    $singleEntryId = null;
                    if (!$isNewSection) {
                        // Re-save the entrytype name if the section name just changed
                        /** @noinspection PhpUndefinedVariableInspection */
                        if (!$isNewSection && $oldSection->name != $section->name) {
                            $entryType = $this->getEntryTypeById($entryTypeId);
                            $entryType->name = $section->name;
                            $this->saveEntryType($entryType);
                        }
                        // Make sure there's only one entry in this section
                        $results = (new Query())
                            ->select([
                                'id' => 'e.id',
                                'siteId' => (new Query())
                                    ->select('i18n.siteId')
                                    ->from('{{%elements_i18n}} i18n')
                                    ->where('[[i18n.elementId]] = [[e.id]]')
                                    ->limit(1)
                            ])
                            ->from(['{{%entries}} e'])
                            ->where(['e.sectionId' => $section->id])
                            ->all();
                        if (!empty($results)) {
                            $firstResult = array_shift($results);
                            $singleEntryId = $firstResult['id'];
                            // If there are any more, get rid of them
                            if (!empty($results)) {
                                foreach ($results as $result) {
                                    Craft::$app->getElements()->deleteElementById($result['id'], Entry::class, $result['siteId']);
                                }
                            }
                            // Make sure it's enabled and all that.
                            $db->createCommand()
                                ->update(
                                    '{{%elements}}',
                                    [
                                        'enabled' => 1,
                                        'archived' => 0,
                                    ],
                                    [
                                        'id' => $singleEntryId
                                    ])
                                ->execute();
                            $db->createCommand()
                                ->update(
                                    '{{%entries}}',
                                    [
                                        'typeId' => $entryTypeId,
                                        'authorId' => null,
                                        'postDate' => Db::prepareDateForDb(new \DateTime()),
                                        'expiryDate' => null,
                                    ],
                                    [
                                        'id' => $singleEntryId
                                    ])
                                ->execute();
                        }
                        // Make sure there's only one entry type for this section
                        /** @noinspection PhpUndefinedVariableInspection */
                        if (!empty($entryTypeIds)) {
                            foreach ($entryTypeIds as $entryTypeId) {
                                $this->deleteEntryTypeById($entryTypeId);
                            }
                        }
                    }
                    if (!$singleEntryId) {
                        // Create it
                        $firstSiteSettings = reset($allSiteSettings);
                        $singleEntry = new Entry();
                        $singleEntry->siteId = $firstSiteSettings->siteId;
                        $singleEntry->sectionId = $section->id;
                        $singleEntry->typeId = $entryTypeId;
                        $singleEntry->title = $section->name;
                        Craft::$app->getElements()->saveElement($singleEntry, false);
                    }
                    break;
                case Section::TYPE_STRUCTURE:
                    /** @noinspection PhpUndefinedVariableInspection */
                    if (!$isNewSection && $isNewStructure) {
                        // Add all of the entries to the structure
                        $query = Entry::find();
                        /** @noinspection PhpUndefinedVariableInspection */
                        $query->siteId(ArrayHelper::firstKey($allOldSiteSettingsRecords));
                        $query->sectionId($section->id);
                        $query->status(null);
                        $query->enabledForSite(false);
                        $query->orderBy('elements.id');
                        /** @var Entry $entry */
                        foreach ($query->each() as $entry) {
                            Craft::$app->getStructures()->appendToRoot($section->structureId, $entry, 'insert');
                        }
                    }
                    break;
            }

            // Finally, deal with the existing entries...
            // -----------------------------------------------------------------

            if (!$isNewSection) {
                // Get the most-primary site that this section was already enabled in
                /** @noinspection PhpUndefinedVariableInspection */
                $siteIds = array_values(array_intersect(Craft::$app->getSites()->getAllSiteIds(), array_keys($allOldSiteSettingsRecords)));

                if (!empty($siteIds)) {
                    Craft::$app->getTasks()->queueTask([
                        'type' => ResaveElements::class,
                        'description' => Craft::t('app', 'Resaving {section} entries', ['section' => $section->name]),
                        'elementType' => Entry::class,
                        'criteria' => [
                            'siteId' => $siteIds[0],
                            'sectionId' => $section->id,
                            'status' => null,
                            'enabledForSite' => false,
                            'limit' => null,
                        ]
                    ]);
                }
            }

            $transaction->commit();
        } catch (\Exception $e) {
            $transaction->rollBack();

            throw $e;
        }

        // Fire an 'afterSaveSection' event
        $this->trigger(self::EVENT_AFTER_SAVE_SECTION, new SectionEvent([
            'section' => $section,
            'isNew' => $isNewSection
        ]));

        return true;
    }

    /**
     * Deletes a section by its ID.
     *
     * @param int $sectionId
     *
     * @return bool Whether the section was deleted successfully
     * @throws \Exception if reasons
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
     * @param Section $section
     *
     * @return bool Whether the section was deleted successfully
     * @throws \Exception if reasons
     */
    public function deleteSection(Section $section): bool
    {
        // Fire a 'beforeDeleteSection' event
        $this->trigger(self::EVENT_BEFORE_DELETE_SECTION, new SectionEvent([
            'section' => $section
        ]));

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
            $entries = Entry::find()
                ->status(null)
                ->enabledForSite(false)
                ->sectionId($section->id)
                ->all();

            foreach ($entries as $entry) {
                Craft::$app->getElements()->deleteElement($entry);
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
        } catch (\Exception $e) {
            $transaction->rollBack();

            throw $e;
        }

        // Fire an 'afterDeleteSection' event
        $this->trigger(self::EVENT_AFTER_DELETE_SECTION, new SectionEvent([
            'section' => $section
        ]));

        return true;
    }

    /**
     * Returns whether a section’s entries have URLs for the given site ID, and if the section’s template path is valid.
     *
     * @param Section $section
     * @param int     $siteId
     *
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
            $templateExists = Craft::$app->getView()->doesTemplateExist($sectionSiteSettings[$siteId]->template);

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
     * @param int $sectionId
     *
     * @return EntryType[]
     */
    public function getEntryTypesBySectionId(int $sectionId): array
    {
        $entryTypeRecords = EntryTypeRecord::find()
            ->where(['sectionId' => $sectionId])
            ->orderBy(['sortOrder' => SORT_ASC])
            ->all();

        foreach ($entryTypeRecords as $key => $entryTypeRecord) {
            $entryTypeRecords[$key] = new EntryType($entryTypeRecord->toArray([
                'id',
                'sectionId',
                'fieldLayoutId',
                'name',
                'handle',
                'hasTitleField',
                'titleLabel',
                'titleFormat',
            ]));
        }

        return $entryTypeRecords;
    }

    /**
     * Returns an entry type by its ID.
     *
     * @param int $entryTypeId
     *
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

        if (($entryTypeRecord = EntryTypeRecord::findOne($entryTypeId)) === null) {
            return $this->_entryTypesById[$entryTypeId] = null;
        }

        return $this->_entryTypesById[$entryTypeId] = new EntryType($entryTypeRecord->toArray([
            'id',
            'sectionId',
            'fieldLayoutId',
            'name',
            'handle',
            'hasTitleField',
            'titleLabel',
            'titleFormat',
        ]));
    }

    /**
     * Returns entry types that have a given handle.
     *
     * @param string $entryTypeHandle
     *
     * @return EntryType[]
     */
    public function getEntryTypesByHandle(string $entryTypeHandle): array
    {
        $entryTypes = [];

        $entryTypeRecords = EntryTypeRecord::findAll([
            'handle' => $entryTypeHandle
        ]);

        foreach ($entryTypeRecords as $record) {
            $entryTypes[] = new EntryType($record->toArray([
                'id',
                'sectionId',
                'fieldLayoutId',
                'name',
                'handle',
                'hasTitleField',
                'titleLabel',
                'titleFormat',
            ]));
        }

        return $entryTypes;
    }

    /**
     * Saves an entry type.
     *
     * @param EntryType $entryType     The entry type to be saved
     * @param bool      $runValidation Whether the entry type should be validated
     *
     * @return bool Whether the entry type was saved successfully
     * @throws EntryTypeNotFoundException if $entryType->id is invalid
     * @throws \Exception if reasons
     */
    public function saveEntryType(EntryType $entryType, bool $runValidation = true): bool
    {
        if ($runValidation && !$entryType->validate()) {
            Craft::info('Entry type not saved due to validation error.', __METHOD__);

            return false;
        }

        $isNewEntryType = !$entryType->id;

        // Fire a 'beforeSaveEntryType' event
        $this->trigger(self::EVENT_BEFORE_SAVE_ENTRY_TYPE, new EntryTypeEvent([
            'entryType' => $entryType,
            'isNew' => $isNewEntryType,
        ]));

        if ($entryType->id) {
            $entryTypeRecord = EntryTypeRecord::findOne($entryType->id);

            if (!$entryTypeRecord) {
                throw new EntryTypeNotFoundException("No entry type exists with the ID '{$entryType->id}'");
            }

            $oldEntryType = new EntryType($entryTypeRecord->toArray([
                'id',
                'sectionId',
                'fieldLayoutId',
                'name',
                'handle',
                'hasTitleField',
                'titleLabel',
                'titleFormat',
            ]));
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
            // Is there a new field layout?
            $fieldLayout = $entryType->getFieldLayout();

            if (!$fieldLayout->id) {
                // Delete the old one
                /** @noinspection PhpUndefinedVariableInspection */
                if (!$isNewEntryType && $oldEntryType->fieldLayoutId) {
                    Craft::$app->getFields()->deleteLayoutById($oldEntryType->fieldLayoutId);
                }

                // Save the new one
                Craft::$app->getFields()->saveLayout($fieldLayout);

                // Update the entry type record/model with the new layout ID
                $entryType->fieldLayoutId = $fieldLayout->id;
                $entryTypeRecord->fieldLayoutId = $fieldLayout->id;
            }

            // Save the entry type
            $entryTypeRecord->save(false);

            // Now that we have an entry type ID, save it on the model
            if (!$entryType->id) {
                $entryType->id = $entryTypeRecord->id;
            }

            // Might as well update our cache of the entry type while we have it.
            $this->_entryTypesById[$entryType->id] = $entryType;

            $transaction->commit();
        } catch (\Exception $e) {
            $transaction->rollBack();

            throw $e;
        }

        // Fire an 'afterSaveEntryType' event
        $this->trigger(self::EVENT_AFTER_SAVE_ENTRY_TYPE, new EntryTypeEvent([
            'entryType' => $entryType,
            'isNew' => $isNewEntryType,
        ]));

        if (!$isNewEntryType) {
            // Re-save the entries of this type
            $section = $entryType->getSection();
            $siteIds = array_keys($section->getSiteSettings());

            Craft::$app->getTasks()->queueTask([
                'type' => ResaveElements::class,
                'description' => Craft::t('app', 'Resaving {type} entries', ['type' => $entryType->name]),
                'elementType' => Entry::class,
                'criteria' => [
                    'siteId' => $siteIds[0],
                    'sectionId' => $section->id,
                    'typeId' => $entryType->id,
                    'status' => null,
                    'enabledForSite' => false,
                ]
            ]);
        }

        return true;
    }

    /**
     * Reorders entry types.
     *
     * @param array $entryTypeIds
     *
     * @return bool Whether the entry types were reordered successfully
     * @throws \Exception if reasons
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
        } catch (\Exception $e) {
            $transaction->rollBack();

            throw $e;
        }

        return true;
    }

    /**
     * Deletes an entry type by its ID.
     *
     * @param int $entryTypeId
     *
     * @return bool Whether the entry type was deleted successfully
     * @throws \Exception if reasons
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
     * @param EntryType $entryType
     *
     * @return bool Whether the entry type was deleted successfully
     * @throws \Exception if reasons
     */
    public function deleteEntryType(EntryType $entryType): bool
    {
        // Fire a 'beforeSaveEntryType' event
        $this->trigger(self::EVENT_BEFORE_DELETE_ENTRY_TYPE, new EntryTypeEvent([
            'entryType' => $entryType,
        ]));

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
            $entries = Entry::find()
                ->status(null)
                ->enabledForSite(false)
                ->typeId($entryType->id)
                ->all();

            foreach ($entries as $entry) {
                Craft::$app->getElements()->deleteElement($entry);
            }

            // Delete the entry type.
            Craft::$app->getDb()->createCommand()
                ->delete('{{%entrytypes}}', ['id' => $entryType->id])
                ->execute();

            $transaction->commit();
        } catch (\Exception $e) {
            $transaction->rollBack();

            throw $e;
        }

        // Fire an 'afterDeleteEntryType' event
        $this->trigger(self::EVENT_AFTER_DELETE_ENTRY_TYPE, new EntryTypeEvent([
            'entryType' => $entryType,
        ]));

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
                'structures.maxLevels',
            ])
            ->leftJoin('{{%structures}} structures', '[[structures.id]] = [[sections.structureId]]')
            ->from(['{{%sections}} sections'])
            ->orderBy(['name' => SORT_ASC]);
    }
}
