<?php

/**
 *
 */
class BlocksApp extends CWebApplication
{
	private $_templatePath;
	private $_cpTemplatePath;
	private $_layoutPath;
	private $_isDbInstalled;

	/**
	 * Process the request
	 */
	public function processRequest()
	{
		// Resources
		if ($this->request->mode == RequestMode::Resource)
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

		// validate the config
		$this->validateConfig();

		// is Blocks actually installed?
		if (!$this->isDbInstalled)
		{
			if ($this->request->mode == RequestMode::CP)
			{
				if ($this->request->getPathSegment(1) !== 'install')
				{
					// redirect to /install
					$url = UrlHelper::generateUrl('install');
					$this->request->redirect($url);
				}

				$this->runController('install/index');
			}
			else
			{
				// return 404
				throw new BlocksHttpException(404);
			}
		}

		// Action request?
		else if ($this->request->mode == RequestMode::Action)
		{
			if (!$this->request->getPathSegment(2))
				throw new BlocksHttpException(404);

			$handle = $this->request->getPathSegment(2);
			$controller = $this->request->getPathSegment(3, 'default');
			$action = $this->request->getPathSegment(4, 'index');

			if ($handle != 'app')
			{
				Blocks::import("base.plugins.{$handle}.controllers.*");
			}

			$this->runController($controller.'/'.$action);
		}

		// template request
		else
		{
			$this->runController('template/index');
		}
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
	}

	/**
	 * @return bool
	 */
	public function getIsDbInstalled()
	{
		if (!isset($this->_isDbInstalled))
		{
			// Check to see if the prefix_info table exists.  If not, we assume it's a fresh installation.
			$infoTable = $this->db->schema->getTable('{{info}}');

			$this->_isDbInstalled = ($infoTable !== null);
		}

		return $this->_isDbInstalled;
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
				if ($this->request->mode == RequestMode::Site || ($this->request->mode == RequestMode::Action && BLOCKS_CP_REQUEST !== true))
				{
					$this->_templatePath = $this->path->normalizeDirectorySeparators(realpath($this->path->siteTemplatePath).'/');
				}
				else
				{
					// CP request OR action request, but coming in through admin.php
					if ($this->request->mode == RequestMode::CP || ($this->request->mode == RequestMode::Action && BLOCKS_CP_REQUEST === true))
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
	public function getConfig($key, $default = null)
	{
		if (isset($this->params['config'][$key]))
			return $this->params['config'][$key];

		return $default;
	}
}
