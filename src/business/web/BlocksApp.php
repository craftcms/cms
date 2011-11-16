<?php

class BlocksApp extends CWebApplication
{
	private $_requestTemplatePath;
	private $_cpTemplatePath;
	private $_layoutPath;
	private $_dbInstalled = null;

	//Blocks::app()->attachEventHandler('onBeginRequest', array($this, 'blar'));

	public function init()
	{
		// run the resource processor if necessary.
		if ($this->request->getRequestType() == 'GET')
		{
			$segs = $this->request->getPathSegments();
			if (array_shift($segs) == 'resources')
				if ($pluginHandle = array_shift($segs))
					if ($resourcePath = implode('/', $segs))
						new ResourceProcessor($resourcePath, $pluginHandle);
		}

		parent::init();
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

		if ($this->urlManager->getTemplateMatch() !== null || $this->request->getParam('c', null) !== null)
			$this->catchAllRequest = array('blocks/index');

		if($this->hasEventHandler('onBeginRequest'))
			$this->onBeginRequest(new CEvent($this));

		$this->processRequest();

		if($this->hasEventHandler('onEndRequest'))
			$this->onEndRequest(new CEvent($this));
	}

	private function validateConfig()
	{
		$pathInfo = $this->request->getPathInfo();

		if (strpos($pathInfo, '/install') !== false)
			return;

		if (strpos($pathInfo, '/error') !== false)
			return;

		$messages = array();

		$databaseServerName = $this->config->getDatabaseServerName();
		$databaseAuthName = $this->config->getDatabaseAuthName();
		$databaseAuthPassword = $this->config->getDatabaseAuthPassword();
		$databaseName = $this->config->getDatabaseName();
		$databaseType = $this->config->getDatabaseType();
		$databasePort = $this->config->getDatabasePort();
		$databaseTablePrefix = $this->config->getDatabaseTablePrefix();
		$databaseCharset = $this->config->getDatabaseCharset();
		$databaseCollation = $this->config->getDatabaseCollation();

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
			if (!in_array($databaseType, $this->config->getDatabaseSupportedTypes()))
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
			if ($this->request->getCMSRequestType() == RequestType::Site)
					throw new BlocksHttpException(404, 'Page not found.');
			else
			{
				$pathInfo = $this->request->getPathSegments();
				if (!$pathInfo || $pathInfo[0] !== 'install')
					$this->request->redirect(Blocks::app()->urlManager->getBaseUrl().'/install');
			}
		}
	}

	public function isDbInstalled()
	{
		if ($this->_dbInstalled == null)
		{
			// Check to see if the prefix_info table exists.  If not, we assume it's a fresh installation.
			$infoTable = $this->db->schema->getTable($this->config->getDatabaseTablePrefix().'_info');

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
			if (get_class($this->request) == 'BlocksHttpRequest')
			{
				$requestType = $this->request->getCMSRequestType();
				if ($requestType == RequestType::Site)
				{
					$templatePath = Blocks::app()->path->normalizeDirectorySeparators(realpath($this->path->getSiteTemplatePath()).'/');
				}
				else
				{
					$pathInfo = $this->request->getPathSegments();
					if ($pathInfo && ($module = $this->getModule($pathInfo[0])) !== null)
					{
						$templatePath = $module->getViewPath();
					}
					else
					{
						$this->_cpTemplatePath = Blocks::app()->path->normalizeDirectorySeparators(realpath($this->path->getCPTemplatePath()).'/');
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
			return $this->_layoutPath = $this->getViewPath().'layouts';
	}

	public function getSystemViewPath()
	{
		if($this->_cpTemplatePath !== null)
			return $this->_cpTemplatePath;
		else
			return Blocks::app()->path->normalizeDirectorySeparators(realpath($this->path->getCPTemplatePath()).'/');
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
			$route = $this->urlManager->parseUrl($this->getRequest());

		if ($route !== '')
			$this->runController($route);
		else
			throw new BlocksHttpException(404, 'Could not find the requested page.');
	}
}
