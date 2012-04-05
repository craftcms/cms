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
		// in case of an error, import everything we need
		Blocks::import('business.exceptions.HttpException');
		Blocks::import('business.db.DbCommand');
		Blocks::import('business.db.DbConnection');
		Blocks::import('business.db.MysqlSchema');
		Blocks::import('business.web.ErrorHandler');
		Blocks::import('business.web.templating.TemplateRenderer');

		// If this is a resource request, we should respond with the resource ASAP
		$this->_processResourceRequest();

		// We would normally use the 'preload' config option for logging, but because of PHP namespace hackery, we'll manually load it here.
		Blocks::import('business.logging.WebLogRoute');
		Blocks::import('business.logging.ProfileLogRoute');
		b()->getComponent('log');

		parent::init();
	}

	/**
	 * Prepares Yii's autoloader with a map pointing all of Blocks' class names to their file paths.
	 * @access private
	 */
	private function _importClasses()
	{
		$aliases = array(
			'blocktypes.*',
			'business.console.*',
			'business.console.commands.*',
			'business.datetime.*',
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
			'controllers.*',
			'migrations.*',
			'models.*',
			'models.forms.*',
			'services.*',
			'widgets.*',
		);

		foreach ($aliases as $alias)
		{
			Blocks::import($alias);
		}
	}

	/**
	 * Processes the request.
	 */
	public function processRequest()
	{
		// Import the majority of Blocks' classes
		$this->_importClasses();

		// Config validation
		$this->_validateConfig();

		// Process install and setup requests?
		$this->_processSpecialRequests('install', !$this->isInstalled);
		$this->_processSpecialRequests('setup', !$this->isSetup);

		// Are we in the middle of a manual update?
		if ($this->isDbUpdateNeeded)
		{
			// Let's let all CP requests through.
			if ($this->request->mode == RequestMode::CP)
			{
				$this->runController('dbupdate');
				$this->end();
			}
			// We'll also let action requests to dbupdate through as well.
			else if ($this->request->mode == RequestMode::Action && $this->request->actionController == 'dbupdate')
			{
				$this->runController($this->request->actionController.'/'.$this->request->actionAction);
				$this->end();
			}
			else
				throw new HttpException(404);
		}

		// If it's not a CP request OR the system is on, let's continue processing.
		if (Blocks::isSystemOn() || (!Blocks::isSystemOn() && ($this->request->mode == RequestMode::CP || ($this->request->mode == RequestMode::Action && BLOCKS_CP_REQUEST))))
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
	 * @param string $what  The controller and possible first URL segment value.
	 * @param bool   $force Whether to redirect to that controller if we're not already there.
	 */
	private function _processSpecialRequests($what, $force)
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
			// Give it to them if accessing the CP or it's an action request for logging in.
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
	 * Processes action requests.
	 * @access private
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
	 * Processes resource requests.
	 * @access private
	 */
	private function _processResourceRequest()
	{
		// Import the bare minimum to determine if what type of request this is
		Blocks::import('business.Component');
		Blocks::import('business.Plugin');
		Blocks::import('business.enums.UrlFormat');
		Blocks::import('business.enums.RequestMode');
		Blocks::import('business.utils.HtmlHelper');
		Blocks::import('business.utils.UrlHelper');
		Blocks::import('business.web.HttpRequest');
		Blocks::import('business.web.UrlManager');
		Blocks::import('services.ConfigService');

		if ($this->request->mode == RequestMode::Resource)
		{
			// Import the bare minimum to process a resource
			Blocks::import('business.utils.File');
			Blocks::import('business.web.ResourceProcessor');
			Blocks::import('services.PathService');

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
		catch(Exception $e)
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
			if (strpos(Blocks::getEdition(false), '@@@') !== false)
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
		return $this->path->cpTemplatesPath;
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
