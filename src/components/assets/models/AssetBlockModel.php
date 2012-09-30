<?php
namespace Blocks;

/**
 * Asset block model class
 *
 * Used for transporting asset block data throughout the system.
 */
class AssetBlockModel extends BaseBlockModel
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
