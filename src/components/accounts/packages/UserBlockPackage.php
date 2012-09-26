<?php
namespace Blocks;

/**
 * User block package class
 *
 * Used for transporting user block data throughout the system.
 */
class UserBlockPackage extends BaseBlockPackage
{
	/**
	 * Saves the user block.
	 *
	 * @return bool
	 */
	public function save()
	{
		return blx()->userBlocks->saveBlock($this);
	}
}
