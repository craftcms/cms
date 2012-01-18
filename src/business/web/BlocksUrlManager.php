<?php

/**
 *
 */
class BlocksUrlManager extends CUrlManager
{
	private $_path = null;
	private $_pathSegments = null;
	private $_templateMatch = null;
	private $_requestExtension = null;
	private $_currentModule = null;

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

		// makes more sense to set in BlocksHttpRequest
		if (Blocks::app()->request->urlFormat == UrlFormat::PathInfo)
			$this->setUrlFormat(self::PATH_FORMAT);
		else
			$this->setUrlFormat(self::GET_FORMAT);

		// save an internal copy of the path, sans-extension
		$this->_path = Blocks::app()->request->path;

		if (($ext = Blocks::app()->request->pathExtension) !== '')
		{
			$this->_requestExtension = $ext;

			// remove the extension from our internal path
			$this->_path = substr($this->_path, 0, -(strlen($ext)+1));
		}

		$this->_pathSegments = array_filter(explode('/', $this->_path));
	}

	/**
	 * @return null
	 */
	public function processTemplateMatching()
	{
		$matchFound = false;

		// we'll never have a db entry match on a control panel request
		if (Blocks::app()->request->mode == RequestMode::Site)
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
	 * @return null
	 */
	public function getCurrentModule()
	{
		if ($this->_currentModule == null)
		{
			if ($this->_pathSegments !== null && isset($this->_pathSegments[0]))
			{
				if (($module = Blocks::app()->getModule($this->_pathSegments[0])) !== null)
					$this->_currentModule = $module;
			}
		}

		return $this->_currentModule;
	}


	/**
	 * Attempts to match a request with an entry in the database.  If one is found, we set the template match property.
	 * @return bool True if a match was found, false otherwise.
	 */
	public function matchEntry()
	{
		$pathMatchPattern = rtrim(Blocks::app()->request->serverName.Blocks::app()->request->scriptUrl.'/'.Blocks::app()->request->pathInfo, '/');

		$entry = Entries::model()->findByAttributes(array(
			'full_uri' => $pathMatchPattern,
		));

		if ($entry !== null)
		{
			$extension = pathinfo($entry->section->template, PATHINFO_EXTENSION);
			$this->setTemplateMatch($entry->section->template, $pathMatchPattern, $extension, TemplateMatchType::Entry);
			return true;
		}

		return false;
	}

	/**
	 * @return bool
	 */
	public function matchRoute()
	{


		/*
		if (Blocks::app()->request->mode == RequestMode::CP)
		{
			$routes = array(
				array('content/edit/{entryId}', 'content/_edit', array(
					'entryId' => RoutePattern::Integer
				)),
				array('assets/edit/{path}', 'assets/_edit', array(
					'path' => RoutePattern::Wild
				)),
				array('users/edit/{userId}', 'users/_edit', array(
					'userId' => RoutePattern::Integer
				)),
			);
		}
		*/

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
		$moduleName = null;
		$templatePath = $this->normalizeTrailingSlash(Blocks::app()->viewPath);
		$pathMatchPattern = rtrim(Blocks::app()->request->serverName.Blocks::app()->request->scriptUrl.'/'.$this->_path, '/');

		$tempPath = $this->_path;
		$testPath = null;

		// if this is a control panel request, let's see if we can match it to a module as well and we're dealing with a module
		if (Blocks::app()->request->mode == RequestMode::CP && $this->_currentModule !== null)
		{
			$moduleName = $this->_currentModule->Id;
			$requestPath = substr($tempPath, strlen($moduleName) + 1);

			if ($requestPath === false)
				$requestPath = '';
		}
		else
			$requestPath = $tempPath;

		// fix the trailing and ending slashes
		if ($requestPath !== '')
			$requestPath = ltrim($requestPath, '\\/');

		$templatePath = $this->normalizeTrailingSlash($templatePath);

		// if there are any folders that have a '_' as the first character of the name, then it's hidden and there is no template match.
		$requestPathSegs = explode('/', $requestPath);
		foreach ($requestPathSegs as $requestPathSeg)
		{
			if (isset($requestPathSeg[0]) && $requestPathSeg[0] == '_')
				return false;
		}

		// first try to match /path/to/folder.{allowedTemplateFileExtensions}
		if ($this->_attemptTemplateMatch($templatePath.$requestPath, $requestPath, $moduleName, $pathMatchPattern))
			return true;

		// now try to match /path/to/folder/index.{allowedTemplateFileExtensions}
		$requestPath = $this->normalizeTrailingSlash($requestPath).'index';
		$templatePath = rtrim($templatePath, '\\/').'/';
		if ($this->_attemptTemplateMatch($templatePath.$requestPath, $requestPath, $moduleName, $pathMatchPattern))
			return true;

		// no template match.
		return false;
	}

	/**
	 * @access private
	 * @param $path
	 * @param $requestPath
	 * @param $moduleName
	 * @param $pathMatchPattern
	 * @return bool
	 */
	private function _attemptTemplateMatch($path, $requestPath, $moduleName, $pathMatchPattern)
	{
		if (($fullMatchPath = Blocks::app()->site->matchTemplatePathWithAllowedFileExtensions($path)) !== null)
		{
			$extension = pathinfo($fullMatchPath, PATHINFO_EXTENSION);

			if ($moduleName !== null)
				$requestPath = $moduleName.$requestPath;

			$this->setTemplateMatch($requestPath, $pathMatchPattern, TemplateMatchType::Template, $extension);
			return true;
		}

		return false;
	}

	/**
	 * @access private
	 * @param $path
	 * @param $pathMatchPattern
	 * @param $matchType
	 * @param $extension
	 */
	private function setTemplateMatch($path, $pathMatchPattern, $matchType, $extension)
	{
		$templateMatch = new TemplateMatch($path);
		$templateMatch->setMatchRequest($pathMatchPattern);
		$templateMatch->setMatchType($matchType);
		$templateMatch->setMatchExtension($extension);
		$this->_templateMatch = $templateMatch;
	}

	/**
	 * Adds a trailing slash to the end of a path if one does not exist
	 * @param $path The path to normalize.
	 * @return string The normalized path.
	 */
	public function normalizeTrailingSlash($path)
	{
		$path = rtrim($path, '\\/').'/';
		return $path;
	}
}
