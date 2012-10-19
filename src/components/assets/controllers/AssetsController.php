<?php
namespace Blocks;

/**
 * Asset blocks controller class
 */
class AssetsController extends BaseEntityController
{
	/**
	 * Returns the block service instance.
	 *
	 * @return AssetBlocksService
	 */
	protected function getService()
	{
		return blx()->assets;
	}
}
