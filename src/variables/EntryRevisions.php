<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2013 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\variables;

use craft\app\models\EntryDraft as EntryDraftModel;
use craft\app\models\EntryVersion as EntryVersionModel;

\Craft::$app->requireEdition(\Craft::Client);

/**
 * Class EntryRevisions variable.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
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
	 * @param int    $entryId
	 * @param string $localeId
	 *
	 * @return array
	 */
	public function getDraftsByEntryId($entryId, $localeId = null)
	{
		return \Craft::$app->entryRevisions->getDraftsByEntryId($entryId, $localeId);
	}

	/**
	 * Returns the drafts of a given entry that are editable by the current user.
	 *
	 * @param int    $entryId
	 * @param string $localeId
	 *
	 * @return array
	 */
	public function getEditableDraftsByEntryId($entryId, $localeId = null)
	{
		return \Craft::$app->entryRevisions->getEditableDraftsByEntryId($entryId, $localeId);
	}

	/**
	 * Returns an entry draft by its offset.
	 *
	 * @param int $draftId
	 *
	 * @return EntryDraftModel|null
	 */
	public function getDraftById($draftId)
	{
		return \Craft::$app->entryRevisions->getDraftById($draftId);
	}

	/**
	 * Returns an entry draft by its offset.
	 *
	 * @param int $entryId
	 * @param int $offset
	 *
	 * @return EntryDraftModel|null
	 */
	public function getDraftByOffset($entryId, $offset = 0)
	{
		return \Craft::$app->entryRevisions->getDraftByOffset($entryId, $offset);
	}

	// Versions
	// -------------------------------------------------------------------------

	/**
	 * Returns entry versions by an entry ID.
	 *
	 * @param int    $entryId
	 * @param string $localeId
	 *
	 * @return array
	 */
	public function getVersionsByEntryId($entryId, $localeId)
	{
		return \Craft::$app->entryRevisions->getVersionsByEntryId($entryId, $localeId, 10);
	}

	/**
	 * Returns an entry version by its ID.
	 *
	 * @param int $versionId
	 *
	 * @return EntryVersionModel|null
	 */
	public function getVersionById($versionId)
	{
		return \Craft::$app->entryRevisions->getVersionById($versionId);
	}

	/**
	 * Returns an entry version by its offset.
	 *
	 * @param int $entryId
	 * @param int $offset
	 *
	 * @return EntryVersionModel|null
	 */
	public function getVersionByOffset($entryId, $offset = 0)
	{
		return \Craft::$app->entryRevisions->getVersionByOffset($entryId, $offset);
	}
}
