<?php
namespace Blocks;

/**
 * Entry blocks controller class
 */
class EntryBlocksController extends BaseBlocksController
{
	/**
	 * Returns the block service instance.
	 *
	 * @return EntryBlocksService
	 */
	protected function getService()
	{
		return blx()->entryBlocks;
	}
}
