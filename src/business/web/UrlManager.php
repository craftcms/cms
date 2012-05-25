<?php
namespace Blocks;

/**
 *
 */
class UrlManager extends \CUrlManager
{
	private $_templateMatch;
	private $_templateVariables = array();

	public $routePatterns;
	public $cpRoutes;
	public $routeVar;
	public $caseSensitive = false;

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
		if (b()->request->getUrlFormat() == UrlFormat::PathInfo)
			$this->setUrlFormat(self::PATH_FORMAT);
		else
			$this->setUrlFormat(self::GET_FORMAT);
	}

	/**
	 * @return null
	 */
	public function processTemplateMatching()
	{
		$matchFound = false;

		// we'll never have a db entry match on a control panel request
		if (!BLOCKS_CP_REQUEST)
		{
			if (b()->getIsInstalled())
				if ($this->matchEntry())
					$matchFound = true;
		}

		if (!$matchFound)
			if (!$this->matchRoute())
				$this->matchTemplatePath();
	}

	/**
	 * @return null
	 */
	public function getTemplateMatch()
	{
		return $this->_templateMatch;
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
			'uri' => b()->request->getPath(),
		));

		if ($entry !== null)
		{
			$this->_setTemplateMatch($entry->section->template, TemplateMatchType::Entry, $entry->uri);
			$this->_templateVariables['entry'] = $entry;
			return true;
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
				if ($this->_matchRouteInternal($route))
					return true;
			}

			// As a last ditch to match routes, check to see if any plugins have routes registered that will match.
			$pluginCpRoutes = b()->plugins->callHook('registerCpRoutes');
			foreach ($pluginCpRoutes as $pluginRoutes)
			{
				foreach ($pluginRoutes as $route)
				{
					if ($this->_matchRouteInternal($route))
						return true;
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
		if (preg_match("/^{$pattern}$/", b()->request->getPath(), $match))
		{
			$templateMatch = TemplateHelper::resolveTemplatePath(trim($route[1], '/'));
			if ($templateMatch !== false)
				$this->_setTemplateMatch($templateMatch['templatePath'], TemplateMatchType::Route, $templateMatch['fileSystemPath']);

			// Set any capture variables
			if (!empty($route[2]))
			{
				foreach ($route[2] as $i => $variableName)
				{
					if (isset($match[$i+1]))
						$this->_templateVariables[$variableName] = $match[$i + 1];
					else
						break;
				}
			}

			return true;
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
		if (!b()->request->getIsAjaxRequest())
		{
			foreach (b()->request->getPathSegments() as $requestPathSeg)
			{
				if (isset($requestPathSeg[0]) && $requestPathSeg[0] == '_')
					return false;
			}
		}

		// Does a request path match a template?
		$templateMatch = TemplateHelper::resolveTemplatePath(b()->request->getPath());
		if ($templateMatch !== false)
		{
			$this->_setTemplateMatch($templateMatch['templatePath'], TemplateMatchType::Template, $templateMatch['fileSystemPath']);
			return true;
		}

		return false;
	}

	/**
	 * @access private
	 * @param $templatePath
	 * @param $matchType
	 * @param $fileSystemPath
	 */
	private function _setTemplateMatch($templatePath, $matchType, $fileSystemPath)
	{
		$templateMatch = new TemplateMatch($templatePath, $fileSystemPath);
		$templateMatch->setMatchType($matchType);
		$this->_templateMatch = $templateMatch;
	}
}
