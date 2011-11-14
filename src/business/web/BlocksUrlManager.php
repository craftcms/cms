<?php

class BlocksUrlManager extends CUrlManager
{
	private $_path = null;
	private $_pathSegments = null;
	private $_templateMatch = null;
	private $_requestExtension = null;

	public function init()
	{
		// the default CUrlManager processes routes according to Yii's default routing.
		parent::init();

		$this->_path = Blocks::app()->request->getPathInfo();
		$this->_pathSegments = Blocks::app()->request->getPathSegments();
		$this->_requestExtension = Blocks::app()->request->getPathExtension();

		if ($this->_pathSegments !== null && isset($this->_pathSegments[0]) && $this->_pathSegments === 'gii')
			return;

		$this->processTemplateMatching();
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

	public function parseUrl($request)
	{
		if($this->getUrlFormat()===self::PATH_FORMAT)
		{
			$rawPathInfo = $request->getPathInfo();
			$pathInfo = $this->removeUrlSuffix($rawPathInfo, $this->urlSuffix);
			foreach ($this->rules as $pattern => $rule)
			{
				if (is_array($rule))
					$this->rules[$pattern] = $rule = Yii::createComponent($rule);

				$rule = new CUrlRule($rule, $pattern);
				if (($r = $rule->parseUrl($this, $request, $pathInfo, $rawPathInfo)) !== false)
					return isset($_GET[$this->routeVar]) ? $_GET[$this->routeVar] : $r;
			}

			if ($this->useStrictParsing)
				throw new CHttpException(404,Blocks::t('yii','Unable to resolve the request "{route}".', array('{route}' => $pathInfo)));
			else
				return $pathInfo;
		}
		else if(isset($_GET[$this->routeVar]))
			return $_GET[$this->routeVar];
		else if(isset($_POST[$this->routeVar]))
			return $_POST[$this->routeVar];
		else
			return '';
	}

	public function matchTemplate()
	{
		$moduleName = null;
		$templatePath = Blocks::app()->getViewPath();
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
			if (strpos($templatePath, '/modules/') !== false)
			{
				$moduleName = $this->_pathSegments[0];
				$numSlashes = substr_count($tempPath, '/');
				$requestPath = substr($tempPath, strlen($moduleName) + $numSlashes);

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

		if (($fullMatchPath = Blocks::app()->site->matchTemplatePathWithAllowedFileExtensions($templatePath.$requestPath)) !== null)
		{
			$extension = pathinfo($fullMatchPath, PATHINFO_EXTENSION);
			$this->setTemplateMatch($moduleName == null ? $requestPath : $moduleName.'/'.$requestPath, $pathMatchPattern, TemplateMatchType::Template, $extension, $moduleName);
			return true;
		}

		// see if it matches directory/index'
		$path = $requestPath.'index';
		if (($fullMatchPath = (Blocks::app()->site->matchTemplatePathWithAllowedFileExtensions($templatePath.$path)) !== null))
		{
			$extension = pathinfo($fullMatchPath, PATHINFO_EXTENSION);
			$this->setTemplateMatch($moduleName == null ? $path : $moduleName.$path, $pathMatchPattern, TemplateMatchType::Template, $extension, $moduleName);
			return true;
		}

		// no template match.
		return false;
	}

	private function setTemplateMatch($path, $pathMatchPattern, $matchType, $extension, $moduleName = null)
	{
		$templateMatch = new TemplateMatch($path);
		$templateMatch->setMatchRequest($pathMatchPattern);
		$templateMatch->setMatchType($matchType);
		$templateMatch->setModuleName($moduleName);
		$templateMatch->setMatchExtension($extension);
		$this->_templateMatch = $templateMatch;
	}
}
