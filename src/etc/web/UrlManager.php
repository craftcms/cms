<?php
namespace Craft;

/**
 *
 */
class UrlManager extends \CUrlManager
{
	public $cpRoutes;
	public $pathParam;

	/**
	 * @var array List of variables to pass to the routed controller action's $variables argument. Set via setRouteVariables().
	 * @access private
	 */
	private $_routeVariables;

	private $_routeAction;
	private $_routeParams;
	private $_matchedElement;
	private $_matchedElementRoute;

	/**
	 *
	 */
	public function init()
	{
		parent::init();

		// set this to false so extra query string parameters don't get the path treatment
		$this->appendParams = false;

		// makes more sense to set in HttpRequest
		if (craft()->config->usePathInfo())
		{
			$this->setUrlFormat(static::PATH_FORMAT);
		}
		else
		{
			$this->setUrlFormat(static::GET_FORMAT);
		}

		$this->_routeVariables = array();
	}

	/**
	 * Sets variables to be passed to the routed controllers action's $variables argument.
	 *
	 * @param array $variables
	 */
	public function setRouteVariables($variables)
	{
		$this->_routeVariables = array_merge($this->_routeVariables, $variables);
	}

	/**
	 * Determines which controller/action to route the request to.
	 * Routing candidates include actual template paths, elements with URIs, and registered URL routes.
	 *
	 * @param HttpRequestService $request
	 * @return string The controller/action path.
	 * @throws HttpException Throws a 404 in the event that we can't figure out where to route the request.
	 */
	public function parseUrl(HttpRequestService $request)
	{
		$this->_routeAction = null;
		$this->_routeParams = array(
			'variables' => array()
		);

		$path = $request->getPath();

		// Does it look like they're trying to access a public template path?
		if ($this->_isPublicTemplatePath())
		{
			// Default to that, then
			$this->_setRoute($path);
		}

		// If this is a site request, see if there's an element assigned to this URI
		if (Craft::isInstalled() && $request->isSiteRequest())
		{
			$this->_setRoute($this->_getMatchedElementRoute($path));
		}

		// Finally see if there's a URL route that matches
		$this->_setRoute($this->_getMatchedUrlRoute($path));

		// Did we come up with something?
		if ($this->_routeAction)
		{
			// Merge the route variables into the params
			$this->_routeParams['variables'] = array_merge($this->_routeParams['variables'], $this->_routeVariables);

			// Save the params in $_GET so they can get mapped to the controller action arguments
			$_GET = array_merge($_GET, $this->_routeParams);

			// Return the controller action
			return $this->_routeAction;
		}

		// If we couldn't figure out what to do with the request, throw a 404
		throw new HttpException(404);
	}

	/**
	 * Returns the element that was matched by the URI.
	 *
	 * @return BaseElementModel|null
	 */
	public function getMatchedElement()
	{
		if (!isset($this->_matchedElement))
		{
			$path = craft()->request->getPath();
			$this->_getMatchedElementRoute($path);
		}

		return $this->_matchedElement;
	}

	/**
	 * Sets the route.
	 *
	 * @access private
	 * @param mixed $route
	 */
	private function _setRoute($route)
	{
		if ($route !== false)
		{
			// Normalize it
			$route = $this->_normalizeRoute($route);

			// Set the new action
			$this->_routeAction = $route['action'];

			// Merge in any params
			if (!empty($route['params']))
			{
				$this->_routeParams = array_merge($this->_routeParams, $route['params']);
			}
		}
	}

	/**
	 * Normalizes a route.
	 *
	 * @access private
	 * @param mixed $route
	 * @return array
	 */
	private function _normalizeRoute($route)
	{
		if ($route !== false)
		{
			// Strings are template paths
			if (is_string($route))
			{
				$route = array(
					'params' => array(
						'template' => $route
					)
				);
			}

			if (!isset($route['action']))
			{
				$route['action'] = 'templates/render';
			}
		}

		return $route;
	}

	/**
	 * Attempts to match a path with an element in the database.
	 *
	 * @access private
	 * @param string $path
	 * @return mixed
	 */
	private function _getMatchedElementRoute($path)
	{
		if (!isset($this->_matchedElementRoute))
		{
			$this->_matchedElement = false;
			$this->_matchedElementRoute = false;

			$query = craft()->db->createCommand()
				->select('elements.id, elements.type')
				->from('elements elements')
				->join('elements_i18n elements_i18n', 'elements_i18n.elementId = elements.id');

			$conditions = array('and', 'elements_i18n.uri = :path', 'elements.enabled = 1', 'elements.archived = 0');
			$params = array(':path' => $path);

			$localeIds = array_unique(array_merge(
				array(craft()->language),
				craft()->i18n->getSiteLocaleIds()
			));

			if (count($localeIds) == 1)
			{
				$conditions[] = 'elements_i18n.locale = :locale';
				$params[':locale'] = $localeIds[0];
			}
			else
			{
				$quotedLocales = array();
				$localeOrder = array();

				foreach ($localeIds as $localeId)
				{
					$quotedLocale = craft()->db->quoteValue($localeId);
					$quotedLocales[] = $quotedLocale;
					$localeOrder[] = "(elements_i18n.locale = {$quotedLocale}) DESC";
				}

				$conditions[] = "elements_i18n.locale IN (".implode(', ', $quotedLocales).')';
				$query->order($localeOrder);
			}

			$query->where($conditions, $params);

			$row = $query->queryRow();

			if ($row)
			{
				$elementCriteria = craft()->elements->getCriteria($row['type']);
				$elementCriteria->id = $row['id'];

				$element = $elementCriteria->first();

				if ($element)
				{
					$elementType = $elementCriteria->getElementType();
					$route = $elementType->routeRequestForMatchedElement($element);

					if ($route)
					{
						$this->_matchedElement = $element;
						$this->_matchedElementRoute = $route;
					}
				}
			}
		}

		return $this->_matchedElementRoute;
	}

	/**
	 * Attempts to match a path with the registered URL routes.
	 *
	 * @access private
	 * @param string $path
	 * @return mixed
	 */
	private function _getMatchedUrlRoute($path)
	{
		if (craft()->request->isCpRequest())
		{
			// Merge in any package-specific routes for packages that are actually installed
			if (isset($this->cpRoutes['pkgRoutes']))
			{
				// Merge in the package routes
				foreach ($this->cpRoutes['pkgRoutes'] as $packageName => $packageRoutes)
				{
					if (Craft::hasPackage($packageName))
					{
						$this->cpRoutes = array_merge($this->cpRoutes, $packageRoutes);
					}
				}

				unset($this->cpRoutes['pkgRoutes']);
			}

			if (($route = $this->_matchUrlRoutes($path, $this->cpRoutes)) !== false)
			{
				return $route;
			}

			// As a last ditch to match routes, check to see if any plugins have routes registered that will match.
			$pluginCpRoutes = craft()->plugins->call('registerCpRoutes');

			foreach ($pluginCpRoutes as $pluginRoutes)
			{
				if (($route = $this->_matchUrlRoutes($path, $pluginRoutes)) !== false)
				{
					return $route;
				}
			}
		}
		else
		{
			// Check the user-defined routes
			$siteRoutes = craft()->routes->getAllRoutes();

			if (($route = $this->_matchUrlRoutes($path, $siteRoutes)) !== false)
			{
				return $route;
			}
		}

		return false;
	}

	/**
	 * Attempts to match a path with a set of given URL routes.
	 *
	 * @access private
	 * @param string $path
	 * @param array $routes
	 * @return mixed
	 */
	private function _matchUrlRoutes($path, $routes)
	{
		foreach ($routes as $pattern => $route)
		{
			// Escape any unescaped forward slashes
			// Dumb ol' PHP is having trouble with this one when you use single quotes and don't escape the backslashes.
			$regexPattern = preg_replace("/(?<!\\\\)\\//", '\/', $pattern);

			// Parse {handle} tokens
			$regexPattern = str_replace('{handle}', '[a-zA-Z][a-zA-Z0-9_]*', $regexPattern);

			// Does it match?
			if (preg_match('/^'.$regexPattern.'$/', $path, $match))
			{
				// Normalize the route
				$route = $this->_normalizeRoute($route);

				// Save the matched components as route variables
				$routeVariables = array(
					'matches' => $match
				);

				// Add any named subpatterns too
				foreach ($match as $key => $value)
				{
					// Is this a valid handle?
					if (preg_match('/^[a-zA-Z][a-zA-Z0-9_]*$/', $key))
					{
						$routeVariables[$key] = $value;
					}
				}

				$this->setRouteVariables($routeVariables);

				return $route;
			}
		}

		return false;
	}

	/**
	 * Returns whether the current path is "public" (no segments that start with underscores).
	 *
	 * @access private
	 * @return bool
	 */
	private function _isPublicTemplatePath()
	{
		if (!craft()->request->isAjaxRequest())
		{
			foreach (craft()->request->getSegments() as $requestPathSeg)
			{
				if (isset($requestPathSeg[0]) && $requestPathSeg[0] == '_')
				{
					return false;
				}
			}
		}

		return true;
	}
}
