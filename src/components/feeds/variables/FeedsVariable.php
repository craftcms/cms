<?php
namespace Blocks;

/**
 *
 */
class FeedsVariable
{
	/**
	 * @return array
	 */
	public function getFeedItems($url, $limit = 0, $offset = 0)
	{
		$items = blx()->feeds->getFeedItems($url, $limit, $offset);

		// Prevent everyone from having to use the |raw filter when outputting the title
		$charset = blx()->templates->getTwig()->getCharset();

		foreach ($items as &$item)
		{
			$item['title'] = new \Twig_Markup($item['title'], $charset);
		}

		return $items;
	}
}
