<?php
namespace Blocks;

/**
 *
 * @property TemplatesService $templates The template service
 * @property AssetsService $assets The assets service
 * @property AssetIndexingService $assetIndexing The assets indexing service
 * @property AssetSourcesService $assetSources The assets sources service
 * @property PathService $path
 * @property UsersService $users
 * @property AccountService $account
 * @property ImagesService $images
 */
class App extends \CWebApplication
{
	public $componentAliases;

	private $_templatePath;
	private $_isInstalled;
	private $_validDbConfig = null;

	/**
	 * Processes resource requests before anything else has a chance to initialize.
	 */
	public function init()
	{
		// Set default timezone to UTC
		date_default_timezone_set('UTC');

		foreach ($this->componentAliases as $alias)
		{
			Blocks::import($alias);
		}

		blx()->getComponent('request');
		blx()->getComponent('log');

		parent::init();
	}

	/**
	 * Processes the request.
	 *
	 * @throws HttpException
	 */
	public function processRequest()
	{
		// Let's set the target language from the browser's language preferences.
		$this->_processBrowserLanguage();

		// If this is a resource request, we should respond with the resource ASAP
		$this->_processResourceRequest();

		// Database config validation
		$this->_validateDbConfig();

		// We add the DbLogRoute *after* we have validated the config.
		$this->_addDbLogRoute();

		// Process install requests
		$this->_processInstallRequest();

		// Are we in the middle of a manual update?
		if ($this->isDbUpdateNeeded())
		{
			// Let's let all CP requests through.
			if ($this->request->getType() == HttpRequestType::CP)
			{
				$this->runController('update/manualUpdate');
				$this->end();
			}
			// We'll also let action requests to UpdateController through as well.
			else if ($this->request->getType() == HttpRequestType::Action && (($actionPath = $this->request->getActionPath()) !== null) && isset($actionPath[0]) && $actionPath[0] == 'update')
			{
				$controller = $actionPath[0];
				$action = isset($actionPath[1]) ? $actionPath[1] : 'index';
				$this->runController($controller.'/'.$action);
				$this->end();
			}
			else
			{
				throw new HttpException(404);
			}
		}

		// If it's not a CP request OR the system is on, let's continue processing.
		if (Blocks::isSystemOn() || (!Blocks::isSystemOn() && ($this->request->getType() == HttpRequestType::CP || ($this->request->getType() == HttpRequestType::Action && BLOCKS_CP_REQUEST))))
		{
			// Attempt to set the target language from user preferences.
			$this->_processUserPreferredLanguage();

			// Otherwise maybe it's an action request?
			$this->_processActionRequest();

			// Otherwise run the template controller
			$this->runController('templates');
		}
		else
		{
			// Display the offline template for the front-end.
			$this->runController('templates/offline');
		}
	}

	/**
	 * Processes install requests.
	 *
	 * @access private
	 * @throws HttpException
	 */
	private function _processInstallRequest()
	{
		// Are they requesting an installer template/action specifically?
		if ($this->request->getType() == HttpRequestType::CP && $this->request->getSegment(1) === 'install')
		{
			$action = $this->request->getSegment(2, 'index');
			$this->runController('install/'.$action);
			$this->end();
		}
		else if (BLOCKS_CP_REQUEST && $this->request->getType() == HttpRequestType::Action)
		{
			$actionPath = $this->request->getActionPath();
			if (isset($actionPath[0]) && $actionPath[0] == 'install')
			{
				$this->_processActionRequest();
			}
		}

		// Should they be?
		else if (!$this->isInstalled())
		{
			// Give it to them if accessing the CP
			if ($this->request->getType() == HttpRequestType::CP)
			{
				$url = UrlHelper::getUrl('install');
				$this->request->redirect($url);
			}
			// Otherwise return a 404
			else
			{
				throw new HttpException(404);
			}
		}
	}

	/**
	 * Get's the browser's preferred languages, checks to see if we have translation data for it and set the target language.
	 */
	private function _processBrowserLanguage()
	{
		$browserLanguages = blx()->request->getBrowserLanguages();

		// If browserLanguages == false, then it's not a browser request (cURL, Requests, etc.)
		if ($browserLanguages)
		{
			foreach ($browserLanguages as $language)
			{
				// Check to see if we have translation data for the language.  If it doesn't exist, it will default to en_us.
				if (Locale::exists($language))
				{
					$this->setLanguage($language);
					break;
				}
			}
		}
	}

	/**
	 * See if the user is logged in and they have a preferred language.  If so, use it.
	 */
	private function _processUserPreferredLanguage()
	{
		// See if the user is logged in.
		if (blx()->user->isLoggedIn())
		{
			$user = blx()->account->getCurrentUser();
			$userLanguage = Locale::getCanonicalID($user->language);

			// If the user has a preferred language saved and we have translation data for it, set the target language.
			if (($userLanguage !== $this->getLanguage()) && Locale::exists($userLanguage))
			{
				$this->setLanguage($userLanguage);
			}
		}
	}

	/**
	 * Processes action requests.
	 *
	 * @access private
	 * @throws HttpException
	 */
	private function _processActionRequest()
	{
		if ($this->request->getType() == HttpRequestType::Action)
		{
			$actionPath = $this->request->getActionPath();

			// See if there is a first segment.
			if (isset($actionPath[0]))
			{
				$controller = $actionPath[0];
				$action = isset($actionPath[1]) ? $actionPath[1] : '';

				// Check for a valid controller
				$class = __NAMESPACE__.'\\'.ucfirst($controller).'Controller';
				if (class_exists($class))
				{
					$route = $controller.'/'.$action;
					$this->runController($route);
					$this->end();
				}
				else
				{
					// Mayhaps this is a plugin action request.
					$plugin = strtolower($actionPath[0]);

					if (($plugin = blx()->plugins->getPlugin($plugin)) !== null)
					{
						$pluginHandle = $plugin->getClassHandle();

						// Check to see if the second segment is an existing controller.  If no second segment, check for "PluginHandle"Controller, which is a plugin's default controller.
						// i.e. pluginHandle/testController or pluginHandle/pluginController
						$controller = (isset($actionPath[1]) ? ucfirst($pluginHandle).'_'.ucfirst($actionPath[1]) : ucfirst($pluginHandle)).'Controller';

						if (class_exists(__NAMESPACE__.'\\'.$controller))
						{
							// Check to see if there is a 3rd path segment.  If so, use it for the action.  If not, use the default Index for the action.
							// i.e. pluginHandle/pluginController/index or pluginHandle/pluginController/testAction
							$action = isset($actionPath[2]) ? $actionPath[2] : 'Index';

							$route = substr($controller, 0, strpos($controller, 'Controller')).'/'.$action;
							$this->runController($route);
							$this->end();
						}
						else
						{
							// It's possible the 2nd segment is an action and they are using the plugin's default controller.
							// i.e. pluginHandle/testAction or pluginHandle/indexAction.
							// Here, the plugin's default controller is assumed.
							$controller = ucfirst($pluginHandle).'Controller';

							if (class_exists(__NAMESPACE__.'\\'.$controller))
							{
								$action = $actionPath[1];

								$route = substr($controller, 0, strpos($controller, 'Controller')).'/'.$action;
								$this->runController($route);
								$this->end();
							}
						}
					}
				}
			}

			throw new HttpException(404);
		}
	}

	/**
	 * Creates a controller instance based on a route.
	 */
	public function createController($route)
	{
		if (($route=trim($route,'/')) === '')
		{
			$route = $this->defaultController;
		}

		$routeParts = explode('/', $route);
		$controllerId = ucfirst(array_shift($routeParts));
		$action = implode('/', $routeParts);

		$class = __NAMESPACE__.'\\'.$controllerId.'Controller';

		if (class_exists($class))
		{
			return array(
				Blocks::createComponent($class, $controllerId),
				$this->parseActionParams($action),
			);
		}
	}

	/**
	 * Processes resource requests.
	 *
	 * @access private
	 * @throws HttpException
	 */
	private function _processResourceRequest()
	{
		if ($this->request->getType() == HttpRequestType::Resource)
		{
			// Get the path segments, except for the first one which we already know is "resources"
			$segs = array_slice(array_merge($this->request->getSegments()), 1);
			$path = implode('/', $segs);

			blx()->resources->sendResource($path);
		}
	}

	/**
	 * Validates that we can connect to the database with the settings in the db config file.
	 *
	 * @access private
	 * @return mixed
	 * @throws Exception|HttpException
	 */
	private function _validateDbConfig()
	{
		$messages = array();

		$databaseServerName = $this->config->getDbItem('server');
		$databaseAuthName = $this->config->getDbItem('user');
		$databaseName = $this->config->getDbItem('database');
		$databasePort = $this->config->getDbItem('port');
		$databaseCharset = $this->config->getDbItem('charset');
		$databaseCollation = $this->config->getDbItem('collation');

		if (StringHelper::isNullOrEmpty($databaseServerName))
		{
			$messages[] = Blocks::t('The database server name isn’t set in your db config file.');
		}

		if (StringHelper::isNullOrEmpty($databaseAuthName))
		{
			$messages[] = Blocks::t('The database user name isn’t set in your db config file.');
		}

		if (StringHelper::isNullOrEmpty($databaseName))
		{
			$messages[] = Blocks::t('The database name isn’t set in your db config file.');
		}

		if (StringHelper::isNullOrEmpty($databasePort))
		{
			$messages[] = Blocks::t('The database port isn’t set in your db config file.');
		}

		if (StringHelper::isNullOrEmpty($databaseCharset))
		{
			$messages[] = Blocks::t('The database charset isn’t set in your db config file.');
		}

		if (StringHelper::isNullOrEmpty($databaseCollation))
		{
			$messages[] = Blocks::t('The database collation isn’t set in your db config file.');
		}

		if (!empty($messages))
		{
			$this->_validDbConfig = false;
			throw new Exception(Blocks::t('Database configuration errors: {errors}', array('errors' => implode(PHP_EOL, $messages))));
		}

		try
		{
			$connection = $this->db;
			if (!$connection)
			{
				$messages[] = Blocks::t('There is a problem connecting to the database with the credentials supplied in your db config file.');
			}
		}
		catch (\Exception $e)
		{
			Blocks::log($e->getMessage(), \CLogger::LEVEL_ERROR);
			$messages[] = Blocks::t('There is a problem connecting to the database with the credentials supplied in your db config file.');
		}

		if (!empty($messages))
		{
			$this->_validDbConfig = false;
			throw new Exception(Blocks::t('Database configuration errors: {errors}', array('errors' => implode(PHP_EOL, $messages))));
		}

		$this->_validDbConfig = true;
	}

	/**
	 * Adds the DbLogRoute class to the log router.
	 */
	private function _addDbLogRoute()
	{
		$route = array('class' => 'Blocks\\DbLogRoute');
		$this->log->addRoute($route);
	}

	/**
	 * Checks whether the database config values are valid or not.
	 *
	 * @return mixed
	 */
	public function isDbConfigValid()
	{
		if ($this->_validDbConfig === null)
		{
			$this->_validateDbConfig();
		}

		return $this->_validDbConfig;
	}

	/**
	 * Determines if we're in the middle of a manual update, and a DB update is needed.
	 *
	 * @return bool
	 */
	public function isDbUpdateNeeded()
	{
		if (Blocks::getBuild() !== Blocks::getStoredBuild() || Blocks::getVersion() !== Blocks::getStoredVersion())
		{
			return true;
		}
		else
			return false;
	}

	/**
	 * Determines if Blocks is installed by checking if the info table exists.
	 *
	 * @return bool
	 */
	public function isInstalled()
	{
		if (!isset($this->_isInstalled))
		{
			$infoTable = $this->db->getSchema()->getTable('{{info}}');
			$this->_isInstalled = (bool)$infoTable;
		}

		return $this->_isInstalled;
	}

	/**
	 * Sets the isInstalled state.
	 *
	 * @param bool $isInstalled
	 */
	public function setInstalledStatus($isInstalled)
	{
		$this->_isInstalled = (bool)$isInstalled;
	}

	/**
	 * Gets the viewPath for the incoming request.
	 * We can't use setViewPath() because our view path depends on the request type, which is initialized after web application, so we override getViewPath();
	 *
	 * @return mixed
	 */
	public function getViewPath()
	{
		if (!isset($this->_templatePath))
		{
			if (strpos(get_class($this->request), 'HttpRequest') !== false)
			{
				$this->_templatePath = $this->path->getTemplatesPath();
			}
			else
			{
				// in the case of an exception, our custom classes are not loaded.
				$this->_templatePath = BLOCKS_SITE_TEMPLATES_PATH;
			}
		}

		return $this->_templatePath;
	}

	/**
	 * Sets the template path for the app.
	 *
	 * @param $path
	 */
	public function setViewPath($path)
	{
		$this->_templatePath = $path;
	}

	/**
	 * Returns the CP templates path.
	 *
	 * @return string
	 */
	public function getSystemViewPath()
	{
		return $this->path->getCpTemplatesPath();
	}

	/**
	 * Formats an exception into JSON before returning it to the client.
	 *
	 * @param array $data
	 */
	public function returnAjaxException($data)
	{
		$exceptionArr['error'] = $data['message'];

		if (blx()->config->devMode)
		{
			$exceptionArr['trace']  = $data['trace'];
			$exceptionArr['traces'] = $data['traces'];
			$exceptionArr['file']   = $data['file'];
			$exceptionArr['line']   = $data['line'];
			$exceptionArr['type']   = $data['type'];
		}

		JsonHelper::sendJsonHeaders();
		echo JsonHelper::encode($exceptionArr);
		$this->end();
	}

	/**
	 * Formats a PHP error into JSON before returning it to the client.
	 *
	 * @param integer $code error code
	 * @param string $message error message
	 * @param string $file error file
	 * @param string $line error line
	 */
	public function returnAjaxError($code, $message, $file, $line)
	{
		if(blx()->config->devMode == true)
		{
			$outputTrace = '';
			$trace = debug_backtrace();

			// skip the first 3 stacks as they do not tell the error position
			if(count($trace) > 3)
				$trace = array_slice($trace, 3);

			foreach($trace as $i => $t)
			{
				if (!isset($t['file']))
				{
					$t['file'] = 'unknown';
				}

				if (!isset($t['line']))
				{
					$t['line'] = 0;
				}

				if (!isset($t['function']))
				{
					$t['function'] = 'unknown';
				}

				$outputTrace .= "#$i {$t['file']}({$t['line']}): ";

				if (isset($t['object']) && is_object($t['object']))
				{
					$outputTrace .= get_class($t['object']).'->';
				}

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

		JsonHelper::sendJsonHeaders();
		echo JsonHelper::encode($errorArr);
		$this->end();
	}
}
