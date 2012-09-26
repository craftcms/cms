<?php
namespace Blocks;

/**
 * User block package class
 *
 * Used for transporting user block data throughout the system.
 */
class UserProfileBlockPackage extends BaseBlockPackage
{
	/**
	 * Saves the user block.
	 *
	 * @return bool
	 */
	public function save()
	{
		return blx()->userProfileBlocks->saveBlock($this);
	}
}
