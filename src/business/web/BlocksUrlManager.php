<?php

class BlocksUrlManager extends CUrlManager
{
	private $_path = null;
	private $_pathSegments = null;
	private $_templateMatch = null;
	private $_requestExtension = null;
	private $_currentModule = null;

	public function init()
	{
		parent::init();

		$this->_path = Blocks::app()->request->getPathInfo();
		$this->_pathSegments = Blocks::app()->request->getPathSegments();
		$this->_requestExtension = Blocks::app()->request->getPathExtension();
	}

	public function processTemplateMatching()
	{
		$matchFound = false;

		// we'll never have a db page match on a control panel request
		if (Blocks::app()->request->getCMSRequestType() == RequestType::Site)
		{
			if (Blocks::app()->isDbInstalled())
				if ($this->matchPage())
					$matchFound = true;
		}

		if (!$matchFound)
			if (!$this->matchTemplate())
				$this->matchRoute();
					//throw new BlocksHttpException('404', 'Page not found.');
	}

	public function getTemplateMatch()
	{
		return $this->_templateMatch;
	}

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

	public function matchPage()
	{
		$pathMatchPattern = rtrim(Blocks::app()->request->serverName.Blocks::app()->request->scriptUrl.'/'.Blocks::app()->request->getPathInfo(), '/');

		$page = ContentPages::model()->findByAttributes(array(
			'full_uri' => $pathMatchPattern,
		));

		if ($page !== null)
		{
			$extension = pathinfo($page->section->template, PATHINFO_EXTENSION);
			$this->setTemplateMatch($page->section->template, $pathMatchPattern, $extension, TemplateMatchType::Page);
			return true;
		}

		return false;
	}

	public function matchRoute()
	{
		$test = $this->parseUrl(Blocks::app()->request);
		return false;
	}

	public function matchTemplate()
	{
		$moduleName = null;
		$templatePath = $this->normalizeTrailingSlash(Blocks::app()->getViewPath());
		$pathMatchPattern = rtrim(Blocks::app()->request->serverName.Blocks::app()->request->scriptUrl.'/'.Blocks::app()->request->getPathInfo(), '/');
		$tempPath = $this->_path;
		$testPath = null;

		if ($this->_requestExtension !== null)
		{
			$pathMatchPattern = rtrim($pathMatchPattern, '.'.$this->_requestExtension);
			$tempPath = rtrim($tempPath, '.'.$this->_requestExtension);
		}

		if (Blocks::app()->request->getCmsRequestType() == RequestType::ControlPanel)
		{
			// we're dealing with a module
			if ($this->_currentModule !== null)
			{
				$moduleName = $this->_currentModule->getId();
				$requestPath = substr($tempPath, strlen($moduleName) + 1);

				if ($requestPath === false)
					$requestPath = '';
			}
			else
			{
				$requestPath = $tempPath;
			}
		}
		else
			$requestPath = $tempPath;

		if ($requestPath != '')
		{
			$requestPath = ltrim($requestPath, '\\/');
			$templatePath = $this->normalizeTrailingSlash($templatePath);
		}
		else
		{
			$templatePath = rtrim($templatePath, '\\/');
		}

		if (($fullMatchPath = Blocks::app()->site->matchTemplatePathWithAllowedFileExtensions($templatePath.$requestPath)) !== null)
		{
			$extension = pathinfo($fullMatchPath, PATHINFO_EXTENSION);
			$this->setTemplateMatch($moduleName == null ? $requestPath : $moduleName.'/'.$requestPath, $pathMatchPattern, TemplateMatchType::Template, $extension);
			return true;
		}

		// see if it matches directory/index'
		$requestPath = $this->normalizeTrailingSlash($requestPath).'index';
		$templatePath = rtrim($templatePath, '\\/').'/';
		if (($fullMatchPath = Blocks::app()->site->matchTemplatePathWithAllowedFileExtensions($templatePath.$requestPath)) !== null)
		{
			$extension = pathinfo($fullMatchPath, PATHINFO_EXTENSION);
			$this->setTemplateMatch($moduleName == null ? $requestPath : $moduleName.$requestPath, $pathMatchPattern, TemplateMatchType::Template, $extension);
			return true;
		}

		// no template match.
		return false;
	}

	private function setTemplateMatch($path, $pathMatchPattern, $matchType, $extension)
	{
		$templateMatch = new TemplateMatch($path);
		$templateMatch->setMatchRequest($pathMatchPattern);
		$templateMatch->setMatchType($matchType);
		$templateMatch->setMatchExtension($extension);
		$this->_templateMatch = $templateMatch;
	}

	public function normalizeTrailingSlash($path)
	{
		$lastChar = substr($path, -1);
		if ($lastChar !== '\\' && $lastChar !== '/')
			$path .= '/';

		return $path;
	}
}
