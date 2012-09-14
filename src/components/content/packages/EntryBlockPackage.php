<?php
namespace Blocks;

/**
 * Entry block package class.
 *
 * Used for transporting entry block data throughout the system.
 */
class EntryBlockPackage extends BlockPackage
{
	/* BLOCKSPRO ONLY */
	public $sectionId;
	/* end BLOCKSPRO ONLY */

	/**
	 * Saves the entry block.
	 *
	 * @return bool
	 */
	public function save()
	{
		return blx()->content->saveEntryBlock($this);
	}
}
