<?php
namespace Blocks;

/**
 *
 */
class App extends \CWebApplication
{
	private $_templatePath;
	private $_isInstalled;
	private $_isSetup;

	/**
	 * Processes resource requests before anything else has a chance to initialize.
	 */
	public function init()
	{
		// Set default timezone to UTC
		date_default_timezone_set('UTC');

		// In case of an error, import everything we need.
		Blocks::import('app.business.exceptions.HttpException');
		Blocks::import('app.business.db.DbCommand');
		Blocks::import('app.business.db.DbConnection');
		Blocks::import('app.business.db.PDO');
		Blocks::import('app.business.db.MysqlSchema');
		Blocks::import('app.business.web.ErrorHandler');
		Blocks::import('app.business.web.templating.TemplateRenderer');

		// We would normally use the 'preload' config option for logging, but because of PHP namespace hackery, we'll manually load it here.
		Blocks::import('app.business.services.ConfigService');
		Blocks::import('app.business.enums.AttributeType');
		Blocks::import('app.business.utils.DatabaseHelper');
		Blocks::import('app.business.utils.Json');
		Blocks::import('app.business.Component');
		Blocks::import('app.business.Plugin');
		Blocks::import('app.business.logging.FileLogRoute');
		Blocks::import('app.business.logging.WebLogRoute');
		Blocks::import('app.business.logging.ProfileLogRoute');
		Blocks::import('app.business.logging.DbLogRoute');
		b()->getComponent('log');

		// Manually load the request object as early as possible.
		Blocks::import('app.business.enums.UrlFormat');
		Blocks::import('app.business.enums.RequestMode');
		Blocks::import('app.business.utils.HtmlHelper');
		Blocks::import('app.business.utils.UrlHelper');
		Blocks::import('app.business.web.HttpRequest');
		Blocks::import('app.business.web.UrlManager');
		b()->getComponent('request');

		parent::init();
	}

	/**
	 * Prepares Yii's autoloader with a map pointing all of Blocks' class names to their file paths.
	 * @access private
	 */
	private function _importClasses()
	{
		$aliases = array(
			'app.blocktypes.*',
			'app.business.console.*',
			'app.business.console.commands.*',
			'app.business.datetime.*',
			'app.business.db.*',
			'app.business.email.*',
			'app.business.enums.*',
			'app.business.exceptions.*',
			'app.business.install.*',
			'app.business.logging.*',
			'app.business.services.*',
			'app.business.updates.*',
			'app.business.utils.*',
			'app.business.validators.*',
			'app.business.web.*',
			'app.business.web.filters.*',
			'app.business.web.templating.*',
			'app.business.web.templating.adapters.*',
			'app.business.web.templating.templatewidgets.*',
			'app.business.web.templating.variables.*',
			'app.business.webservices.*',
			'app.controllers.*',
			'app.migrations.*',
			'app.models.*',
			'app.models.forms.*',
			'app.widgets.*',
		);

		foreach ($aliases as $alias)
		{
			Blocks::import($alias);
		}
	}

	/**
	 * Processes the request.
	 * @throws HttpException
	 */
	public function processRequest()
	{
		// If this is a resource request, we should respond with the resource ASAP
		$this->_processResourceRequest();

		// Import the majority of Blocks' classes
		$this->_importClasses();

		// Config validation
		$this->_validateConfig();

		// Process install and setup requests?
		$this->_processSpecialRequests('install', !$this->getIsInstalled());
		$this->_processSpecialRequests('setup', !$this->getIsSetup());

		// Are we in the middle of a manual update?
		if ($this->getIsDbUpdateNeeded())
		{
			// Let's let all CP requests through.
			if ($this->request->mode == RequestMode::CP)
			{
				$this->runController('dbupdate');
				$this->end();
			}
			// We'll also let action requests to dbupdate through as well.
			else if ($this->request->getMode() == RequestMode::Action && $this->request->getActionController() == 'dbupdate')
			{
				$this->runController($this->request->getActionController().'/'.$this->request->getActionAction());
				$this->end();
			}
			else
				throw new HttpException(404);
		}

		// If it's not a CP request OR the system is on, let's continue processing.
		if (Blocks::isSystemOn() || (!Blocks::isSystemOn() && ($this->request->getMode() == RequestMode::CP || ($this->request->getMode() == RequestMode::Action && BLOCKS_CP_REQUEST))))
		{
			// Otherwise maybe it's an action request?
			$this->_processActionRequest();

			// Otherwise run the template controller
			$this->runController('template');
		}
		else
		{
			// Display the offline template for the front-end.
			$this->runController('template/offline');
		}
	}

	/**
	 * Processes install and setup requests.
	 * @access private
	 *
	 * @param string $what  The controller and possible first URL segment value.
	 * @param bool   $force Whether to redirect to that controller if we're not already there.
	 * @throws HttpException
	 */
	private function _processSpecialRequests($what, $force)
	{
		// Are they requesting this specifically?
		if ($this->request->getMode() == RequestMode::CP && $this->request->getPathSegment(1) === $what)
		{
			$action = $this->request->getPathSegment(2, 'index');
			$this->runController("{$what}/{$action}");
			$this->end();
		}

		// Should they be?
		else if ($force)
		{
			// Give it to them if accessing the CP or it's an action request for logging in.
			if ($this->request->getMode() == RequestMode::CP)
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
	 * Processes action requests.
	 * @access private
	 * @throws HttpException
	 */
	private function _processActionRequest()
	{
		if ($this->request->getMode() == RequestMode::Action)
		{
			$plugin = $this->request->getPluginHandle();

			if ($plugin !== null)
				Blocks::import("plugins.{$plugin}.controllers.*");

			$this->runController($this->request->getActionController().'/'.$this->request->getActionAction());
			$this->end();
		}
	}

	/**
	 * Processes resource requests.
	 * @access private
	 * @throws HttpException
	 */
	private function _processResourceRequest()
	{
		if ($this->request->getMode() == RequestMode::Resource)
		{
			// Import the bare minimum to process a resource
			Blocks::import('app.business.utils.File');
			Blocks::import('app.business.web.ResourceProcessor');
			Blocks::import('app.business.services.PathService');

			// Get the path segments, except for the first one which we already know is "resources"
			$segs = array_slice(array_merge($this->request->getPathSegments()), 1);

			$rootFolderUrl = null;
			$rootFolderPath = $this->path->getResourcesPath();
			$relativeResourcePath = implode('/', $segs);

			// Check app/resources folder first.
			if (file_exists($rootFolderPath.$relativeResourcePath))
			{
				$rootFolderUrl = UrlHelper::generateUrl($this->config->resourceTriggerWord.'/');
			}
			else
			{
				// See if the first segment is a plugin handle.
				if (isset($segs[0]))
				{
					$rootFolderPath = $this->path->getPluginsPath().$segs[0].'/resources/';
					$relativeResourcePath = implode('/', array_splice($segs, 2));

					// Looks like it belongs to a plugin.
					if (file_exists($rootFolderPath.$relativeResourcePath))
					{
						$rootFolderUrl = UrlHelper::generateUrl($this->config->resourceTriggerWord.$segs[0]);
					}
				}
			}

			// Couldn't find a match, so 404
			if (!$rootFolderUrl)
				throw new HttpException(404);

			$resourceProcessor = new ResourceProcessor($rootFolderPath, $rootFolderUrl, $relativeResourcePath);
			$resourceProcessor->processResourceRequest();

			exit(1);
		}
	}

	/**
	 * Validates the system config.
	 * @access private
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
		catch (\Exception $e)
		{
			$messages[] = 'There is a problem connecting to the database with the credentials supplied in your db config file.';
		}

		if (!empty($messages))
			throw new Exception(implode(PHP_EOL, $messages));
	}

	/**
	 * Determines if we're in the middle of a manual update, and a DB update is needed.
	 * @return bool
	 */
	public function getIsDbUpdateNeeded()
	{
		if (Blocks::getBuild(false) !== Blocks::getStoredBuild() || Blocks::getVersion(false) !== Blocks::getStoredVersion())
		{
			// Make sure we're not running from source
			if (Blocks::getProduct() == '')
				return false;
			else
				return true;
		}
		else
			return false;
	}

	/**
	 * Determines if Blocks is installed by checking if the info table exists.
	 * @return bool
	 */
	public function getIsInstalled()
	{
		if (!isset($this->_isInstalled))
		{
			$infoTable = $this->db->schema->getTable('{{info}}');
			$this->_isInstalled = (bool)$infoTable;
		}
		return $this->_isInstalled;
	}

	/**
	 * Sets the isInstalled state.
	 * @param bool $isInstalled
	 */
	public function setIsInstalled($isInstalled)
	{
		$this->_isInstalled = (bool)$isInstalled;
	}

	/**
	 * Determines if Blocks has been setup yet, by checking to see if a license key has been entered, a site has been created, and an admin user exists.
	 * @return bool
	 */
	public function getIsSetup()
	{
		if (!isset($this->_isSetup))
		{
			$this->_isSetup = (
				LicenseKey::model()->exists()
				&& Site::model()->exists()
				&& User::model()->exists('admin=:admin', array(':admin'=>true)));
		}

		return $this->_isSetup;
	}

	/**
	 * Sets the isSetup state.
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
				$this->_templatePath = $this->path->getTemplatePath();
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
	 * Sets the template path for the app.
	 * @param $path
	 */
	public function setViewPath($path)
	{
		$this->_templatePath = $path;
	}

	/**
	 * Returns the CP templates path.
	 * @return string
	 */
	public function getSystemViewPath()
	{
		return $this->path->getCpTemplatesPath();
	}

	/**
	 * Formats an exception into JSON before returning it to the client.
	 * @param array $data
	 */
	public function returnAjaxException($data)
	{
		$exceptionArr['error'] = $data['message'];

		if (b()->config->devMode)
		{
			$exceptionArr['trace']  = $data['trace'];
			$exceptionArr['traces'] = $data['traces'];
			$exceptionArr['file']   = $data['file'];
			$exceptionArr['line']   = $data['line'];
			$exceptionArr['type']   = $data['type'];
		}

		Json::sendJsonHeaders();
		echo Json::encode($exceptionArr);
		$this->end();
	}

	/**
	 * Formats a PHP error into JSON before returning it to the client.
	 * @param integer $code error code
	 * @param string $message error message
	 * @param string $file error file
	 * @param string $line error line
	 */
	public function returnAjaxError($code, $message, $file, $line)
	{
		if(b()->config->devMode == true)
		{
			$outputTrace = '';
			$trace = debug_backtrace();

			// skip the first 3 stacks as they do not tell the error position
			if(count($trace) > 3)
				$trace = array_slice($trace, 3);

			foreach($trace as $i => $t)
			{
				if (!isset($t['file']))
					$t['file'] = 'unknown';

				if (!isset($t['line']))
					$t['line'] = 0;

				if (!isset($t['function']))
					$t['function'] = 'unknown';

				$outputTrace .= "#$i {$t['file']}({$t['line']}): ";

				if (isset($t['object']) && is_object($t['object']))
					$outputTrace .= get_class($t['object']).'->';

				$outputTrace .= "{$t['function']}()\n";
			}

			$errorArr = array(
				'error' => $code.' : '.$message,
				'trace' => $outputTrace,
				'file'  => $file,
				'line'  => $line,
			);
		}
		else
		{
			$errorArr = array('error' => $message);
		}

		Json::sendJsonHeaders();
		echo Json::encode($errorArr);
		$this->end();
	}
}
