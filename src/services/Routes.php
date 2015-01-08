<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2013 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\services;

use craft\app\Craft;
use craft\app\errors\Exception;
use craft\app\helpers\JsonHelper;
use craft\app\helpers\StringHelper;
use yii\base\Component;
use craft\app\helpers\IOHelper;
use craft\app\records\Route          as RouteRecord;
use craft\app\web\Application;

/**
 * Class Routes service.
 *
 * An instance of the Routes service is globally accessible in Craft via [[Application::routes `Craft::$app->routes`]].
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class Routes extends Component
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
		$path = Craft::$app->path->getConfigPath().'routes.php';

		if (IOHelper::fileExists($path))
		{
			$routes = require_once($path);

			if (is_array($routes))
			{
				// Check for any locale-specific routes
				$locale = Craft::$app->language;

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

		return [];
	}

	/**
	 * Returns the routes defined in the CP.
	 *
	 * @return array
	 */
	public function getDbRoutes()
	{
		$results = Craft::$app->db->createCommand()
			->select('urlPattern, template')
			->from('routes')
			->where(['or', 'locale is null', 'locale = :locale'], [':locale' => Craft::$app->language])
			->order('sortOrder')
			->queryAll();

		if ($results)
		{
			$routes = [];

			foreach ($results as $result)
			{
				$routes[$result['urlPattern']] = $result['template'];
			}

			return $routes;
		}

		return [];
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
				throw new Exception(Craft::t('No route exists with the ID “{id}”.', ['id' => $routeId]));
			}
		}
		else
		{
			$routeRecord = new RouteRecord();

			// Get the next biggest sort order
			$maxSortOrder = Craft::$app->db->createCommand()
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
		Craft::$app->db->createCommand()->delete('routes', ['id' => $routeId]);
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
			$data = ['sortOrder' => $order + 1];
			$condition = ['id' => $routeId];

			Craft::$app->db->createCommand()->update('routes', $data, $condition);
		}
	}
}
