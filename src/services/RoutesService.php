<?php
namespace Blocks;

/**
 *
 */
class RoutesService extends \CApplicationComponent
{
	/**
	 * Returns all of the routes.
	 */
	public function getAllRoutes()
	{
		return Route::model()->ordered()->findAll();
	}

	/**
	 * Returns a route by its ID.
	 *
	 * @param int $routeId The route ID
	 */
	public function getRouteById($routeId)
	{
		$route = Route::model()->findById($routeId);
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
	 * @return Route
	 */
	public function saveRoute($urlParts, $template, $routeId = null)
	{
		if ($routeId !== null)
		{
			$route = $this->getRouteById($routeId);

			if (!$route)
				throw new Exception(Blocks::t('No route exists with the Id “{routeId}”', array('routeId' => $routeId)));
		}
		else
		{
			$route = new Route();

			// Get the next biggest sort order
			$query = blx()->db->createCommand()
				->select('MAX(sort_order) AS max_sort_order')
				->from('routes')
				->queryRow();

			$route->sort_order = $query['max_sort_order'] + 1;
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
				$urlPattern .= '(?<'.$part[0].'>'.$part[1].')';
			}
		}

		$route->url_parts = Json::encode($urlParts);
		$route->url_pattern = $urlPattern;
		$route->template = $template;
		$route->save();

		return $route;
	}

	/**
	 * Deletes a route.
	 *
	 * @param int $routeId The route ID
	 * @throws Exception
	 * @return void
	 */
	public function deleteRoute($routeId)
	{
		$route = $this->getRouteById($routeId);

		if (!$route)
			throw new Exception(Blocks::t('No route exists with the Id “{routeId}”', array('routeId' => $routeId)));

		$route->delete();
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
			$data = array('sort_order' => $order + 1);
			$condition = array('id' => $routeId);
			blx()->db->createCommand()->update('routes', $data, $condition);
		}
	}
}
