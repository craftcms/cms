<?php
/**
 * @link      http://craftcms.com/
 * @copyright Copyright (c) 2015 Pixel & Tonic, Inc.
 * @license   http://craftcms.com/license
 */

namespace craft\app\web\twig\variables;

use craft\app\models\EntryDraft as EntryDraftModel;
use craft\app\models\EntryVersion as EntryVersionModel;

\Craft::$app->requireEdition(\Craft::Client);

/**
 * Class EntryRevisions variable.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class EntryRevisions
{
    // Public Methods
    // =========================================================================

    // Drafts
    // -------------------------------------------------------------------------

    /**
     * Returns entry drafts by an entry ID.
     *
     * @param integer $entryId
     * @param string  $localeId
     *
     * @return array
     */
    public function getDraftsByEntryId($entryId, $localeId = null)
    {
        return \Craft::$app->getEntryRevisions()->getDraftsByEntryId($entryId, $localeId);
    }

    /**
     * Returns the drafts of a given entry that are editable by the current user.
     *
     * @param integer $entryId
     * @param string  $localeId
     *
     * @return array
     */
    public function getEditableDraftsByEntryId($entryId, $localeId = null)
    {
        return \Craft::$app->getEntryRevisions()->getEditableDraftsByEntryId($entryId, $localeId);
    }

    /**
     * Returns an entry draft by its offset.
     *
     * @param integer $draftId
     *
     * @return EntryDraftModel|null
     */
    public function getDraftById($draftId)
    {
        return \Craft::$app->getEntryRevisions()->getDraftById($draftId);
    }

    // Versions
    // -------------------------------------------------------------------------

    /**
     * Returns entry versions by an entry ID.
     *
     * @param integer $entryId
     * @param string  $localeId
     *
     * @return array
     */
    public function getVersionsByEntryId($entryId, $localeId)
    {
        return \Craft::$app->getEntryRevisions()->getVersionsByEntryId($entryId, $localeId, 10);
    }

    /**
     * Returns an entry version by its ID.
     *
     * @param integer $versionId
     *
     * @return EntryVersionModel|null
     */
    public function getVersionById($versionId)
    {
        return \Craft::$app->getEntryRevisions()->getVersionById($versionId);
    }
}
