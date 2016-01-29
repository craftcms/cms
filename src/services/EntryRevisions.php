<?php
/**
 * @link      http://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   http://craftcms.com/license
 */

namespace craft\app\services;

use Craft;
use craft\app\db\Query;
use craft\app\errors\Exception;
use craft\app\events\DraftEvent;
use craft\app\events\EntryEvent;
use craft\app\helpers\ArrayHelper;
use craft\app\helpers\Json;
use craft\app\elements\Entry;
use craft\app\models\EntryDraft;
use craft\app\models\EntryVersion;
use craft\app\models\Section;
use craft\app\records\EntryDraft as EntryDraftRecord;
use craft\app\records\EntryVersion as EntryVersionRecord;
use yii\base\Component;

Craft::$app->requireEdition(Craft::Client);

/**
 * Class EntryRevisions service.
 *
 * An instance of the EntryRevisions service is globally accessible in Craft via [[Application::entryRevisions `Craft::$app->getEntryRevisions()`]].
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class EntryRevisions extends Component
{
    // Constants
    // =========================================================================

    /**
     * @event DraftEvent The event that is triggered after a draft is saved.
     */
    const EVENT_AFTER_SAVE_DRAFT = 'afterSaveDraft';

    /**
     * @event DraftEvent The event that is triggered after a draft is published.
     */
    const EVENT_AFTER_PUBLISH_DRAFT = 'afterPublishDraft';

    /**
     * @event DraftEvent The event that is triggered before a draft is deleted.
     *
     * You may set [[DraftEvent::isValid]] to `false` to prevent the draft from getting deleted.
     */
    const EVENT_BEFORE_DELETE_DRAFT = 'beforeDeleteDraft';

    /**
     * @event DraftEvent The event that is triggered after a draft is deleted.
     */
    const EVENT_AFTER_DELETE_DRAFT = 'afterDeleteDraft';

    /**
     * @event EntryEvent The event that is triggered after an entry is reverted to an old version.
     */
    const EVENT_AFTER_REVERT_ENTRY_TO_VERSION = 'afterRevertEntryToVersion';

    // Public Methods
    // =========================================================================

    /**
     * Returns a draft by its ID.
     *
     * @param integer $draftId
     *
     * @return EntryDraft|null
     */
    public function getDraftById($draftId)
    {
        $draftRecord = EntryDraftRecord::findOne($draftId);

        if ($draftRecord) {
            $config = ArrayHelper::toArray($draftRecord, [], false);
            $config['data'] = Json::decode($config['data']);
            $draft = EntryDraft::create($config);

            // This is a little hacky, but fixes a bug where entries are getting the wrong URL when a draft is published
            // inside of a structured section since the selected URL Format depends on the entry's level, and there's no
            // reason to store the level along with the other draft data.
            $entry = Craft::$app->getEntries()->getEntryById($draftRecord->entryId, $draftRecord->locale);

            $draft->root = $entry->root;
            $draft->lft = $entry->lft;
            $draft->rgt = $entry->rgt;
            $draft->level = $entry->level;

            return $draft;
        }
    }

    /**
     * Returns drafts of a given entry.
     *
     * @param integer $entryId
     * @param string  $localeId
     *
     * @return EntryDraft[]
     */
    public function getDraftsByEntryId($entryId, $localeId = null)
    {
        if (!$localeId) {
            $localeId = Craft::$app->getI18n()->getPrimarySiteLocale();
        }

        $drafts = [];

        $results = (new Query())
            ->select('*')
            ->from('{{%entrydrafts}}')
            ->where(['and', 'entryId = :entryId', 'locale = :locale'],
                [':entryId' => $entryId, ':locale' => $localeId])
            ->orderBy('name asc')
            ->all();

        foreach ($results as $result) {
            $result['data'] = Json::decode($result['data']);

            // Don't initialize the content
            unset($result['data']['fields']);

            $drafts[] = EntryDraft::create($result);
        }

        return $drafts;
    }

    /**
     * Returns the drafts of a given entry that are editable by the current user.
     *
     * @param integer $entryId
     * @param string  $localeId
     *
     * @return EntryDraft[]
     */
    public function getEditableDraftsByEntryId($entryId, $localeId = null)
    {
        $editableDrafts = [];
        $user = Craft::$app->getUser()->getIdentity();

        if ($user) {
            $allDrafts = $this->getDraftsByEntryId($entryId, $localeId);

            foreach ($allDrafts as $draft) {
                if ($draft->creatorId == $user->id || $user->can('editPeerEntryDrafts:'.$draft->sectionId)) {
                    $editableDrafts[] = $draft;
                }
            }
        }

        return $editableDrafts;
    }

    /**
     * Saves a draft.
     *
     * @param EntryDraft $draft
     *
     * @return boolean
     */
    public function saveDraft(EntryDraft $draft)
    {
        $draftRecord = $this->_getDraftRecord($draft);

        if (!$draft->name && $draft->id) {
            // Get the total number of existing drafts for this entry/locale
            $totalDrafts = (new Query())
                ->from('{{%entrydrafts}}')
                ->where(
                    ['and', 'entryId = :entryId', 'locale = :locale'],
                    [':entryId' => $draft->id, ':locale' => $draft->locale]
                )
                ->count('id');

            $draft->name = Craft::t('app', 'Draft {num}',
                ['num' => $totalDrafts + 1]);
        }

        $draftRecord->name = $draft->name;
        $draftRecord->notes = $draft->revisionNotes;
        $draftRecord->data = $this->_getRevisionData($draft);

        if ($draftRecord->save()) {
            $draft->draftId = $draftRecord->id;

            // Fire an 'afterSaveDraft' event
            $this->trigger(static::EVENT_AFTER_SAVE_DRAFT, new DraftEvent([
                'draft' => $draft
            ]));

            return true;
        } else {
            return false;
        }
    }

    /**
     * Publishes a draft.
     *
     * @param EntryDraft $draft
     *
     * @return boolean
     */
    public function publishDraft(EntryDraft $draft)
    {
        // If this is a single, we'll have to set the title manually
        if ($draft->getSection()->type == Section::TYPE_SINGLE) {
            $draft->title = $draft->getSection()->name;
        }

        // Set the version notes
        if (!$draft->revisionNotes) {
            $draft->revisionNotes = Craft::t('app', 'Published draft “{name}”.',
                ['name' => $draft->name]);
        }

        if (Craft::$app->getEntries()->saveEntry($draft)) {
            // Fire an 'afterPublishDraft' event
            $this->trigger(static::EVENT_AFTER_PUBLISH_DRAFT, new DraftEvent([
                'draft' => $draft
            ]));

            $this->deleteDraft($draft);

            return true;
        } else {
            return false;
        }
    }

    /**
     * Deletes a draft by it's model.
     *
     * @param EntryDraft $draft
     *
     * @return boolean
     * @throws \Exception
     * @throws \yii\db\Exception
     */
    public function deleteDraft(EntryDraft $draft)
    {
        $transaction = Craft::$app->getDb()->beginTransaction();

        try {
            // Fire a 'beforeDeleteDraft' event
            $event = new DraftEvent([
                'draft' => $draft
            ]);

            $this->trigger(static::EVENT_BEFORE_DELETE_DRAFT, $event);

            // Is the event giving us the go-ahead?
            if ($event->isValid) {
                $draftRecord = $this->_getDraftRecord($draft);
                $draftRecord->delete();

                $success = true;
            } else {
                $success = false;
            }

            // Commit the transaction regardless of whether we deleted the draft, in case something changed
            // in onBeforeDeleteDraft
            $transaction->commit();
        } catch (\Exception $e) {
            $transaction->rollback();

            throw $e;
        }

        if ($success) {
            // Fire an 'afterDeleteDraft' event
            $this->trigger(static::EVENT_AFTER_DELETE_DRAFT, new DraftEvent([
                'draft' => $draft
            ]));
        }

        return $success;
    }

    /**
     * Returns a version by its ID.
     *
     * @param integer $versionId
     *
     * @return EntryVersion|null
     */
    public function getVersionById($versionId)
    {
        $versionRecord = EntryVersionRecord::findOne($versionId);

        if ($versionRecord) {
            $config = ArrayHelper::toArray($versionRecord, [], false);
            $config['data'] = Json::decode($config['data']);
            return EntryVersion::create($config);
        }
    }

    /**
     * Returns versions by an entry ID.
     *
     * @param integer      $entryId        The entry ID to search for.
     * @param string       $localeId       The locale ID to search for.
     * @param integer|null $limit          The limit on the number of versions to retrieve.
     * @param boolean      $includeCurrent Whether to include the current "top" version of the entry.
     *
     * @return EntryVersion[]
     */
    public function getVersionsByEntryId($entryId, $localeId, $limit = null, $includeCurrent = false)
    {
        if (!$localeId) {
            $localeId = Craft::$app->getI18n()->getPrimarySiteLocale();
        }

        $versions = [];

        $results = (new Query())
            ->select('*')
            ->from('{{%entryversions}}')
            ->where(['and', 'entryId = :entryId', 'locale = :locale'],
                [':entryId' => $entryId, ':locale' => $localeId])
            ->orderBy('dateCreated desc')
            ->offset($includeCurrent ? 0 : 1)
            ->limit($limit)
            ->all();

        foreach ($results as $result) {
            $result['data'] = Json::decode($result['data']);

            // Don't initialize the content
            unset($result['data']['fields']);

            $versions[] = EntryVersion::create($result);
        }

        return $versions;
    }

    /**
     * Saves a new version.
     *
     * @param Entry $entry
     *
     * @return boolean
     */
    public function saveVersion(Entry $entry)
    {
        // Get the total number of existing versions for this entry/locale
        $totalVersions = (new Query())
            ->from('{{%entryversions}}')
            ->where(
                ['and', 'entryId = :entryId', 'locale = :locale'],
                [':entryId' => $entry->id, ':locale' => $entry->locale]
            )
            ->count('id');

        $versionRecord = new EntryVersionRecord();
        $versionRecord->entryId = $entry->id;
        $versionRecord->sectionId = $entry->sectionId;
        $versionRecord->creatorId = Craft::$app->getUser()->getIdentity() ? Craft::$app->getUser()->getIdentity()->id : $entry->authorId;
        $versionRecord->locale = $entry->locale;
        $versionRecord->num = $totalVersions + 1;
        $versionRecord->data = $this->_getRevisionData($entry);
        $versionRecord->notes = $entry->revisionNotes;

        return $versionRecord->save();
    }

    /**
     * Reverts an entry to a version.
     *
     * @param EntryVersion $version
     *
     * @return boolean
     */
    public function revertEntryToVersion(EntryVersion $version)
    {
        // If this is a single, we'll have to set the title manually
        if ($version->getSection()->type == Section::TYPE_SINGLE) {
            $version->title = $version->getSection()->name;
        }

        // Set the version notes
        $version->revisionNotes = Craft::t('app', 'Reverted version {num}.',
            ['num' => $version->num]);

        if (Craft::$app->getEntries()->saveEntry($version)) {
            // Fire an 'afterRevertEntryToVersion' event
            $this->trigger(static::EVENT_AFTER_REVERT_ENTRY_TO_VERSION,
                new EntryEvent([
                    'entry' => $version,
                ]));

            return true;
        } else {
            return false;
        }
    }

    // Private Methods
    // =========================================================================

    /**
     * Returns a draft record.
     *
     * @param EntryDraft $draft
     *
     * @throws Exception
     * @return EntryDraftRecord
     */
    private function _getDraftRecord(EntryDraft $draft)
    {
        if ($draft->draftId) {
            $draftRecord = EntryDraftRecord::findOne($draft->draftId);

            if (!$draftRecord) {
                throw new Exception(Craft::t('app', 'No draft exists with the ID “{id}”.', ['id' => $draft->draftId]));
            }
        } else {
            $draftRecord = new EntryDraftRecord();
            $draftRecord->entryId = $draft->id;
            $draftRecord->sectionId = $draft->sectionId;
            $draftRecord->creatorId = $draft->creatorId;
            $draftRecord->locale = $draft->locale;
        }

        return $draftRecord;
    }

    /**
     * Returns an array of all the revision data for a draft or version.
     *
     * @param EntryDraft|EntryVersion $revision
     *
     * @return array
     */
    private function _getRevisionData($revision)
    {
        $revisionData = [
            'typeId' => $revision->typeId,
            'authorId' => $revision->authorId,
            'title' => $revision->title,
            'slug' => $revision->slug,
            'postDate' => ($revision->postDate ? $revision->postDate->getTimestamp() : null),
            'expiryDate' => ($revision->expiryDate ? $revision->expiryDate->getTimestamp() : null),
            'enabled' => $revision->enabled,
            'newParentId'   => $revision->newParentId,
            'fields' => [],
        ];

        $content = $revision->getContentFromPost();

        foreach (Craft::$app->getFields()->getAllFields() as $field) {
            if (isset($content[$field->handle]) && $content[$field->handle] !== null) {
                $revisionData['fields'][$field->id] = $content[$field->handle];
            }
        }

        return $revisionData;
    }
}
