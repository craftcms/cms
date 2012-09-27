<?php
namespace Blocks;

/**
 * Asset block package class
 *
 * Used for transporting asset block data throughout the system.
 */
class AssetBlockPackage extends BaseBlockPackage
{
	/**
	 * Saves the asset block.
	 *
	 * @return bool
	 */
	public function save()
	{
		return blx()->assetBlocks->saveBlock($this);
	}
}
