<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2013 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\logging;

use craft\app\Craft;
use craft\app\helpers\StringHelper;

/**
 * Class LogRouter
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
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
