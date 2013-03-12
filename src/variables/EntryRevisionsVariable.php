<?php
namespace Craft;

Craft::requirePackage(CraftPackage::PublishPro);

/**
 *
 */
class EntryRevisionsVariable
{
	// -------------------------------------------
	//  Drafts
	// -------------------------------------------

	/**
	 * Returns entry drafts by an entry ID.
	 *
	 * @param int $entryId
	 * @return array
	 */
	public function getDraftsByEntryId($entryId)
	{
		return craft()->entryRevisions->getDraftsByEntryId($entryId);
	}

	/**
	 * Returns the drafts of a given entry that are editable by the current user.
	 *
	 * @param int $entryId
	 * @return array
	 */
	public function getEditableDraftsByEntryId($entryId)
	{
		return craft()->entryRevisions->getEditableDraftsByEntryId($entryId);
	}

	/**
	 * Returns an entry draft by its offset.
	 *
	 * @param $draftId
	 * @return EntryDraftModel|null
	 */
	public function getDraftById($draftId)
	{
		return craft()->entryRevisions->getDraftById($draftId);
	}

	/**
	 * Returns an entry draft by its offset.
	 *
	 * @param int $entryId
	 * @param int $offset
	 * @return EntryDraftModel|null
	 */
	public function getDraftByOffset($entryId, $offset = 0)
	{
		return craft()->entryRevisions->getDraftByOffset($entryId, $offset);
	}

	// -------------------------------------------
	//  Versions
	// -------------------------------------------

	/**
	 * Returns entry versions by an entry ID.
	 *
	 * @param int $entryId
	 * @return array
	 */
	public function getVersionsByEntryId($entryId)
	{
		return craft()->entryRevisions->getVersionsByEntryId($entryId);
	}

	/**
	 * Returns an entry version by its ID.
	 *
	 * @param $versionId
	 * @return EntryVersionModel|null
	 */
	public function getVersionById($versionId)
	{
		return craft()->entryRevisions->getVersionById($versionId);
	}

	/**
	 * Returns an entry version by its offset.
	 *
	 * @param int $entryId
	 * @param int $offset
	 * @return EntryVersionModel|null
	 */
	public function getVersionByOffset($entryId, $offset = 0)
	{
		return craft()->entryRevisions->getVersionByOffset($entryId, $offset);
	}
}
