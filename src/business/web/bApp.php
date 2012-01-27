<?php

/**
 *
 */
class bApp extends CWebApplication
{
	private $_templatePath;
	private $_cpTemplatePath;
	private $_layoutPath;
	private $_isInstalled;
	private $_isSetup;

	public function init()
	{
		// Is this a resource request?
		if ($this->request->mode == bRequestMode::Resource)
		{
			$this->processResourceRequest();
			exit(1);
		}
	}

	/**
	 * Process the request
	 */
	public function processRequest()
	{
		// Config validation
		$this->validateConfig();

		// Process install and setup requests?
		$this->processInstallAndSetupRequest('install', !$this->isInstalled);
		$this->processInstallAndSetupRequest('setup', !$this->isSetup);

		// Otherwise maybe it's an action request?
		$this->processActionRequest();

		// Otherwise run the template controller
		$this->runController('bTemplate');
	}

	/**
	 * Process install and setup requests
	 */
	private function processInstallAndSetupRequest($what, $force)
	{
		// Are they requesting this specifically?
		if ($this->request->mode == bRequestMode::CP && $this->request->getPathSegment(1) === $what)
		{
			$action = $this->request->getPathSegment(2, 'index');
			$this->runController("b{$what}/{$action}");
			$this->end();
		}

		// Should they be?
		else if ($force)
		{
			// Give it to them if accessing the CP
			if ($this->request->mode == bRequestMode::CP)
			{
				$url = bUrlHelper::generateUrl($what);
				$this->request->redirect($url);
			}
			// Otherwise return a 404
			else
				throw new bHttpException(404);
		}
	}

	/**
	 * Process action requests
	 */
	private function processActionRequest()
	{
		if ($this->request->mode == bRequestMode::Action)
		{
			if (!$this->request->getPathSegment(2))
				throw new bHttpException(404);

			$handle = $this->request->getPathSegment(2);
			$controller = $this->request->getPathSegment(3, 'default');
			$action = $this->request->getPathSegment(4, 'index');

			if ($handle != 'app')
			{
				Blocks::import("plugins.{$handle}.controllers.*");
			}
			else
			{
				$controller = 'b'.$controller;
			}

			$this->runController($controller.'/'.$action);
			$this->end();
		}
	}

	/**
	 * Process a resource request
	 */
	private function processResourceRequest()
	{
		// get the path segments, except for the first one which we already know is "resources"
		$segs = array_slice(array_merge($this->request->pathSegments), 1);

		// get the resource handle ("app" or a plugin class)
		$handle = array_shift($segs);

		if ($handle == 'app')
			$rootFolderPath = $this->path->resourcesPath;
		else
			$rootFolderPath = $this->path->pluginsPath.$handle.'/';

		$rootFolderUrl = bUrlHelper::generateUrl('resources/'.$handle).'/';
		$relativeResourcePath = implode('/', $segs);

		$resourceProcessor = new bResourceProcessor($rootFolderPath, $rootFolderUrl, $relativeResourcePath);
		$resourceProcessor->processResourceRequest();
	}

	/**
	 * @return mixed
	 * @throws bException|bHttpException
	 */
	private function validateConfig()
	{
		$messages = array();

		$databaseServerName = $this->getDbConfig('server');
		$databaseAuthName = $this->getDbConfig('user');
		$databaseName = $this->getDbConfig('database');
		$databasePort = $this->getDbConfig('port');
		$databaseTablePrefix = $this->getDbConfig('tablePrefix');
		$databaseCharset = $this->getDbConfig('charset');
		$databaseCollation = $this->getDbConfig('collation');

		if (bStringHelper::isNullOrEmpty($databaseServerName))
			$messages[] = 'The database server name is not set in your db config file.';

		if (bStringHelper::isNullOrEmpty($databaseAuthName))
			$messages[] = 'The database user name is not set in your db config file.';

		if (bStringHelper::isNullOrEmpty($databaseName))
			$messages[] = 'The database name is not set in your db config file.';

		if (bStringHelper::isNullOrEmpty($databasePort))
			$messages[] = 'The database port is not set in your db config file.';

		if (bStringHelper::isNullOrEmpty($databaseTablePrefix))
			$messages[] = 'The database table prefix is not set in your db config file.';

		if (bStringHelper::isNullOrEmpty($databaseCharset))
			$messages[] = 'The database charset is not set in your db config file.';

		if (bStringHelper::isNullOrEmpty($databaseCollation))
			$messages[] = 'The database collation is not set in your db config file.';

		if (!empty($messages))
			throw new bException(implode(PHP_EOL, $messages));

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
			throw new bException(implode(PHP_EOL, $messages));
	}

	/**
	 * @return bool
	 */
	public function getIsInstalled()
	{
		if (!isset($this->_isInstalled))
		{
			// Check to see if the prefix_info table exists.  If not, we assume it's a fresh installation.
			$infoTable = $this->db->schema->getTable('{{info}}');

			$this->_isInstalled = ($infoTable !== null);
		}

		return $this->_isInstalled;
	}

	/**
	 * Updates isInstalled
	 */
	public function setIsInstalled($isInstalled)
	{
		$this->_isInstalled = (bool)$isInstalled;
	}

	/**
	 * @return bool
	 */
	public function getIsSetup()
	{
		if (!isset($this->_isSetup))
		{
			// Check to see if a site exists.  If not, we're still in setup mode
			$totalSites = bSite::model()->count('enabled=:enabled', array(':enabled'=>true));

			$this->_isSetup = ($totalSites > 0);
		}

		return $this->_isSetup;
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
			if (get_class($this->request) == 'bHttpRequest')
			{
				if (BLOCKS_CP_REQUEST !== true)
				{
					$this->_templatePath = $this->path->normalizeDirectorySeparators(realpath($this->path->siteTemplatePath).'/');
				}
				else
				{
					$this->_cpTemplatePath = $this->path->normalizeDirectorySeparators(realpath($this->path->cpTemplatePath).'/');
					$this->_templatePath = $this->_cpTemplatePath;
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
	 * Get a general config item
	 * @param bool|string $key The config item's key to retrieve
	 * @param null        $default
	 * @return mixed The config item's value if set, null if not
	 */
	public function getConfig($key, $default = null)
	{
		if (isset($this->params['blocksConfig'][$key]))
			return $this->params['blocksConfig'][$key];

		return $default;
	}

	/**
	 * Get a config item
	 * @param bool|string $key The config item's key to retrieve
	 * @param null        $default
	 * @return mixed The config item's value if set, null if not
	 */
	public function getDbConfig($key, $default = null)
	{
		if (isset($this->params['dbConfig'][$key]))
			return $this->params['dbConfig'][$key];

		return $default;
	}
}
