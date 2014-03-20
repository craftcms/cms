<?php
namespace Craft;

/**
 * Route functions
 */
class RoutesVariable
{
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

		$results = RouteRecord::model()->ordered()->findAll();

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
					$urlDisplayHtml .= '<span class="token" data-name="'.$part[0].'" data-value="'.$part[1].'"><span>'.$part[0].'</span></span>';
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
