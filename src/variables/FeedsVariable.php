<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2013 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\variables;

use craft\app\helpers\NumberHelper;
use craft\app\helpers\TemplateHelper;

/**
 * Class FeedsVariable
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class FeedsVariable
{
	// Public Methods
	// =========================================================================

	/**
	 * @param string $url
	 * @param int    $limit
	 * @param int    $offset
	 * @param null   $cacheDuration
	 *
	 * @return array
	 */
	public function getFeedItems($url, $limit = 0, $offset = 0, $cacheDuration = null)
	{
		$limit = NumberHelper::makeNumeric($limit);
		$offset = NumberHelper::makeNumeric($offset);
		$items = craft()->feeds->getFeedItems($url, $limit, $offset, $cacheDuration);

		// Prevent everyone from having to use the |raw filter when outputting the title and content
		$rawProperties = array('title', 'content', 'summary');

		foreach ($items as &$item)
		{
			foreach ($rawProperties as $prop)
			{
				$item[$prop] = TemplateHelper::getRaw($item[$prop]);
			}
		}

		return $items;
	}
}
