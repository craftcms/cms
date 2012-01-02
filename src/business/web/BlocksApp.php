<?php

class BlocksApp extends CWebApplication
{
	private $_mode;
	private $_requestTemplatePath;
	private $_cpTemplatePath;
	private $_layoutPath;
	private $_dbInstalled = null;

	//Blocks::app()->attachEventHandler('onBeginRequest', array($this, 'blar'));

	public function init()
	{
		// Is this a resource request?
		if ($this->mode == AppMode::Resource)
		{
			$segs = array_slice($this->request->pathSegments, 1);

			$handle = array_shift($segs);

			if ($handle == 'app')
				$rootFolderPath = $this->path->resourcesPath;
			else
				$rootFolderPath = $this->path->pluginsPath.$handle.'/';

			$rootFolderUrl = $this->urlManager->baseUrl.'/'.'resources/'.$handle.'/';
			$relativeResourcePath = implode('/', $segs);

			$resourceProcessor = new ResourceProcessor($rootFolderPath, $rootFolderUrl, $relativeResourcePath);
			$resourceProcessor->processResourceRequest();
		}

		parent::init();
	}

	public function getMode()
	{
		if (!isset($this->_mode))
		{
			// Controller action?
			if (isset($this->request->pathSegments[0]) && ($this->request->pathSegments[0] == Blocks::app()->config('actionTriggerWord')))
			{
				$this->_mode = AppMode::Action;
			}
			// Resource?
			else if (isset($this->request->pathSegments[0]) && ($this->request->pathSegments[0] == Blocks::app()->config('resourceTriggerWord')))
			{
				$this->_mode = AppMode::Resource;
			}
			// CP?
			else if (defined('BLOCKS_CP_REQUEST') && BLOCKS_CP_REQUEST === true)
			{
				$this->_mode = AppMode::CP;
			}
			// Then it's a site
			else
			{
				$this->_mode = AppMode::Site;
			}
		}

		return $this->_mode;
	}

	public function blar()
	{
		if ('127.0.0.1' === $_SERVER['REMOTE_ADDR'])
		{
			//Blocks::app()->catchAllRequest = null;
		}
	}

	public function run()
	{
		$this->validateConfig();

		if ($this->mode !== AppMode::Action)
			$this->urlManager->processTemplateMatching();

		if ($this->urlManager->templateMatch !== null || ($this->mode == AppMode::Action))
			$this->catchAllRequest = array('blocks/index');

		if($this->hasEventHandler('onBeginRequest'))
			$this->onBeginRequest(new CEvent($this));

		$this->processRequest();

		if($this->hasEventHandler('onEndRequest'))
			$this->onEndRequest(new CEvent($this));
	}

	private function validateConfig()
	{
		$pathInfo = $this->request->pathInfo;

		if (strpos($pathInfo, 'install') !== false)
			return;

		if (strpos($pathInfo, 'error') !== false)
			return;

		$messages = array();

		$databaseServerName = $this->config->databaseServerName;
		$databaseAuthName = $this->config->databaseAuthName;
		$databaseAuthPassword = $this->config->databaseAuthPassword;
		$databaseName = $this->config->databaseName;
		$databaseType = $this->config->databaseType;
		$databasePort = $this->config->databasePort;
		$databaseTablePrefix = $this->config->databaseTablePrefix;
		$databaseCharset = $this->config->databaseCharset;
		$databaseCollation = $this->config->databaseCollation;

		if (StringHelper::IsNullOrEmpty($databaseServerName))
			$messages[] = 'The database server name is not set in your db config file.';

		if (StringHelper::IsNullOrEmpty($databaseAuthName))
			$messages[] = 'The database user name is not set in your db config file.';

		if (StringHelper::IsNullOrEmpty($databaseAuthPassword))
			$messages[] = 'The database password is not set in your db config file.';

		if (StringHelper::IsNullOrEmpty($databaseName))
			$messages[] = 'The database name is not set in your db config file.';

		if (StringHelper::IsNullOrEmpty($databasePort))
			$messages[] = 'The database port is not set in your db config file.';

		if (StringHelper::IsNullOrEmpty($databaseTablePrefix))
			$messages[] = 'The database table prefix is not set in your db config file.';

		if (StringHelper::IsNullOrEmpty($databaseCharset))
			$messages[] = 'The database charset is not set in your db config file.';

		if (StringHelper::IsNullOrEmpty($databaseCollation))
			$messages[] = 'The database collation is not set in your db config file.';

		if (StringHelper::IsNullOrEmpty($databaseType))
			$messages[] = 'The database type is not set in your db config file.';
		else
		{
			if (!in_array($databaseType, $this->config->databaseSupportedTypes))
				$messages[] = 'Blocks does not support the database type you have set in your db config file.';
		}

		if (!empty($messages))
			throw new BlocksException(implode(PHP_EOL, $messages));

		try
		{
			$connection = $this->db;
			if (!$connection)
				$messages[] = 'There is a problem connecting to the database with the credentials supplied in your db config file.';
		}
		catch(Exception $e)
		{
			$messages[] = 'There is a problem connecting to the database with the credentials supplied in your db config file.';
		}

		if (!empty($messages))
			throw new BlocksException(implode(PHP_EOL, $messages));

		if (!$this->isDbInstalled())
		{
			if ($this->mode == AppMode::Site)
				throw new BlocksHttpException(404);
			else
			{
				$pathInfo = $this->request->pathSegments;
				if (!$pathInfo || $pathInfo[0] !== 'install')
					$this->request->redirect(Blocks::app()->urlManager->baseUrl.'/install');
			}
		}
	}

	public function isDbInstalled()
	{
		if ($this->_dbInstalled == null)
		{
			// Check to see if the prefix_info table exists.  If not, we assume it's a fresh installation.
			$infoTable = $this->db->schema->getTable('{{info}}');

			$this->_dbInstalled = $infoTable === null ? false : true;
		}

		return $this->_dbInstalled;
	}

	// we can't use setViewPath() because our view path depends on the request type, which is initialized after web application.
	// so we override getViewPath();
	public function getViewPath()
	{
		if ($this->_requestTemplatePath !== null)
			return $this->_requestTemplatePath;
		else
		{
			if ($this->mode == AppMode::Action)
				return null;

			if (get_class($this->request) == 'BlocksHttpRequest')
			{
				$requestType = $this->mode;
				if ($requestType == AppMode::Site)
				{
					$templatePath = Blocks::app()->path->normalizeDirectorySeparators(realpath($this->path->siteTemplatePath).'/');
				}
				else
				{
					$pathInfo = $this->request->pathSegments;
					if ($pathInfo && ($module = $this->urlManager->currentModule) !== null)
					{
						$templatePath = rtrim($module->viewPath, '\\/').'/';
					}
					else
					{
						$this->_cpTemplatePath = Blocks::app()->path->normalizeDirectorySeparators(realpath($this->path->cpTemplatePath).'/');
						$templatePath = $this->_cpTemplatePath;
					}
				}
			}
			else
			{
				// in the case of an exception, our custom classes are not loaded.
				$templatePath = BLOCKS_BASE_PATH.'templates/';
			}

			$this->_requestTemplatePath = $templatePath;
			return $this->_requestTemplatePath;
		}
	}

	public function getLayoutPath()
	{
		if ($this->_layoutPath !==null)
			return $this->_layoutPath;
		else
			return $this->_layoutPath = $this->viewPath.'layouts';
	}

	public function getSystemViewPath()
	{
		if($this->_cpTemplatePath !== null)
			return $this->_cpTemplatePath;
		else
		{
			$this->_cpTemplatePath = BLOCKS_BASE_PATH.'app/templates/';
			return $this->_cpTemplatePath;
		}
	}

	/**
	 * Get a config item
	 * @param bool|string $key The config item's key to retrieve
	 * @return mixed The config item's value if set, null if not
	 */
	public function config($key = false)
	{
		return (is_string($key) && isset($this->params['config'][$key])) ? $this->params['config'][$key] : null;
	}

	/**
	 * Processes the current request.
	 * It first resolves the request into controller and action,
	 * and then creates the controller to perform the action.
	 */
	public function processRequest()
	{
		if (is_array($this->catchAllRequest) && isset($this->catchAllRequest[0]))
		{
			$route = $this->catchAllRequest[0];
			foreach (array_splice($this->catchAllRequest, 1) as $name => $value)
				$_GET[$name] = $value;
		}
		else
			$route = $this->urlManager->parseUrl($this->request);

		if ($route !== '')
		{
			// don't let a gii request on the front-end go through.
			if (strpos($route, 'gii') !== false)
				if ($this->mode !== AppMode::CP)
					$this->request->redirect('/');

			$this->runController($route);
		}
		else
			// can't find any template or route to match.
			throw new BlocksHttpException(404);
	}
}
