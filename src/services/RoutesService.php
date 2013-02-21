<?php
namespace Blocks;

/**
 *
 */
class RoutesService extends BaseApplicationComponent
{
	private $_routes;

	/**
	 * Returns all of the routes.
	 *
	 * @return array
	 */
	public function getAllRoutes()
	{
		if (!isset($this->_routes))
		{
			$this->_routes = array();

			// Where should we look for routes?
			if (blx()->config->get('siteRoutesSource') == 'file')
			{
				$path = blx()->path->getConfigPath().'routes.php';

				if (IOHelper::fileExists($path))
				{
					$this->_routes = require_once $path;
				}
			}
			else
			{
				$records = RouteRecord::model()->ordered()->findAll();

				foreach ($records as $record)
				{
					$this->_routes[$record->urlPattern] = $record->template;
				}
			}
		}

		return $this->_routes;
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
			$route = $this->_getRecordRouteById($routeId);

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

	/**
	 * Returns a route by its ID.
	 *
	 * @param int $routeId The route ID
	 * @return RouteRecord|null
	 */
	private function _getRecordRouteById($routeId)
	{
		return RouteRecord::model()->findById($routeId);
	}
}
