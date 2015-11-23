<?php
namespace Craft;

/**
 * Class UrlManager
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://craftcms.com/license Craft License Agreement
 * @see       http://craftcms.com
 * @package   craft.app.etc.web
 * @since     1.0
 */
class UrlManager extends \CUrlManager
{
	// Properties
	// =========================================================================

	/**
	 * @var
	 */
	public $cpRoutes;

	/**
	 * @var
	 */
	public $pathParam;

	/**
	 * List of variables to pass to the routed controller action's $variables argument. Set via setRouteVariables().
	 *
	 * @var array
	 */
	private $_routeVariables;

	/**
	 * @var
	 */
	private $_routeAction;

	/**
	 * @var
	 */
	private $_routeParams;

	/**
	 * @var
	 */
	private $_matchedElement;

	/**
	 * @var
	 */
	private $_matchedElementRoute;

	/**
	 * @var
	 */
	private $_regexTokens;

	/**
	 * @var
	 */
	private $_regexTokenPatterns;

	// Public Methods
	// =========================================================================

	/**
	 * @return null
	 */
	public function init()
	{
		parent::init();

		// Set this to false so extra query string parameters don't get the path treatment
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
	 *
	 * @return null
	 */
	public function setRouteVariables($variables)
	{
		$this->_routeVariables = array_merge($this->_routeVariables, $variables);
	}

	/**
	 * Determines which controller/action to route the request to. Routing candidates include actual template paths,
	 * elements with URIs, and registered URL routes.
	 *
	 * @param HttpRequestService $request
	 *
	 * @throws HttpException Throws a 404 in the event that we can't figure out where to route the request.
	 * @return string The controller/action path.
	 */
	public function parseUrl($request)
	{
		$this->_routeAction = null;
		$this->_routeParams = array(
			'variables' => array()
		);

		// Is there a token in the URL?
		$token = craft()->request->getToken();

		if ($token)
		{
			$tokenRoute = craft()->tokens->getTokenRoute($token);

			if ($tokenRoute)
			{
				$this->_setRoute($tokenRoute);
			}
		}
		else
		{
			$path = $request->getPath();

			// Is this an element request?
			$matchedElementRoute = $this->_getMatchedElementRoute($path);

			if ($matchedElementRoute)
			{
				$this->_setRoute($matchedElementRoute);
			}
			else
			{
				// Does it look like they're trying to access a public template path?
				if ($this->_isPublicTemplatePath())
				{
					// Default to that, then
					$this->_setRoute($path);
				}

				// Finally see if there's a URL route that matches
				$this->_setRoute($this->_getMatchedUrlRoute($path));
			}
		}

		// Did we come up with something?
		if ($this->_routeAction)
		{
			// Merge the route variables into the params
			$this->_routeParams['variables'] = array_merge($this->_routeParams['variables'], $this->_routeVariables);

			// Return the controller action
			return $this->_routeAction;
		}

		// If we couldn't figure out what to do with the request, throw a 404
		throw new HttpException(404);
	}

	/**
	 * Returns the route params, or null if we haven't parsed the URL yet.
	 *
	 * @return array|null
	 */
	public function getRouteParams()
	{
		return $this->_routeParams;
	}

	/**
	 * Returns the element that was matched by the URI.
	 *
	 * @return BaseElementModel|false
	 */
	public function getMatchedElement()
	{
		if (!isset($this->_matchedElement))
		{
			if (craft()->request->isSiteRequest())
			{
				$path = craft()->request->getPath();
				$this->_getMatchedElementRoute($path);
			}
			else
			{
				$this->_matchedElement = false;
			}
		}

		return $this->_matchedElement;
	}

	// Private Methods
	// =========================================================================

	/**
	 * Sets the route.
	 *
	 * @param mixed $route
	 *
	 * @return null
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
	 * @param mixed $route
	 *
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
	 * @param string $path
	 *
	 * @return mixed
	 */
	private function _getMatchedElementRoute($path)
	{
		if (!isset($this->_matchedElementRoute))
		{
			$this->_matchedElement = false;
			$this->_matchedElementRoute = false;

			if (craft()->isInstalled() && craft()->request->isSiteRequest())
			{
				$element = craft()->elements->getElementByUri($path, craft()->language, true);

				if ($element)
				{
					// Do any plugins want a say in this?
					$route = craft()->plugins->callFirst('getElementRoute', array($element), true);

					if (!$route)
					{
						// Give the element type a chance
						$elementType = craft()->elements->getElementType($element->getElementType());
						$route = $elementType->routeRequestForMatchedElement($element);
					}

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
	 * @param string $path
	 *
	 * @return mixed
	 */
	private function _getMatchedUrlRoute($path)
	{
		if (craft()->request->isCpRequest())
		{
			// Merge in any edition-specific routes
			for ($i = 1; $i <= craft()->getEdition(); $i++)
			{
				if (isset($this->cpRoutes['editionRoutes'][$i]))
				{
					$this->cpRoutes = array_merge($this->cpRoutes, $this->cpRoutes['editionRoutes'][$i]);
				}
			}

			unset($this->cpRoutes['editionRoutes']);

			if (($route = $this->_matchUrlRoutes($path, $this->cpRoutes)) !== false)
			{
				return $route;
			}

			$pluginHook = 'registerCpRoutes';
		}
		else
		{
			// Check the user-defined routes
			$configFileRoutes = craft()->routes->getConfigFileRoutes();

			if (($route = $this->_matchUrlRoutes($path, $configFileRoutes)) !== false)
			{
				return $route;
			}

			$dbRoutes = craft()->routes->getDbRoutes();

			if (($route = $this->_matchUrlRoutes($path, $dbRoutes)) !== false)
			{
				return $route;
			}

			$pluginHook = 'registerSiteRoutes';
		}

		// Maybe a plugin has a registered route that matches?
		$allPluginRoutes = craft()->plugins->call($pluginHook);

		foreach ($allPluginRoutes as $pluginRoutes)
		{
			if (($route = $this->_matchUrlRoutes($path, $pluginRoutes)) !== false)
			{
				return $route;
			}
		}

		return false;
	}

	/**
	 * Attempts to match a path with a set of given URL routes.
	 *
	 * @param string $path
	 * @param array  $routes
	 *
	 * @return mixed
	 */
	private function _matchUrlRoutes($path, $routes)
	{
		foreach ($routes as $pattern => $route)
		{
			// Escape any unescaped forward slashes. Dumb ol' PHP is having trouble with this one when you use single
			// quotes and don't escape the backslashes.
			$regexPattern = preg_replace("/(?<!\\\\)\\//", '\/', $pattern);

			// Parse tokens
			$regexPattern = $this->_parseRegexTokens($regexPattern);

			// Does it match?
			if (preg_match('/^'.$regexPattern.'$/u', $path, $match))
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
	 * Parses any tokens in a given regex pattern.
	 *
	 * @param string $pattern
	 *
	 * @return string
	 */
	private function _parseRegexTokens($pattern)
	{
		if (!isset($this->_regexTokens))
		{
			$this->_regexTokens = array(
				'{handle}',
				'{slug}',
			);

			$slugChars = array('.', '_', '-');
			$slugWordSeparator = craft()->config->get('slugWordSeparator');

			if ($slugWordSeparator != '/' && !in_array($slugWordSeparator, $slugChars))
			{
				$slugChars[] = $slugWordSeparator;
			}

			$this->_regexTokenPatterns = array(
				'(?:[a-zA-Z][a-zA-Z0-9_]*)',
				'(?:[\p{L}\p{N}'.preg_quote(implode($slugChars), '/').']+)',
			);
		}

		return str_replace($this->_regexTokens, $this->_regexTokenPatterns, $pattern);
	}

	/**
	 * Returns whether the current path is "public" (no segments that start with the privateTemplateTrigger).
	 *
	 * @return bool
	 */
	private function _isPublicTemplatePath()
	{
		if (!craft()->request->isAjaxRequest())
		{
			$trigger = craft()->request->isCpRequest() ? '_' : craft()->config->get('privateTemplateTrigger');
			$length = strlen($trigger);

			foreach (craft()->request->getSegments() as $requestPathSeg)
			{
				if (strncmp($requestPathSeg, $trigger, $length) === 0)
				{
					return false;
				}
			}
		}

		return true;
	}
}
