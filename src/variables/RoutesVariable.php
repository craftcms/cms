<?php
namespace Craft;

/**
 * Route functions.
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://craftcms.com/license Craft License Agreement
 * @see       http://craftcms.com
 * @package   craft.app.variables
 * @since     1.0
 */
class RoutesVariable
{
	// Public Methods
	// =========================================================================

	/**
	 * Returns the routes defined in the CP.
	 *
	 * @return array
	 */
	public function getDbRoutes()
	{
		$routes = array();

		$results = craft()->db->createCommand()
			->select('id, locale, urlParts, template')
			->from('routes')
			->order('sortOrder')
			->queryAll();

		foreach ($results as $result)
		{
			$urlDisplayHtml = '';
			$urlParts = JsonHelper::decode($result['urlParts']);

			foreach ($urlParts as $part)
			{
				if (is_string($part))
				{
					$urlDisplayHtml .= $part;
				}
				else
				{
					$urlDisplayHtml .= HtmlHelper::encodeParams('<span class="token" data-name="{partZero}" data-value="{partOne}"><span>{partZero}</span></span>', array('partZero' => $part[0], 'partOne' => $part[1]));
				}
			}

			$routes[] = array(
				'id'             => $result['id'],
				'locale'         => $result['locale'],
				'urlDisplayHtml' => $urlDisplayHtml,
				'template'       => $result['template']
			);
		}

		return $routes;
	}
}
