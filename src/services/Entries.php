<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\app\services;

use Craft;
use craft\app\db\Query;
use craft\app\errors\EntryNotFoundException;
use craft\app\errors\SectionNotFoundException;
use craft\app\events\EntryEvent;
use craft\app\helpers\DateTimeHelper;
use craft\app\elements\Entry;
use craft\app\models\Section;
use craft\app\records\Entry as EntryRecord;
use yii\base\Component;
use yii\base\Exception;

/**
 * The Entries service provides APIs for managing entries in Craft.
 *
 * An instance of the Entries service is globally accessible in Craft via [[Application::entries `Craft::$app->getEntries()`]].
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class Entries extends Component
{
    // Constants
    // =========================================================================

    /**
     * @event EntryEvent The event that is triggered before an entry is saved.
     *
     * You may set [[EntryEvent::isValid]] to `false` to prevent the entry from getting saved.
     */
    const EVENT_BEFORE_SAVE_ENTRY = 'beforeSaveEntry';

    /**
     * @event EntryEvent The event that is triggered after an entry is saved.
     */
    const EVENT_AFTER_SAVE_ENTRY = 'afterSaveEntry';

    /**
     * @event EntryEvent The event that is triggered before an entry is deleted.
     *
     * You may set [[EntryEvent::isValid]] to `false` to prevent the entry from being deleted.
     */
    const EVENT_BEFORE_DELETE_ENTRY = 'beforeDeleteEntry';

    /**
     * @event EntryEvent The event that is triggered after an entry is deleted.
     */
    const EVENT_AFTER_DELETE_ENTRY = 'afterDeleteEntry';

    // Public Methods
    // =========================================================================

    /**
     * Returns an entry by its ID.
     *
     * ```php
     * $entry = Craft::$app->getEntries()->getEntryById($entryId);
     * ```
     *
     * @param integer $entryId  The entry’s ID.
     * @param string  $localeId The locale to fetch the entry in.
     *                          Defaults to [[Application::language `Craft::$app->language`]].
     *
     * @return Entry|null The entry with the given ID, or `null` if an entry could not be found.
     */
    public function getEntryById($entryId, $localeId = null)
    {
        if (!$entryId) {
            return null;
        }

        // Get the structure ID
        $structureId = (new Query())
            ->select('sections.structureId')
            ->from('{{%entries}} entries')
            ->innerJoin('{{%sections}} sections', 'sections.id = entries.sectionId')
            ->where(['entries.id' => $entryId])
            ->scalar();

        return Entry::find()
            ->id($entryId)
            ->structureId($structureId)
            ->locale($localeId)
            ->status(null)
            ->localeEnabled(false)
            ->one();
    }

    /**
     * Saves a new or existing entry.
     *
     * ```php
     * $entry = new Entry();
     * $entry->sectionId = 10;
     * $entry->typeId = 1;
     * $entry->authorId = 5;
     * $entry->enabled = true;
     * $entry->title = "Hello World!";
     *
     * $entry->setFieldValuesFromPost(
     *     [
     *         'body' => "<p>I can’t believe I literally just called this “Hello World!”.</p>",
     *     ]);
     *
     * $success = Craft::$app->getEntries()->saveEntry($entry);
     *
     * if (!$success) {
     *     Craft::error('Couldn’t save the entry "'.$entry->title.'"', __METHOD__);
     * }
     * ```
     *
     * @param Entry $entry The entry to be saved.
     *
     * @return boolean
     * @throws EntryNotFoundException if $entry->newParentId or $entry->id is invalid
     * @throws SectionNotFoundException if $entry->sectionId is invalid
     * @throws Exception if $entry->locale is set to a locale that its section doesn’t support
     * @throws \Exception if reasons
     */
    public function saveEntry(Entry $entry)
    {
        $isNewEntry = !$entry->id;

        $hasNewParent = $this->_checkForNewParent($entry);

        if ($hasNewParent) {
            if ($entry->newParentId) {
                $parentEntry = $this->getEntryById($entry->newParentId, $entry->locale);

                if (!$parentEntry) {
                    throw new EntryNotFoundException("No entry exists with the ID '{$entry->newParentId}'");
                }
            } else {
                $parentEntry = null;
            }

            $entry->setParent($parentEntry);
        }

        // Get the entry record
        if (!$isNewEntry) {
            $entryRecord = EntryRecord::findOne($entry->id);

            if (!$entryRecord) {
                throw new EntryNotFoundException("No entry exists with the ID '{$entry->id}'");
            }
        } else {
            $entryRecord = new EntryRecord();
        }

        // Get the section
        $section = Craft::$app->getSections()->getSectionById($entry->sectionId);

        if (!$section) {
            throw new SectionNotFoundException("No section exists with the ID '{$entry->sectionId}'");
        }

        // Verify that the section is available in this locale
        $sectionLocales = $section->getLocales();

        if (!isset($sectionLocales[$entry->locale])) {
            throw new Exception("The section '{$section->name}' is not enabled for the locale '{$entry->locale}'");
        }

        // Set the entry data
        $entryType = $entry->getType();

        $entryRecord->sectionId = $entry->sectionId;

        if ($section->type == Section::TYPE_SINGLE) {
            $entryRecord->authorId = $entry->authorId = null;
            $entryRecord->expiryDate = $entry->expiryDate = null;
        } else {
            $entryRecord->authorId = $entry->authorId;
            $entryRecord->postDate = $entry->postDate;
            $entryRecord->expiryDate = $entry->expiryDate;
            $entryRecord->typeId = $entryType->id;
        }

        if ($entry->enabled && !$entryRecord->postDate) {
            // Default the post date to the current date/time
            $entryRecord->postDate = $entry->postDate = DateTimeHelper::currentUTCDateTime();
        }

        $entryRecord->validate();
        $entry->addErrors($entryRecord->getErrors());

        if ($entry->hasErrors()) {
            return false;
        }

        if (!$entryType->hasTitleField) {
            $entry->title = Craft::$app->getView()->renderObjectTemplate($entryType->titleFormat, $entry);
        }

        $transaction = Craft::$app->getDb()->beginTransaction();

        try {
            // Fire a 'beforeSaveEntry' event
            $event = new EntryEvent([
                'entry' => $entry
            ]);

            $this->trigger(self::EVENT_BEFORE_SAVE_ENTRY, $event);

            // Is the event giving us the go-ahead?
            if ($event->isValid) {
                // Save the element
                $success = Craft::$app->getElements()->saveElement($entry);

                // If it didn't work, rollback the transaction in case something changed in onBeforeSaveEntry
                if (!$success) {
                    $transaction->rollBack();

                    // If "title" has an error, check if they've defined a custom title label.
                    if ($entry->getFirstError('title')) {
                        // Grab all of the original errors.
                        $errors = $entry->getErrors();

                        // Grab just the title error message.
                        $originalTitleError = $errors['title'];

                        // Clear the old.
                        $entry->clearErrors();

                        // Create the new "title" error message.
                        $errors['title'] = str_replace(Craft::t('app', 'Title'), $entryType->titleLabel, $originalTitleError);

                        // Add all of the errors back on the model.
                        $entry->addErrors($errors);
                    }

                    return false;
                }

                // Now that we have an element ID, save it on the other stuff
                if ($isNewEntry) {
                    $entryRecord->id = $entry->id;
                }

                // Save the actual entry row
                $entryRecord->save(false);

                if ($section->type == Section::TYPE_STRUCTURE) {
                    // Has the parent changed?
                    if ($hasNewParent) {
                        if (!$entry->newParentId) {
                            Craft::$app->getStructures()->appendToRoot($section->structureId, $entry);
                        } else {
                            /** @noinspection PhpUndefinedVariableInspection */
                            Craft::$app->getStructures()->append($section->structureId, $entry, $parentEntry);
                        }
                    }

                    // Update the entry's descendants, who may be using this entry's URI in their own URIs
                    Craft::$app->getElements()->updateDescendantSlugsAndUris($entry, true, true);
                }

                // Save a new version
                if ($section->enableVersioning) {
                    Craft::$app->getEntryRevisions()->saveVersion($entry);
                }
            } else {
                $success = false;
            }

            // Commit the transaction regardless of whether we saved the entry, in case something changed
            // in onBeforeSaveEntry
            $transaction->commit();
        } catch (\Exception $e) {
            $transaction->rollBack();

            throw $e;
        }

        if ($success) {
            // Fire an 'afterSaveEntry' event
            $this->trigger(self::EVENT_AFTER_SAVE_ENTRY, new EntryEvent([
                'entry' => $entry
            ]));
        }

        return $success;
    }

    /**
     * Deletes an entry(s).
     *
     * @param Entry|Entry[] $entries An entry, or an array of entries, to be deleted.
     *
     * @return boolean Whether the entry deletion was successful.
     * @throws \Exception if reasons
     */
    public function deleteEntry($entries)
    {
        if (!$entries) {
            return false;
        }

        $transaction = Craft::$app->getDb()->beginTransaction();

        try {
            if (!is_array($entries)) {
                $entries = [$entries];
            }

            $entryIds = [];

            foreach ($entries as $entry) {
                // Fire a 'beforeDeleteEntry' event
                $event = new EntryEvent([
                    'entry' => $entry
                ]);

                $this->trigger(self::EVENT_BEFORE_DELETE_ENTRY, $event);

                if ($event->isValid) {
                    $section = $entry->getSection();

                    if ($section->type == Section::TYPE_STRUCTURE) {
                        // First let's move the entry's children up a level, so this doesn't mess up the structure.
                        $children = $entry->getChildren()->status(null)->localeEnabled(false)->limit(null)->all();

                        foreach ($children as $child) {
                            Craft::$app->getStructures()->moveBefore($section->structureId, $child, $entry, 'update');
                        }
                    }

                    $entryIds[] = $entry->id;
                }
            }

            if ($entryIds) {
                // Delete 'em
                $success = Craft::$app->getElements()->deleteElementById($entryIds);
            } else {
                $success = false;
            }

            $transaction->commit();
        } catch (\Exception $e) {
            $transaction->rollBack();

            throw $e;
        }

        if ($success) {
            foreach ($entries as $entry) {
                // Fire an 'afterDeleteEntry' event
                $this->trigger(self::EVENT_AFTER_DELETE_ENTRY,
                    new EntryEvent([
                        'entry' => $entry
                    ]));
            }

            return true;
        }

        return false;
    }

    /**
     * Deletes an entry(s) by its ID.
     *
     * @param integer|array $entryId The ID of an entry to delete, or an array of entry IDs.
     *
     * @return boolean Whether the entry deletion was successful.
     */
    public function deleteEntryById($entryId)
    {
        if (!$entryId) {
            return false;
        }

        $entries = Entry::find()
            ->id($entryId)
            ->limit(null)
            ->status(null)
            ->localeEnabled(false)
            ->all();

        if ($entries) {
            return $this->deleteEntry($entries);
        }

        return false;
    }

    // Private Methods
    // =========================================================================

    /**
     * Checks if an entry was submitted with a new parent entry selected.
     *
     * @param Entry $entry
     *
     * @return boolean
     */
    private function _checkForNewParent(Entry $entry)
    {
        // Make sure this is a Structure section
        if ($entry->getSection()->type != Section::TYPE_STRUCTURE) {
            return false;
        }

        // Is it a brand new entry?
        if (!$entry->id) {
            return true;
        }

        // Was a new parent ID actually submitted?
        if ($entry->newParentId === null) {
            return false;
        }

        // Is it set to the top level now, but it hadn't been before?
        if ($entry->newParentId === '' && $entry->level != 1) {
            return true;
        }

        // Is it set to be under a parent now, but didn't have one before?
        if ($entry->newParentId !== '' && $entry->level == 1) {
            return true;
        }

        // Is the parentId set to a different entry ID than its previous parent?
        $oldParentId = Entry::find()
            ->ancestorOf($entry)
            ->ancestorDist(1)
            ->status(null)
            ->locale($entry->locale)
            ->localeEnabled(false)
            ->select('elements.id')
            ->scalar();

        if ($entry->newParentId != $oldParentId) {
            return true;
        }

        // Must be set to the same one then
        return false;
    }
}
