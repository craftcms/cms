<?php

/**
 *
 */
class bUrlManager extends CUrlManager
{
	private $_templateMatch = null;
	private $_templateTags = array();

	public $routeVar;

	/**
	 */
	function __construct()
	{
		$this->routeVar = Blocks::app()->getConfig('pathVar');
	}

	/**
	 */
	public function init()
	{
		parent::init();

		// set this to false so extra query string parameters don't get the path treatment
		$this->appendParams = false;

		// makes more sense to set in bHttpRequest
		if (Blocks::app()->request->urlFormat == bUrlFormat::PathInfo)
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
		if (Blocks::app()->request->mode == bRequestMode::Site)
		{
			if (Blocks::app()->isInstalled)
				if ($this->matchEntry())
					$matchFound = true;
		}

		if (!$matchFound)
			if (!$this->matchRoute())
				$this->matchTemplate();
	}

	/**
	 * @return null
	 */
	public function getTemplateMatch()
	{
		return $this->_templateMatch;
	}

	/**
	 * Attempts to match a request with an entry in the database.  If one is found, we set the template match property.
	 * @return bool True if a match was found, false otherwise.
	 */
	public function matchEntry()
	{
		$entry = bEntry::model()->findByAttributes(array(
			'full_uri' => Blocks::app()->request->path,
		));

		if ($entry !== null)
		{
			$this->setTemplateMatch($entry->section->template, bTemplateMatchType::Entry);
			$this->_templateTags['entry'] = new bContentEntryTag($entry);
			return true;
		}

		return false;
	}

	/**
	 * @return bool
	 */
	public function matchRoute()
	{
		$routePatterns = array(
			'{wild}'    => '.+',
			'{segment}' => '[^\/]*',
			'{integer}' => '\d+',
			'{word}'    => '[A-Za-z]\w*',
		);

		if (Blocks::app()->request->mode == bRequestMode::CP)
		{
			$routes = array(
				array('update/({segment})', 'update', array('handle'))
			);

			foreach ($routes as $route)
			{
				// Escape special regex characters from the pattern
				$pattern = str_replace(array('.','/'), array('\.','\/'), $routePatterns, $route[0]);

				// Mix in the predefined subpatterns
				$pattern = str_replace(array_keys($routePatterns), $routePatterns, $pattern);

				// Does it match?
				if (preg_match("/{$pattern}/", Blocks::app()->request->path, $match))
				{
					$templatePath = bTemplateHelper::resolveTemplatePath(trim($route[1], '/'));
					if ($templatePath !== false)
						$this->setTemplateMatch($templatePath, bTemplateMatchType::Route);

					// Set any capture tags
					if (!empty($route[2]))
					{
						foreach ($route[2] as $i => $tagName)
						{
							if (isset($match[$i+1]))
								$this->_templateTags[$tagName] = $match[$i+1];
							else
								break;
						}
					}

					return true;
				}
			}
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
	public function matchTemplate()
	{
		// Make sure they're not trying to access a private template
		foreach (Blocks::app()->request->pathSegments as $requestPathSeg)
		{
			if (isset($requestPathSeg[0]) && $requestPathSeg[0] == '_')
				return false;
		}

		// Does a request path match a template?
		$templatePath = bTemplateHelper::resolveTemplatePath(Blocks::app()->request->path);
		if ($templatePath !== false)
		{
			$this->setTemplateMatch($templatePath, bTemplateMatchType::Template);
			return true;
		}

		return false;
	}

	/**
	 * @access private
	 * @param $path
	 * @param $matchType
	 */
	private function setTemplateMatch($path, $matchType)
	{
		$templateMatch = new bTemplateMatch($path);
		$templateMatch->setMatchType($matchType);
		$this->_templateMatch = $templateMatch;
	}
}
