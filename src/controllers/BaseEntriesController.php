<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\controllers;

use Craft;
use craft\elements\Entry;
use craft\models\EntryDraft;
use craft\models\Section;
use craft\web\Controller;

/**
 * BaseEntriesController is a base class that any entry-related controllers, such as [[EntriesController]] and
 * [[EntryRevisionsController]], extend to share common functionality.
 * It extends [[Controller]], overwriting specific methods as required.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
abstract class BaseEntriesController extends Controller
{
    // Protected Methods
    // =========================================================================

    /**
     * Enforces all Edit Entry permissions.
     *
     * @param Entry $entry
     * @param bool $duplicate
     */
    protected function enforceEditEntryPermissions(Entry $entry, bool $duplicate = false)
    {
        $userSessionService = Craft::$app->getUser();
        $permissionSuffix = ':'.$entry->sectionId;

        if (Craft::$app->getIsMultiSite()) {
            // Make sure they have access to this site
            $this->requirePermission('editSite:'.$entry->siteId);
        }

        // Make sure the user is allowed to edit entries in this section
        $this->requirePermission('editEntries'.$permissionSuffix);

        // Is it a new entry?
        if (!$entry->id || $duplicate) {
            // Make sure they have permission to create new entries in this section
            $this->requirePermission('createEntries'.$permissionSuffix);
        } else {
            switch (get_class($entry)) {
                case Entry::class:
                    // If it's another user's entry (and it's not a Single), make sure they have permission to edit those
                    if (
                        $entry->authorId != $userSessionService->getIdentity()->id &&
                        $entry->getSection()->type !== Section::TYPE_SINGLE
                    ) {
                        $this->requirePermission('editPeerEntries'.$permissionSuffix);
                    }

                    break;

                case EntryDraft::class:
                    // If it's another user's draft, make sure they have permission to edit those
                    /** @var EntryDraft $entry */
                    if (!$entry->creatorId || $entry->creatorId != $userSessionService->getIdentity()->id) {
                        $this->requirePermission('editPeerEntryDrafts'.$permissionSuffix);
                    }

                    break;
            }
        }
    }
}
