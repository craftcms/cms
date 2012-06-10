<?php
namespace Blocks;

/**
 *
 */
class UrlManager extends \CUrlManager
{
	private $_templateVariables = array();

	public $routePatterns;
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
	 * Attempts to match a request with an entry in the database.  If one is found, we set the template match property.
	 * @return bool True if a match was found, false otherwise.
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
			// Check the Blocks predefined routes.
			foreach ($this->cpRoutes as $route)
			{
				if (($path = $this->_matchRouteInternal($route)) !== false)
					return $path;
			}

			// As a last ditch to match routes, check to see if any plugins have routes registered that will match.
			$pluginCpRoutes = blx()->plugins->callHook('registerCpRoutes');
			foreach ($pluginCpRoutes as $pluginRoutes)
			{
				foreach ($pluginRoutes as $route)
				{
					if (($path = $this->_matchRouteInternal($route)) !== false)
						return $path;
				}
			}
		}

		return false;
	}

	/**
	 * @param $route
	 * @return bool
	 */
	private function _matchRouteInternal($route)
	{
		// Escape special regex characters from the pattern
		$pattern = str_replace(array('.','/'), array('\.','\/'), $route[0]);

		// Mix in the predefined subpatterns
		$pattern = str_replace(array_keys($this->routePatterns), $this->routePatterns, $pattern);

		// Does it match?
		if (preg_match("/^{$pattern}$/", blx()->request->getPath(), $match))
		{
			// Set any capture variables
			if (!empty($route[2]))
			{
				foreach ($route[2] as $i => $variableName)
				{
					if (isset($match[$i + 1]))
						$this->_templateVariables[$variableName] = $match[$i + 1];
					else
						break;
				}
			}

			return $route[1];
		}

		return false;
	}

	/**
	 * Attempts to match a request to a file on the file system.
	 * Will return false for any directory that has a "_" as the first character.
	 * Will attempt to match "path/to/folder/file.{allowedFileExtensions}" first, "path/to/folder/file/index.{allowedFileExtensions}" second.
	 * Sets the template match property if a match is found.
	 * @return bool True is a match is found, false otherwise.
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
