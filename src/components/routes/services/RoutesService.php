<?php
namespace Blocks;

/**
 *
 */
class RoutesService extends BaseApplicationComponent
{
	/**
	 * Returns all of the routes.
	 */
	public function getAllRoutes()
	{
		$routes = array();

		$records = RouteRecord::model()->ordered()->findAll();

		foreach ($records as $record)
		{
			$routes[$record->urlPattern] = $record->template;
		}

		return $routes;
	}

	/**
	 * Returns a route by its ID.
	 *
	 * @param int $routeId The route ID
	 */
	public function getRouteById($routeId)
	{
		$route = RouteRecord::model()->findById($routeId);
		return $route;
	}

	/**
	 * Saves a new or existing route.
	 *
	 * @param array  $urlParts The URL as defined by the user.
	 * This is an array where each element is either a string
	 * or an array containing the name of a subpattern and the subpattern.
	 * @param string $template The template to route matching URLs to.
	 * @param int    $routeId The route ID, if editing an existing route.
	 *
	 * @throws Exception
	 * @return RouteRecord
	 */
	public function saveRoute($urlParts, $template, $routeId = null)
	{
		if ($routeId !== null)
		{
			$route = $this->getRouteById($routeId);

			if (!$route)
			{
				throw new Exception(Blocks::t('No route exists with the ID “{id}”', array('id' => $routeId)));
			}
		}
		else
		{
			$route = new RouteRecord();

			// Get the next biggest sort order
			$maxSortOrder = blx()->db->createCommand()
				->select('max(sortOrder)')
				->from('routes')
				->queryScalar();

			$route->sortOrder = $maxSortOrder + 1;
		}

		// Compile the URL parts into a regex pattern
		$urlPattern = '';
		$urlParts = array_filter($urlParts);

		foreach ($urlParts as $part)
		{
			if (is_string($part))
			{
				// Escape any special regex characters
				$urlPattern .= StringHelper::escapeRegexChars($part);
			}
			else if (is_array($part))
			{
				// Add the var as a named subpattern
				$urlPattern .= '(?P<'.$part[0].'>'.$part[1].')';
			}
		}

		$route->urlParts = JsonHelper::encode($urlParts);
		$route->urlPattern = $urlPattern;
		$route->template = $template;
		$route->save();

		return $route;
	}

	/**
	 * Deletes a route by its ID.
	 *
	 * @param int $routeId
	 * @return bool
	 */
	public function deleteRouteById($routeId)
	{
		blx()->db->createCommand()->delete('routes', array('id' => $routeId));
		return true;
	}

	/**
	 * Updates the route order.
	 *
	 * @param array $routeIds An array of each of the route IDs, in their new order.
	 */
	public function updateRouteOrder($routeIds)
	{
		foreach ($routeIds as $order => $routeId)
		{
			$data = array('sortOrder' => $order + 1);
			$condition = array('id' => $routeId);
			blx()->db->createCommand()->update('routes', $data, $condition);
		}
	}
}
