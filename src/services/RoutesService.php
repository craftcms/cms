<?php
namespace Craft;

/**
 * Class RoutesService
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://buildwithcraft.com/license Craft License Agreement
 * @see       http://buildwithcraft.com
 * @package   craft.app.services
 * @since     1.0
 */
class RoutesService extends BaseApplicationComponent
{
	// Public Methods
	// =========================================================================

	/**
	 * Returns the routes defined in craft/config/routes.php
	 *
	 * @return array
	 */
	public function getConfigFileRoutes()
	{
		$path = craft()->path->getConfigPath().'routes.php';

		if (IOHelper::fileExists($path))
		{
			$routes = require_once($path);

			if (is_array($routes))
			{
				// Check for any locale-specific routes
				$locale = craft()->language;

				if (isset($routes[$locale]) && is_array($routes[$locale]))
				{
					$localizedRoutes = $routes[$locale];
					unset($routes[$locale]);

					// Merge them so that the localized routes come first
					$routes = array_merge($localizedRoutes, $routes);
				}

				return $routes;
			}
		}

		return array();
	}

	/**
	 * Returns the routes defined in the CP.
	 *
	 * @return array
	 */
	public function getDbRoutes()
	{
		$results = craft()->db->createCommand()
			->select('urlPattern, template')
			->from('routes')
			->where(array('or', 'locale is null', 'locale = :locale'), array(':locale' => craft()->language))
			->order('sortOrder')
			->queryAll();

		if ($results)
		{
			$routes = array();

			foreach ($results as $result)
			{
				$routes[$result['urlPattern']] = $result['template'];
			}

			return $routes;
		}

		return array();
	}

	/**
	 * Saves a new or existing route.
	 *
	 * @param array       $urlParts The URL as defined by the user. This is an array where each element is either a
	 *                              string or an array containing the name of a subpattern and the subpattern.
	 * @param string      $template The template to route matching URLs to.
	 * @param int|null    $routeId  The route ID, if editing an existing route.
	 * @param string|null $locale
	 *
	 * @throws Exception
	 * @return RouteRecord
	 */
	public function saveRoute($urlParts, $template, $routeId = null, $locale = null)
	{
		if ($routeId !== null)
		{
			$routeRecord = RouteRecord::model()->findById($routeId);

			if (!$routeRecord)
			{
				throw new Exception(Craft::t('No route exists with the ID “{id}”.', array('id' => $routeId)));
			}
		}
		else
		{
			$routeRecord = new RouteRecord();

			// Get the next biggest sort order
			$maxSortOrder = craft()->db->createCommand()
				->select('max(sortOrder)')
				->from('routes')
				->queryScalar();

			$routeRecord->sortOrder = $maxSortOrder + 1;
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
				// Is the name a valid handle?
				if (preg_match('/^[a-zA-Z][a-zA-Z0-9_]*$/', $part[0]))
				{
					// Add the var as a named subpattern
					$urlPattern .= '(?P<'.preg_quote($part[0]).'>'.$part[1].')';
				}
				else
				{
					// Just match it
					$urlPattern .= '('.$part[1].')';
				}
			}
		}

		$routeRecord->locale     = $locale;
		$routeRecord->urlParts   = JsonHelper::encode($urlParts);
		$routeRecord->urlPattern = $urlPattern;
		$routeRecord->template   = $template;
		$routeRecord->save();

		return $routeRecord;
	}

	/**
	 * Deletes a route by its ID.
	 *
	 * @param int $routeId
	 *
	 * @return bool
	 */
	public function deleteRouteById($routeId)
	{
		craft()->db->createCommand()->delete('routes', array('id' => $routeId));
		return true;
	}

	/**
	 * Updates the route order.
	 *
	 * @param array $routeIds An array of each of the route IDs, in their new order.
	 *
	 * @return null
	 */
	public function updateRouteOrder($routeIds)
	{
		foreach ($routeIds as $order => $routeId)
		{
			$data = array('sortOrder' => $order + 1);
			$condition = array('id' => $routeId);
			craft()->db->createCommand()->update('routes', $data, $condition);
		}
	}
}
