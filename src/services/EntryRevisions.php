<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\services;

use Craft;
use craft\elements\Entry;
use craft\errors\EntryDraftNotFoundException;
use yii\base\Component;
use yii\base\InvalidConfigException;

/**
 * Entry Revisions service.
 * An instance of the Entry Revisions service is globally accessible in Craft via [[\craft\base\ApplicationTrait::getEntryRevisions()|`Craft::$app->entryRevisions`]].
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 * @deprecated in 3.2.0
 */
class EntryRevisions extends Component
{
    /**
     * @event DraftEvent The event that is triggered before a draft is saved.
     * @deprecated in 3.2.0
     */
    const EVENT_BEFORE_SAVE_DRAFT = 'beforeSaveDraft';

    /**
     * @event DraftEvent The event that is triggered after a draft is saved.
     * @deprecated in 3.2.0
     */
    const EVENT_AFTER_SAVE_DRAFT = 'afterSaveDraft';

    /**
     * @event DraftEvent The event that is triggered before a draft is published.
     * @deprecated in 3.2.0
     */
    const EVENT_BEFORE_PUBLISH_DRAFT = 'beforePublishDraft';

    /**
     * @event DraftEvent The event that is triggered after a draft is published.
     * @deprecated in 3.2.0
     */
    const EVENT_AFTER_PUBLISH_DRAFT = 'afterPublishDraft';

    /**
     * @event DraftEvent The event that is triggered before a draft is deleted.
     * @deprecated in 3.2.0
     */
    const EVENT_BEFORE_DELETE_DRAFT = 'beforeDeleteDraft';

    /**
     * @event DraftEvent The event that is triggered after a draft is deleted.
     * @deprecated in 3.2.0
     */
    const EVENT_AFTER_DELETE_DRAFT = 'afterDeleteDraft';

    /**
     * @event VersionEvent The event that is triggered before an entry is reverted to an old version.
     * @deprecated in 3.2.0
     */
    const EVENT_BEFORE_REVERT_ENTRY_TO_VERSION = 'beforeRevertEntryToVersion';

    /**
     * @event VersionEvent The event that is triggered after an entry is reverted to an old version.
     * @deprecated in 3.2.0
     */
    const EVENT_AFTER_REVERT_ENTRY_TO_VERSION = 'afterRevertEntryToVersion';

    /**
     * Returns a draft by its ID.
     *
     * @param int $draftId
     * @return Entry|null
     * @deprecated in 3.2.0. Use an entry query instead.
     */
    public function getDraftById(int $draftId)
    {
        return Entry::find()
            ->draftId($draftId)
            ->anyStatus()
            ->one();
    }

    /**
     * Returns drafts of a given entry.
     *
     * @param int $entryId
     * @param int|null $siteId
     * @param bool $withContent Whether the field content should be included on the drafts
     * @return Entry[]
     * @deprecated in 3.2.0. Use an entry query instead.
     */
    public function getDraftsByEntryId(int $entryId, int $siteId = null, bool $withContent = false): array
    {
        return Entry::find()
            ->draftOf($entryId)
            ->siteId($siteId)
            ->anyStatus()
            ->orderBy(['drafts.name' => SORT_ASC])
            ->all();
    }

    /**
     * Returns the drafts of a given entry that are editable by the current user.
     *
     * @param int $entryId
     * @param int|null $siteId
     * @return Entry[]
     * @throws InvalidConfigException
     * @deprecated in 3.2.0. Use [[\craft\services\Drafts::getEditableDrafts()]] instead.
     */
    public function getEditableDraftsByEntryId(int $entryId, int $siteId = null): array
    {
        $entry = Entry::find()
            ->id($entryId)
            ->siteId($siteId)
            ->anyStatus()
            ->one();

        if (!$entry) {
            return [];
        }

        return Craft::$app->getDrafts()->getEditableDrafts($entry, 'editPeerEntryDrafts:' . $entry->getSection()->uid);
    }

    /**
     * Returns a version by its ID.
     *
     * @param int $versionId
     * @return Entry|null
     * @deprecated in 3.2.0. Use an entry query instead.
     */
    public function getVersionById(int $versionId)
    {
        return Entry::find()
            ->revisions()
            ->id($versionId)
            ->anyStatus()
            ->one();
    }

    /**
     * Returns whether an entry has any versions stored.
     *
     * @param int $entryId The entry ID to search for
     * @param int|null $siteId The site ID to search for
     * @return bool
     * @deprecated in 3.2.0. Use an entry query instead.
     */
    public function doesEntryHaveVersions(int $entryId, int $siteId = null): bool
    {
        return Entry::find()
            ->revisionOf($entryId)
            ->siteId('*')
            ->anyStatus()
            ->exists();
    }

    /**
     * Returns versions by an entry ID.
     *
     * @param int $entryId The entry ID to search for.
     * @param int|null $siteId The site ID to search for.
     * @param int|null $limit The limit on the number of versions to retrieve.
     * @param bool $includeCurrent Whether to include the current "top" version of the entry.
     * @param bool $withContent Whether the field content should be included on the versions
     * @return Entry[]
     * @deprecated in 3.2.0. Use an entry query instead.
     */
    public function getVersionsByEntryId(int $entryId, int $siteId = null, int $limit = null, bool $includeCurrent = false, bool $withContent = false): array
    {
        return Entry::find()
            ->revisionOf($entryId)
            ->siteId($siteId)
            ->anyStatus()
            ->offset($includeCurrent ? 0 : 1)
            ->limit($limit)
            ->orderBy(['num' => SORT_DESC])
            ->all();
    }
}
