<?php
namespace Blocks;

/**
 * Page functions
 */
class PagesVariable
{
	/**
	 * Gets all pages.
	 *
	 * @param array|null $params
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
