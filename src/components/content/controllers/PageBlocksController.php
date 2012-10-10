<?php
namespace Blocks;

/**
 * Page blocks controller class
 */
class PageBlocksController extends BaseBlocksController
{
	/**
	 * Returns the block service instance.
	 *
	 * @return PageBlocksService
	 */
	protected function getService()
	{
		return blx()->pageBlocks;
	}

	/**
	 * Populates a block model from post.
	 *
	 * @access protected
	 * @return EntryBlockModel
	 */
	protected function populateBlockFromPost()
	{
		$block = parent::populateBlockFromPost();
		$block->pageId = blx()->request->getRequiredPost('pageId');

		return $block;
	}
}
