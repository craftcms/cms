<?php

/**
 *
 */
class BlocksApp extends CWebApplication
{
	private $_mode;
	private $_requestTemplatePath;
	private $_cpTemplatePath;
	private $_layoutPath;
	private $_dbInstalled = null;

	/**
	 *
	 */
	public function init()
	{
		// URL format correction
		if (!$this->request->path)
		{
			if ($this->request->urlFormat == UrlFormat::PathInfo)
			{
				$pathVar = Blocks::app()->config('pathVar');
				$path = $this->request->getParam($pathVar);

				if ($path)
				{
					$params = isset($_GET) ? $_GET : array();
					unset($params[$pathVar]);
					$url = UrlHelper::generateUrl($path, $params);
					$this->request->redirect($url);
				}
			}
			else
			{
				if (isset($_SERVER['PATH_INFO']) && $_SERVER['PATH_INFO'])
				{
					$params = isset($_GET) ? $_GET : array();
					$url = UrlHelper::generateUrl($_SERVER['PATH_INFO'], $params);
					$this->request->redirect($url);
				}
			}
		}

		parent::init();
	}

	/**
	 * @return mixed
	 */
	public function getMode()
	{
		if (!isset($this->_mode))
		{
			// Controller action with path_info format
			if (isset($this->request->pathSegments[0]) && ($this->request->pathSegments[0] == Blocks::app()->config('actionTriggerWord')))
			{
				$this->_mode = AppMode::Action;
			}
			// Controller action with non-path_info format
			else if (($queryStrPath = Blocks::app()->request->getParam(Blocks::app()->config('pathVar'), null)) !== null)
			{
				$pathSegs = explode('/', $queryStrPath);
				if (isset($pathSegs[0]) && $pathSegs[0] == Blocks::app()->config('actionTriggerWord'))
				{
					$this->_mode = AppMode::Action;
				}
			}

			if (!isset($this->_mode))
			{
				// Resource request with path_info format
				if (isset($this->request->pathSegments[0]) && ($this->request->pathSegments[0] == Blocks::app()->config('resourceTriggerWord')))
				{
					$this->_mode = AppMode::Resource;
				}
				// Resource request with non-path_info format
				else if (($queryStrPath = Blocks::app()->request->getParam(Blocks::app()->config('pathVar'), null)) !== null)
				{
					$pathSegs = explode('/', $queryStrPath);
					if (isset($pathSegs[0]) && $pathSegs[0] == Blocks::app()->config('resourceTriggerWord'))
					{
						$this->_mode = AppMode::Resource;
					}
				}
			}

			if (!isset($this->_mode))
			{
				// CP request
				if (defined('BLOCKS_CP_REQUEST') && BLOCKS_CP_REQUEST === true)
				{
					$this->_mode = AppMode::CP;
				}
				// Then it's a site
				else
				{
					$this->_mode = AppMode::Site;
				}
			}
		}

		return $this->_mode;
	}

	/**
	 */
	public function run()
	{
		$this->validateConfig();

		parent::run();
	}

	/**
	 * @return mixed
	 * @throws BlocksException|BlocksHttpException
	 */
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

	/**
	 * @return bool
	 */
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

	/**
	 * Process the request
	 */
	public function processRequest()
	{
		switch ($this->mode)
		{
			case AppMode::Resource:

				// get the path segments, except for the first one which we already know is "resources"
				$segs = array_slice(array_merge($this->request->pathSegments), 1);

				// get the resource handle ("app" or a plugin class)
				$handle = array_shift($segs);

				if ($handle == 'app')
				{
					$rootFolderPath = $this->path->resourcesPath;
				}
				else
				{
					$rootFolderPath = $this->path->pluginsPath.$handle.'/';
				}

				$rootFolderUrl = UrlHelper::generateUrl('resources/'.$handle).'/';
				$relativeResourcePath = implode('/', $segs);

				$resourceProcessor = new ResourceProcessor($rootFolderPath, $rootFolderUrl, $relativeResourcePath);
				$resourceProcessor->processResourceRequest();

				break;

			case AppMode::Action:

				if (!isset($this->request->pathSegments[2]))
					throw new BlocksHttpException(404);

				$handle = $this->request->pathSegments[1];
				$controller = $this->request->pathSegments[2];

				if (isset($this->request->pathSegments[2]))
					$action = $this->request->pathSegments[3];
				else
					$action = 'index';

				$this->runController($controller.'/'.$action);

				break;

			default:

				$this->runController('template/index');
		}
	}

	/**
	 * Gets the viewPath for the incoming request.
	 * We can't use setViewPath() because our view path depends on the request type, which is initialized after web application, so we override getViewPath();
	 * @return mixed
	 */
	public function getViewPath()
	{
		if ($this->_requestTemplatePath !== null)
			return $this->_requestTemplatePath;
		else
		{
			if (get_class($this->request) == 'BlocksHttpRequest')
			{
				$requestType = $this->mode;
				// Site request OR action request, but coming in through index.php
				if ($requestType == AppMode::Site || ($requestType == AppMode::Action && !defined('BLOCKS_CP_REQUEST')))
				{
					$templatePath = Blocks::app()->path->normalizeDirectorySeparators(realpath($this->path->siteTemplatePath).'/');
				}
				else
				{
					// CP request OR action request, but coming in through admin.php
					if ($requestType == AppMode::CP || ($requestType == AppMode::Action && defined('BLOCKS_CP_REQUEST') && BLOCKS_CP_REQUEST === true))
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

	/**
	 * @return string
	 */
	public function getLayoutPath()
	{
		if ($this->_layoutPath !==null)
			return $this->_layoutPath;
		else
			return $this->_layoutPath = $this->viewPath.'layouts';
	}

	/**
	 * @return string
	 */
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
}
