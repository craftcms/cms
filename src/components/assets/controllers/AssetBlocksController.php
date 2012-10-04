<?php
namespace Blocks;

/**
 * Asset blocks controller class
 */
class AssetBlocksController extends BaseBlocksController
{
	/**
	 * Returns the block service instance.
	 *
	 * @return AssetBlocksService
	 */
	protected function getService()
	{
		return blx()->assetBlocks;
	}
}
