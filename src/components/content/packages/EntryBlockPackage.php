<?php
namespace Blocks;

/**
 * Entry block package class
 *
 * Used for transporting entry block data throughout the system.
 */
class EntryBlockPackage extends BaseBlockPackage
{
	/**
	 * Saves the entry block.
	 *
	 * @return bool
	 */
	public function save()
	{
		return blx()->entryBlocks->saveBlock($this);
	}
}
