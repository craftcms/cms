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

	/**
	 * Init
	 */
	public function init()
	{
		// If this is a resource request, we should respond with the resource ASAP
		$this->_processResourceRequest();

		// We would normally use the 'preload' config option for logging, but because of PHP namespace hackery, we'll manually load it here.
		self::import('business.logging.WebLogRoute');
		b()->getComponent('log');

		parent::init();
	}

	/**
	 * Prepares Yii's autoloader with a map pointing all of Blocks' class names to their file paths
	 */
	private function _importClasses()
	{
		$aliases = array(
			'blocktypes.*',
			'business.db.*',
			'business.email.*',
			'business.enums.*',
			'business.exceptions.*',
			'business.install.*',
			'business.logging.*',
			'business.updates.*',
			'business.utils.*',
			'business.validators.*',
			'business.web.*',
			'business.web.filters.*',
			'business.web.templating.*',
			'business.web.templating.tags.*',
			'business.web.templating.templatewidgets.*',
			'business.web.templating.variables.*',
			'business.webservices.*',
			'commands.*',
			'controllers.*',
			'migrations.*',
			'models.*',
			'models.forms.*',
			'services.*',
			'widgets.*',
		);

		foreach ($aliases as $alias)
		{
			self::import($alias);
		}
	}

	/**
	 * @static
	 * @param      $alias
	 * @param bool $forceInclude
	 */
	public static function import($alias, $forceInclude = false)
	{
		$path = BLOCKS_APP_PATH.str_replace('.', '/', $alias);

		$directory = (substr($path, -2) == '/*');
		if ($directory)
		{
			$path = substr($path, 0, -1);

			if (($files = @glob($path."*.php")) !== false)
			{
				foreach ($files as $file)
				{
					self::_importFile(realpath($file));
				}
			}
		}
		else
		{
			$file = $path.'.php';
			self::_importFile($file);

			if ($forceInclude)
				require_once $file;
		}
	}

	/**
	 * @static
	 * @param $file
	 */
	private static function _importFile($file)
	{
		$class = __NAMESPACE__.'\\'.pathinfo($file, PATHINFO_FILENAME);
		\Yii::$classMap[$class] = $file;
	}

	/**
	 * Process the request
	 */
	public function processRequest()
	{
		// Import the majority of Blocks' classes
		$this->_importClasses();

		// Config validation
		$this->_validateConfig();

		// Process install and setup requests?
		$this->_processInstallAndSetupRequest('install', !$this->isInstalled);
		$this->_processInstallAndSetupRequest('setup', !$this->isSetup);

		// Otherwise maybe it's an action request?
		$this->_processActionRequest();

		// Otherwise run the template controller
		$this->runController('Template');
	}

	/**
	 * Process install and setup requests
	 *
	 * @param $what
	 * @param $force
	 */
	private function _processInstallAndSetupRequest($what, $force)
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
	private function _processActionRequest()
	{
		if ($this->request->mode == RequestMode::Action)
		{
			$plugin = $this->request->actionPlugin;
			if ($plugin !== false)
			{
				if ($plugin === null)
					throw new HttpException(404);

				Blocks::import("plugins.{$plugin}.controllers.*");
			}

			$this->runController($this->request->actionController.'/'.$this->request->actionAction);
			$this->end();
		}
	}

	/**
	 * Process a resource request
	 */
	private function _processResourceRequest()
	{
		// Import the bare minimum to determine if what type of request this is
		self::import('business.Component');
		self::import('business.Plugin');
		self::import('business.enums.UrlFormat');
		self::import('business.enums.RequestMode');
		self::import('business.utils.HtmlHelper');
		self::import('business.utils.UrlHelper');
		self::import('business.web.HttpRequest');
		self::import('business.web.UrlManager');
		self::import('services.ConfigService');

		// in case of an error
		self::import('business.exceptions.HttpException');
		self::import('business.db.DbCommand');
		self::import('business.db.DbConnection');
		self::import('business.db.MysqlSchema');
		self::import('business.web.ErrorHandler');

		if ($this->request->mode == RequestMode::Resource)
		{
			// Import the bare minimum to process a resource
			self::import('business.exceptions.HttpException');
			self::import('business.utils.File');
			self::import('business.web.ErrorHandler');
			self::import('business.web.ResourceProcessor');
			self::import('services.PathService');

			// Get the path segments, except for the first one which we already know is "resources"
			$segs = array_slice(array_merge($this->request->pathSegments), 1);

			// Is this a plugin resource?
			$plugin = (isset($segs[0]) && $segs[0] == 'plugin' ? (isset($segs[1]) ? $segs[1] : null) : false);
			if ($plugin !== false)
			{
				if ($plugin === null)
					throw new HttpException(404);

				$segs = array_splice($segs, 2);

				$rootFolderUrl = UrlHelper::generateUrl($this->config->resourceTriggerWord."/plugin/{$plugin}/");
				$rootFolderPath = $this->path->pluginsPath."{$plugin}/resources/";
			}
			else
			{
				$rootFolderUrl = UrlHelper::generateUrl($this->config->resourceTriggerWord.'/');
				$rootFolderPath = $this->path->resourcesPath;
			}

			$relativeResourcePath = implode('/', $segs);

			$resourceProcessor = new ResourceProcessor($rootFolderPath, $rootFolderUrl, $relativeResourcePath);
			$resourceProcessor->processResourceRequest();

			exit(1);
		}
	}

	/**
	 * @return mixed
	 * @throws Exception|HttpException
	 */
	private function _validateConfig()
	{
		$messages = array();

		$databaseServerName = $this->config->getDbItem('server');
		$databaseAuthName = $this->config->getDbItem('user');
		$databaseName = $this->config->getDbItem('database');
		$databasePort = $this->config->getDbItem('port');
		$databaseTablePrefix = $this->config->getDbItem('tablePrefix');
		$databaseCharset = $this->config->getDbItem('charset');
		$databaseCollation = $this->config->getDbItem('collation');

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
			// For Blocks to be considered "set up", there must be at least one license key, site, and admin user.
			$this->_isSetup = (
				LicenseKey::model()->exists()
				&& Site::model()->exists()
				&& User::model()->exists('admin=:admin', array(':admin'=>true)));
		}

		return $this->_isSetup;
	}

	/**
	 * @param $isSetup
	 */
	public function setIsSetup($isSetup)
	{
		$this->_isSetup = $isSetup;
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
}
