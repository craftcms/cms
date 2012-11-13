<?php
namespace Blocks;

/**
 * Page functions
 */
class PagesVariable
{
	// -------------------------------------------
	//  Page Blocks
	// -------------------------------------------

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
	public function getBlockById($id)
	{
		return blx()->pages->getBlockById($id);
	}

	// -------------------------------------------
	//  Pages
	// -------------------------------------------

	/**
	 * Gets all pages.
	 *
	 * @return array
	 */
	public function getAllPages()
	{
		return blx()->pages->getAllPages();
	}

	/**
	 * Gets the total number of pages.
	 *
	 * @return int
	 */
	public function getTotalPages()
	{
		return blx()->pages->getTotalPages();
	}

	/**
	 * Gets a page by its ID.
	 *
	 * @param int $id
	 * @return PageModel|null
	 */
	public function getPageById($id)
	{
		return blx()->pages->getPageById($id);
	}

	/**
	 * Gets a page by its URI.
	 *
	 * @param string $uri
	 * @return PageModel|null
	 */
	public function getPageByUri($uri)
	{
		return blx()->pages->getPageByUri($uri);
	}
}
