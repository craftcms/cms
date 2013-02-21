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

		// Prevent everyone from having to use the |raw filter when outputting the title and content
		$rawProperties = array('title', 'content', 'summary');
		$charset = blx()->templates->getTwig()->getCharset();

		foreach ($items as &$item)
		{
			foreach ($rawProperties as $prop)
			{
				$item[$prop] = new \Twig_Markup($item[$prop], $charset);
			}
		}

		return $items;
	}
}
