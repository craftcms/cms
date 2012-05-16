<?php
namespace Blocks;

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
		$route = Blocks::createComponent($route);
		$route->init();
		$this->_routes[$counter] = $route;
	}
}
