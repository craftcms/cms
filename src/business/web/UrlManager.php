<?php
namespace Blocks;

/**
 *
 */
class UrlManager extends \CUrlManager
{
	private $_templateVariables = array();

	public $cpRoutes;
	public $routeVar;

	const RouteVar = 'p';

	/**
	 *
	 */
	function __construct()
	{
		$this->routeVar = self::RouteVar;
	}

	/**
	 *
	 */
	public function init()
	{
		parent::init();

		// set this to false so extra query string parameters don't get the path treatment
		$this->appendParams = false;

		// makes more sense to set in HttpRequest
		if (blx()->request->getUrlFormat() == UrlFormat::PathInfo)
			$this->setUrlFormat(self::PATH_FORMAT);
		else
			$this->setUrlFormat(self::GET_FORMAT);
	}

	/**
	 * @return null
	 */
	public function processTemplateMatching()
	{
		// we'll never have a db entry match on a control panel request
		if (!BLOCKS_CP_REQUEST)
		{
			if (blx()->getIsInstalled())
				if (($path = $this->matchEntry()) !== false)
					return $path;
		}

		if (($path = $this->matchRoute()) !== false)
		{
			return $path;
		}
		else
		{
			return $this->matchTemplatePath();
		}
	}

	/**
	 * @return array Any variables that should be passed into the matched template
	 */
	public function getTemplateVariables()
	{
		return $this->_templateVariables;
	}

	/**
	 * Attempts to match a request with an entry in the database.
	 * @return bool The URI if a match was found, false otherwise.
	 */
	public function matchEntry()
	{
		$entry = Entry::model()->findByAttributes(array(
			'uri' => blx()->request->getPath(),
		));

		if ($entry !== null)
		{
			$this->_templateVariables['entry'] = $entry;
			return $entry->uri;
		}

		return false;
	}

	/**
	 * @return bool
	 */
	public function matchRoute()
	{
		if (BLOCKS_CP_REQUEST)
		{
			// Check the @@@productDisplay@@@ predefined routes.
			foreach ($this->cpRoutes as $route)
			{
				if ($this->_matchRouteInternal($route[0]))
					return $route[1];
			}

			// As a last ditch to match routes, check to see if any plugins have routes registered that will match.
			$pluginCpRoutes = blx()->plugins->callHook('registerCpRoutes');
			foreach ($pluginCpRoutes as $pluginRoutes)
			{
				foreach ($pluginRoutes as $route)
				{
					if ($this->_matchRouteInternal($route[0]))
						return $route[1];
				}
			}
		}

		return false;
	}

	/**
	 * @param $route
	 * @return bool
	 */
	private function _matchRouteInternal($urlPattern)
	{
		// Does it match?
		if (preg_match('/^'.$urlPattern.'$/', blx()->request->getPath(), $match))
		{
			// Set any capture variables
			foreach ($match as $key => $value)
			{
				if (!is_numeric($key))
				{
					$this->_templateVariables[$key] = $value;
				}
			}

			return true;
		}

		return false;
	}

	/**
	 * @return bool
	 */
	public function matchTemplatePath()
	{
		// Make sure they're not trying to access a private template
		if (!blx()->request->getIsAjaxRequest())
		{
			foreach (blx()->request->getPathSegments() as $requestPathSeg)
			{
				if (isset($requestPathSeg[0]) && $requestPathSeg[0] == '_')
					return false;
			}
		}

		return blx()->request->getPath();
	}
}
