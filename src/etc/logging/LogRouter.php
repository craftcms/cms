<?php
namespace Craft;

/**
 *
 */
class LogRouter extends \CLogRouter
{
	/**
	 * @param $route
	 */
	public function addRoute($route)
	{
		$counter = count($this->_routes);
		$route = Craft::createComponent($route);
		$route->init();
		$this->_routes[$counter] = $route;
	}

	/**
	 * Removes a route from the LogRouter by class name.
	 *
	 * @param $class
	 */
	public function removeRoute($class)
	{
		$match = false;

		for ($counter = 0; $counter < sizeof($this->_routes); $counter++)
		{
			if (mb_strtolower(get_class($this->_routes[$counter])) == mb_strtolower(__NAMESPACE__.'\\'.$class))
			{
				$match = $counter;
				break;
			}
		}

		if ($match)
		{
			array_splice($this->_routes, $match, 1);
		}
	}
}
