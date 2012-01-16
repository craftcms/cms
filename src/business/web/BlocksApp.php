<?php

/**
 *
 */
class BlocksApp extends CWebApplication
{
	private $_mode;
	private $_templatePath;
	private $_cpTemplatePath;
	private $_layoutPath;
	private $_dbInstalled;

	/**
	 * Before we get too far along in the app initialization,
	 * check to see if we need to redirect to a different URL,
	 * or if it's a resource request, return the resource.
	 */
	public function init()
	{
		// URL format correction
		if (!$this->request->path)
		{
			if ($this->request->urlFormat == UrlFormat::PathInfo)
			{
				$pathVar = $this->config('pathVar');
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

		// Resources
		if ($this->mode == AppMode::Resource)
		{
			// get the path segments, except for the first one which we already know is "resources"
			$segs = array_slice(array_merge($this->request->pathSegments), 1);

			// get the resource handle ("app" or a plugin class)
			$handle = array_shift($segs);

			if ($handle == 'app')
				$rootFolderPath = $this->path->resourcesPath;
			else
				$rootFolderPath = $this->path->pluginsPath.$handle.'/';

			$rootFolderUrl = UrlHelper::generateUrl('resources/'.$handle).'/';
			$relativeResourcePath = implode('/', $segs);

			$resourceProcessor = new ResourceProcessor($rootFolderPath, $rootFolderUrl, $relativeResourcePath);
			$resourceProcessor->processResourceRequest();
		}

		parent::init();
	}

	/**
	 * @return string The app mode (Action, Resource, CP, or Site)
	 */
	public function getMode()
	{
		if (!isset($this->_mode))
		{
			if (isset($this->request->pathSegments[0]) && $this->request->pathSegments[0] == $this->config('actionTriggerWord'))
				$this->_mode = AppMode::Action;

			else if (isset($this->request->pathSegments[0]) && $this->request->pathSegments[0] == $this->config('resourceTriggerWord'))
				$this->_mode = AppMode::Resource;

			else if (BLOCKS_CP_REQUEST === true)
				$this->_mode = AppMode::CP;

			else
				$this->_mode = AppMode::Site;
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
		$path = $this->request->path;

		if (strpos($path, 'install') !== false)
			return;

		if (strpos($path, 'error') !== false)
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
				$pathSegments = $this->request->pathSegments;
				if (!$pathSegments || $pathSegments[0] !== 'install')
					$this->request->redirect($this->urlManager->baseUrl.'/install');
			}
		}
	}

	/**
	 * @return bool
	 */
	public function isDbInstalled()
	{
		if (!isset($this->_dbInstalled))
		{
			// Check to see if the prefix_info table exists.  If not, we assume it's a fresh installation.
			$infoTable = $this->db->schema->getTable('{{info}}');

			$this->_dbInstalled = ($infoTable !== null);
		}

		return $this->_dbInstalled;
	}

	/**
	 * Process the request
	 */
	public function processRequest()
	{
		if ($this->mode == AppMode::Action)
		{
			if (!isset($this->request->pathSegments[2]))
				throw new BlocksHttpException(404);

			$handle = $this->request->pathSegments[1];
			$controller = $this->request->pathSegments[2];

			if (isset($this->request->pathSegments[3]))
				$action = $this->request->pathSegments[3];
			else
				$action = 'index';

			$this->runController($controller.'/'.$action);
		}
		else
		{
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
		if (!isset($this->_templatePath))
		{
			if (get_class($this->request) == 'BlocksHttpRequest')
			{
				// Site request OR action request, but coming in through index.php
				if ($this->mode == AppMode::Site || ($this->mode == AppMode::Action && BLOCKS_CP_REQUEST !== true))
				{
					$this->_templatePath = $this->path->normalizeDirectorySeparators(realpath($this->path->siteTemplatePath).'/');
				}
				else
				{
					// CP request OR action request, but coming in through admin.php
					if ($this->mode == AppMode::CP || ($this->mode == AppMode::Action && BLOCKS_CP_REQUEST === true))
					{
						$pathSegments = $this->request->pathSegments;
						if ($pathSegments && ($module = $this->urlManager->currentModule) !== null)
						{
							$this->_templatePath = rtrim($module->viewPath, '\\/').'/';
						}
						else
						{
							$this->_cpTemplatePath = $this->path->normalizeDirectorySeparators(realpath($this->path->cpTemplatePath).'/');
							$this->_templatePath = $this->_cpTemplatePath;
						}
					}
				}
			}
			else
			{
				// in the case of an exception, our custom classes are not loaded.
				$this->_templatePath = BLOCKS_BASE_PATH.'templates/';
			}
		}

		return $this->_templatePath;
	}

	/**
	 * @return string
	 */
	public function getLayoutPath()
	{
		if (!isset($this->_layoutPath))
			$this->_layoutPath = $this->viewPath.'layouts';

		return $this->_layoutPath;
	}

	/**
	 * @return string
	 */
	public function getSystemViewPath()
	{
		if (!isset($this->_cpTemplatePath))
			$this->_cpTemplatePath = BLOCKS_BASE_PATH.'app/templates/';

		return $this->_cpTemplatePath;
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
