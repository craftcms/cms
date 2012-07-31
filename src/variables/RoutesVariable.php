<?php
namespace Blocks;

/**
 * Route functions
 */
class RoutesVariable
{
	/**
	 * Returns all routes.
	 */
	public function all()
	{
		$return = array();

		$routes = blx()->routes->getAllRoutes();
		foreach ($routes as $route)
		{
			$urlDisplayHtml = '';
			$urlParts = Json::decode($route->url_parts);
			foreach ($urlParts as $part)
			{
				if (is_string($part))
					$urlDisplayHtml .= $part;
				else
					$urlDisplayHtml .= '<span class="var" data-name="'.$part[0].'" data-value="'.$part[1].'">'.$part[0].'</span>';
			}

			$return[] = array(
				'id' => $route->id,
				'urlDisplayHtml' => $urlDisplayHtml,
				'template' => $route->template
			);
		}

		return $return;
	}
}
