<?php
namespace Blocks;

/**
 * Entry draft package class
 *
 * Used for transporting entry draft data throughout the system.
 */
class EntryDraftPackage extends EntryPackage
{
	public $draftId;

	/**
	 * Saves the entry.
	 *
	 * @return bool
	 */
	public function save()
	{
		return blx()->content->saveEntryDraft($this);
	}
}
