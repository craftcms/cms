<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2015 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\web;

use Craft;
use craft\app\base\ElementInterface;
use craft\app\helpers\ArrayHelper;
use craft\app\helpers\UrlHelper;
use yii\web\UrlRuleInterface;

/**
 * @inheritdoc
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class UrlManager extends \yii\web\UrlManager
{
	// Properties
	// =========================================================================

	/**
	 * @var array Params that should be included in the
	 */
	private $_routeParams = [];

	/**
	 * @var
	 */
	private $_matchedElement;

	/**
	 * @var
	 */
	private $_matchedElementRoute;

	// Public Methods
	// =========================================================================

	/**
	 * Constructor.
	 *
	 * @param array $config
	 */
	public function __construct($config = [])
	{
		$config['showScriptName'] = !Craft::$app->getConfig()->omitScriptNameInUrls();
		$config['rules'] = $this->_getRules();

		parent::__construct($config);
	}

	/**
	 * Sets params to be passed to the routed controller action.
	 *
	 * @param array $params
	 */
	public function setRouteParams($params)
	{
		$this->_routeParams = ArrayHelper::merge($this->_routeParams, $params);
	}

	/**
	 * @inheritdoc
	 */
	public function parseRequest($request)
	{
		// Just in case...
		if ($request->getIsConsoleRequest())
		{
			return false;
		}

		if (($route = $this->_getRequestRoute($request)) !== false)
		{
			// Merge in any additional route params
			if ($this->_routeParams)
			{
				if (isset($route[1]))
				{
					$route[1] = ArrayHelper::merge($route[1], $this->_routeParams);
				}
				else
				{
					$route[1] = $this->_routeParams;
				}
			}

			return $route;
		}

		return false;
	}

	/**
	 * @inheritdoc
	 */
	public function createUrl($params)
	{
		return $this->createAbsoluteUrl($params);
	}

	/**
	 * @inheritdoc
	 */
	public function createAbsoluteUrl($params, $scheme = null)
	{
		$params = (array) $params;
		unset($params[$this->routeParam]);

		$route = trim($params[0], '/');
		unset($params[0]);

		return UrlHelper::getActionUrl($route, $params, $scheme);
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
	 * @return ElementInterface|false
	 */
	public function getMatchedElement()
	{
		if (!isset($this->_matchedElement))
		{
			$request = Craft::$app->getRequest();

			if ($request->getIsSiteRequest())
			{
				$path = $request->getPathInfo();
				$this->_getMatchedElementRoute($path);
			}
			else
			{
				$this->_matchedElement = false;
			}
		}

		return $this->_matchedElement;
	}

	// Protected Methods
	// =========================================================================

	/**
	 * @inheritdoc
	 */
	protected function buildRules($rules)
	{
		// Add support for patterns in keys even if the value is an array
		$i = 0;
		$verbs = 'GET|HEAD|POST|PUT|PATCH|DELETE|OPTIONS';

		foreach ($rules as $key => $rule)
		{
			if (is_string($key) && is_array($rule))
			{
				// Code adapted from \yii\web\UrlManager::init()
				if (
					!isset($rule['verb']) &&
					preg_match("/^((?:($verbs),)*($verbs))\\s+(.*)$/", $key, $matches)
				)
				{
					$rule['verb'] = explode(',', $matches[1]);

					if (!isset($rule['mode']) && !in_array('GET', $rule['verb']))
					{
						$rule['mode'] = UrlRule::PARSING_ONLY;
					}

					$key = $matches[4];
				}

				$rule['pattern'] = $key;
				array_splice($rules, $i, 1, [$rule]);
			}

			$i++;
		}

		return parent::buildRules($rules);
	}

	// Private Methods
	// =========================================================================

	/**
	 * Returns the rules that should be used for the current request.
	 *
	 * @return array|null
	 */
	private function _getRules()
	{
		$request = Craft::$app->getRequest();

		if ($request->getIsConsoleRequest())
		{
			return null;
		}

		// Load the config file rules
		if ($request->getIsCpRequest())
		{
			$baseCpRoutesPath = Craft::$app->getPath()->getAppPath().'/config/cproutes';
			$rules = require($baseCpRoutesPath.'/common.php');

			if (Craft::$app->getEdition() >= Craft::Client)
			{
				$rules = array_merge($rules, require($baseCpRoutesPath.'/client.php'));

				if (Craft::$app->getEdition() == Craft::Pro)
				{
					$rules = array_merge($rules, require($baseCpRoutesPath.'/pro.php'));
				}
			}

			$pluginHook = 'registerCpRoutes';
		}
		else
		{
			$routesService = Craft::$app->getRoutes();

			$rules = array_merge(
				$routesService->getConfigFileRoutes(),
				$routesService->getDbRoutes()
			);

			$pluginHook = 'registerSiteRoutes';
		}

		// Load the plugin-supplied rules
		$allPluginRules = Craft::$app->getPlugins()->call($pluginHook);

		foreach ($allPluginRules as $pluginRules)
		{
			$rules = array_merge($rules, $pluginRules);
		}

		return array_filter($rules);
	}

	/**
	 * Returns the request's route.
	 *
	 * @param Request $request
	 * @return mixed
	 */
	private function _getRequestRoute(Request $request)
	{
		// Is there a token in the URL?
		if (($token = $request->getToken()) !== null)
		{
			return Craft::$app->getTokens()->getTokenRoute($token);
		}

		$path = $request->getPathInfo();

		// Is this an element request?
		if (($route = $this->_getMatchedElementRoute($path)) !== false)
		{
			return $route;
		}

		// Do we have a URL route that matches?
		if (($route = $this->_getMatchedUrlRoute($request)) !== false)
		{
			return $route;
		}

		// Does it look like they're trying to access a public template path?
		if ($this->_isPublicTemplatePath())
		{
			return ['templates/render', ['template' => $path]];
		}

		return false;
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

			if (Craft::$app->isInstalled() && Craft::$app->getRequest()->getIsSiteRequest())
			{
				$element = Craft::$app->getElements()->getElementByUri($path, Craft::$app->language, true);

				if ($element)
				{
					// Do any plugins want a say in this?
					$route = Craft::$app->getPlugins()->callFirst('getElementRoute', [$element], true);

					if (!$route)
					{
						// Give the element type a chance
						$route = $element::getElementRoute($element);
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
	 * @param Request $request
	 *
	 * @return mixed
	 */
	private function _getMatchedUrlRoute($request)
	{
		// Code adapted from \yii\web\UrlManager::parseRequest()
		/* @var $rule UrlRule */
		foreach ($this->rules as $rule)
		{
			if (($route = $rule->parseRequest($this, $request)) !== false)
			{
				if ($rule->params)
				{
					$this->setRouteParams($rule->params);
				}
				return $route;
			}
		}

		return false;
	}

	/**
	 * Returns whether the current path is "public" (no segments that start with the privateTemplateTrigger).
	 *
	 * @return bool
	 */
	private function _isPublicTemplatePath()
	{
		$trigger = Craft::$app->getConfig()->get('privateTemplateTrigger');
		$length = strlen($trigger);

		foreach (Craft::$app->getRequest()->getSegments() as $requestPathSeg)
		{
			if (strncmp($requestPathSeg, $trigger, $length) === 0)
			{
				return false;
			}
		}

		return true;
	}
}
