<?php
namespace Blocks;

/**
 *
 */
class App extends \CWebApplication
{
	private $_templatePath;
	private $_cpTemplatePath;
	private $_layoutPath;
	private $_isInstalled;
	private $_isSetup;

	public function init()
	{
		$this->_importClassMap();

		// Is this a resource request?
		if ($this->request->mode == RequestMode::Resource)
		{
			$this->processResourceRequest();
			exit(1);
		}
	}

	private function _importClassMap()
	{
		$dirs = array(
			'business',
			'business/db',
			'business/email',
			'business/enums',
			'business/exceptions',
			'business/install',
			'business/logging',
			'business/services',
			'business/updates',
			'business/utils',
			'business/web',
			'business/web/filters',
			'business/web/templatewidgets',
			'business/web/webservices',
			'commands',
			'controllers',
			'migrations',
			'models',
			'models/forms',
			'tags',
			'tags/assets',
			'tags/content',
			'tags/cp',
			'tags/primitive',
			'tags/security',
			'tags/users',
			'widgets',
		);

		foreach ($dirs as $dir)
		{
			$dir = BLOCKS_APP_PATH.$dir.'/';
			if (($contents = @glob($dir."*.php")) !== false)
			{
				foreach ($contents as $file)
				{
					\Yii::$classMap[__NAMESPACE__.'\\'.pathinfo($file, PATHINFO_FILENAME)] = substr($file, strlen(BLOCKS_BASE_PATH) - 7);
				}
			}
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
		$this->runController('Template');
	}

	/**
	 * Process install and setup requests
	 *
	 * @param $what
	 * @param $force
	 */
	private function processInstallAndSetupRequest($what, $force)
	{
		// Are they requesting this specifically?
		if ($this->request->mode == RequestMode::CP && $this->request->getPathSegment(1) === $what)
		{
			$action = $this->request->getPathSegment(2, 'index');
			$this->runController("{$what}/{$action}");
			$this->end();
		}

		// Should they be?
		else if ($force)
		{
			// Give it to them if accessing the CP
			if ($this->request->mode == RequestMode::CP)
			{
				$url = UrlHelper::generateUrl($what);
				$this->request->redirect($url);
			}
			// Otherwise return a 404
			else
				throw new HttpException(404);
		}
	}

	/**
	 * Process action requests
	 */
	private function processActionRequest()
	{
		if ($this->request->mode == RequestMode::Action)
		{
			if (!$this->request->getPathSegment(2))
				throw new HttpException(404);

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

		$rootFolderUrl = UrlHelper::generateUrl('resources/'.$handle).'/';
		$relativeResourcePath = implode('/', $segs);

		$resourceProcessor = new ResourceProcessor($rootFolderPath, $rootFolderUrl, $relativeResourcePath);
		$resourceProcessor->processResourceRequest();
	}

	/**
	 * @return mixed
	 * @throws Exception|HttpException
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

		if (StringHelper::isNullOrEmpty($databaseServerName))
			$messages[] = 'The database server name is not set in your db config file.';

		if (StringHelper::isNullOrEmpty($databaseAuthName))
			$messages[] = 'The database user name is not set in your db config file.';

		if (StringHelper::isNullOrEmpty($databaseName))
			$messages[] = 'The database name is not set in your db config file.';

		if (StringHelper::isNullOrEmpty($databasePort))
			$messages[] = 'The database port is not set in your db config file.';

		if (StringHelper::isNullOrEmpty($databaseTablePrefix))
			$messages[] = 'The database table prefix is not set in your db config file.';

		if (StringHelper::isNullOrEmpty($databaseCharset))
			$messages[] = 'The database charset is not set in your db config file.';

		if (StringHelper::isNullOrEmpty($databaseCollation))
			$messages[] = 'The database collation is not set in your db config file.';

		if (!empty($messages))
			throw new Exception(implode(PHP_EOL, $messages));

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
			throw new Exception(implode(PHP_EOL, $messages));
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
	 * @param $isInstalled
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
			$totalSites = Site::model()->exists('enabled=:enabled', array(':enabled'=>true));

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
			if (strpos(get_class($this->request), 'HttpRequest') !== false)
			{
				$this->_templatePath = $this->path->templatePath;
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
