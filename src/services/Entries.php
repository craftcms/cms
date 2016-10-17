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
use craft\app\elements\Entry;
use craft\app\models\Section;
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
     * @event EntryEvent The event that is triggered before an entry is deleted.
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
     * @param integer $entryId The entry’s ID.
     * @param integer $siteId  The site to fetch the entry in. Defaults to the current site.
     *
     * @return Entry|null The entry with the given ID, or `null` if an entry could not be found.
     */
    public function getEntryById($entryId, $siteId = null)
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
            ->siteId($siteId)
            ->status(null)
            ->enabledForSite(false)
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
     * @param Entry   $entry         The entry to be saved.
     * @param boolean $runValidation Whether the entry should be validated
     *
     * @return bool
     * @throws EntryNotFoundException if $entry->newParentId or $entry->id is invalid
     * @throws Exception if $entry->siteId is set to a site that its section doesn’t support
     * @throws SectionNotFoundException if $entry->sectionId is invalid
     * @throws \Exception if reasons
     */
    public function saveEntry(Entry $entry, $runValidation = true)
    {
        return Craft::$app->getElements()->saveElement($entry, $runValidation);
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

        if (!is_array($entries)) {
            $entries = [$entries];
        }

        $transaction = Craft::$app->getDb()->beginTransaction();

        try {
            $entryIds = [];

            foreach ($entries as $entry) {
                // Fire a 'beforeDeleteEntry' event
                $this->trigger(self::EVENT_BEFORE_DELETE_ENTRY, new EntryEvent([
                    'entry' => $entry
                ]));

                $section = $entry->getSection();

                if ($section->type == Section::TYPE_STRUCTURE) {
                    // First let's move the entry's children up a level, so this doesn't mess up the structure.
                    $children = $entry->getChildren()->status(null)->enabledForSite(false)->limit(null)->all();

                    foreach ($children as $child) {
                        Craft::$app->getStructures()->moveBefore($section->structureId, $child, $entry, 'update');
                    }
                }

                $entryIds[] = $entry->id;
            }

            // Delete 'em
            Craft::$app->getElements()->deleteElementById($entryIds);

            foreach ($entries as $entry) {
                // Fire an 'afterDeleteEntry' event
                $this->trigger(self::EVENT_AFTER_DELETE_ENTRY, new EntryEvent([
                    'entry' => $entry
                ]));
            }
            $transaction->commit();
        } catch (\Exception $e) {
            $transaction->rollBack();

            throw $e;
        }

        return true;
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
            ->enabledForSite(false)
            ->all();

        if ($entries) {
            return $this->deleteEntry($entries);
        }

        return false;
    }
}
