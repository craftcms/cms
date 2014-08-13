<?php
namespace Craft;

/**
 * Class LogRouter
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://buildwithcraft.com/license Craft License Agreement
 * @see       http://buildwithcraft.com
 * @package   craft.app.etc.logging
 * @since     1.0
 */
class LogRouter extends \CLogRouter
{
	// Public Methods
	// =========================================================================

	/**
	 * @param $route
	 *
	 * @return null
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
	 *
	 * @return null
	 */
	public function removeRoute($class)
	{
		$match = false;

		for ($counter = 0; $counter < sizeof($this->_routes); $counter++)
		{
			if (StringHelper::toLowerCase(get_class($this->_routes[$counter])) == StringHelper::toLowerCase(__NAMESPACE__.'\\'.$class))
			{
				$match = $counter;
				break;
			}
		}

		if (is_numeric($match))
		{
			array_splice($this->_routes, $match, 1);
		}
	}
}
