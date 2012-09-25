<?php
namespace Blocks;

/**
 * User blocks controller class
 */
class EntryBlocksController extends BaseBlocksController
{
	protected $blockPackageClass = 'EntryBlockPackage';
	protected $service = 'entryBlocks';
	/* BLOCKSPRO ONLY */

	/**
	 * Populates a block package from post.
	 *
	 * @access protected
	 * @return EntryBlockPackage
	 */
	protected function populateBlockFromPost()
	{
		$block = parent::populateBlockFromPost();
		$block->sectionId = blx()->request->getRequiredPost('sectionId');
		return $block;
	}
	/* end BLOCKSPRO ONLY */
}
