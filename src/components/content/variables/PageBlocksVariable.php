<?php
namespace Blocks;

/**
 * Page block functions
 */
class PageBlocksVariable
{
	/**
	 * Returns all page blocks.
	 *
	 * @return array
	 */
	public function getAllBlocks()
	{
		return blx()->pages->getAllBlocks();
	}

	/**
	 * Returns all page blocks by a given page ID.
	 *
	 * @param int $pageId
	 * @return array
	 */
	public function getBlocksByPageId($pageId)
	{
		return blx()->pages->getBlocksByPageId($pageId);
	}

	/**
	 * Returns the total number of page blocks by a given page ID.
	 *
	 * @param int $pageId
	 * @return int
	 */
	public function getTotalBlocksByPageId($pageId)
	{
		if (Blocks::hasPackage(BlocksPackage::PublishPro))
		{
			return blx()->pages->getTotalBlocksByPageId($pageId);
		}
	}

	/**
	 * Gets an page block by its ID.
	 *
	 * @param int $id
	 * @return PageBlockModel|null
	 */
	public function getPageBlockById($id)
	{
		return blx()->pages->getBlockById($id);
	}
}
