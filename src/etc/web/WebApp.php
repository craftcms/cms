<?php
namespace Craft;

/**
 *
 * @property TemplatesService            $templates            The template service
 * @property AssetsService               $assets               The assets service
 * @property AssetIndexingService        $assetIndexing        The assets indexing service
 * @property AssetTransformationsService $assetTransformations The assets sizes service
 * @property AssetSourcesService         $assetSources         The assets sources service
 * @property PathService                 $path                 The path service
 * @property UsersService                $users                The users service
 * @property ImagesService               $images               The images service
 * @property ResourcesService            $resources            The resources service
 * @property HttpRequestService          $request              The request service
 * @property DbConnection                $db                   Database
 * @property LinksService                $links                The links service
 * @property ElementsService             $elements             The elements service
 * @property ComponentsService           $components           The components service
 */
class WebApp extends \CWebApplication
{
	/**
	 * @var string The language that the application is written in. This mainly refers to
	 * the language that the messages and view files are in.
	 *
	 * Setting it here even though CApplication already defaults to 'en_us',
	 * so it's clear and in case they change it down the road.
	 */
	public $sourceLanguage = 'en_us';

	/**
	 * @var array List of built-in component aliases to be imported.
	 */
	public $componentAliases;

	private $_templatePath;
	private $_packageComponents;

	/**
	 * Sets the application components.
	 */
	public function setComponents($components, $merge = true)
	{
		if (isset($components['pkgComponents']))
		{
			$this->_packageComponents = $components['pkgComponents'];
			unset($components['pkgComponents']);
		}

		parent::setComponents($components, $merge);
	}

	/**
	 * Processes resource requests before anything else has a chance to initialize.
	 */
	public function init()
	{
		// Set default timezone to UTC
		date_default_timezone_set('UTC');

		// Import all the built-in components
		foreach ($this->componentAliases as $alias)
		{
			Craft::import($alias);
		}

		// Set the appropriate package components
		if (isset($this->_packageComponents))
		{
			foreach ($this->_packageComponents as $packageName => $packageComponents)
			{
				if (Craft::hasPackage($packageName))
				{
					$this->setComponents($packageComponents);
				}
			}

			unset($this->_packageComponents);
		}

		// Initialize HttpRequestService and LogRouter right away
		$this->getComponent('request');
		$this->getComponent('log');

		parent::init();
	}

	/**
	 * Processes the request.
	 *
	 * @throws HttpException
	 */
	public function processRequest()
	{
		// If this is a resource request, we should respond with the resource ASAP
		$this->_processResourceRequest();

		// Database config validation
		$this->_validateDbConfig();

		// Process install requests
		$this->_processInstallRequest();

		// If the system in is maintenance mode and it's a site request, throw a 503.
		if (Craft::isInMaintenanceMode() && $this->request->isSiteRequest())
		{
			throw new HttpException(503);
		}

		// isDbUpdateNeeded will return true if we're in the middle of a manual or auto-update.
		// If we're in maintenance mode and it's not a site request, show the manual update template.
		if (craft()->updates->isDbUpdateNeeded() || (Craft::isInMaintenanceMode() && $this->request->isCpRequest()))
		{
			// Let all non-action CP requests through.
			if (
				$this->request->isCpRequest() &&
				(!$this->request->isActionRequest() || $this->request->getActionSegments() == array('users', 'login'))
			)
			{
				// If there is a 'manual=1' in the query string, run the templates controller.
				if ($this->request->getParam('manual', null) == 1)
				{
					$this->runController('templates');
					$this->end();
				}
				else
				{
					if (craft()->updates->isBreakpointUpdateNeeded())
					{
						$this->runController('update/breakpointUpdate');
						$this->end();
					}
					else
					{
						if (!craft()->request->isAjaxRequest())
						{
							if (craft()->request->getPathInfo() !== '')
							{
								craft()->userSession->setReturnUrl(craft()->request->getPath());
							}
						}

						$this->runController('update/manualUpdate');
						$this->end();
					}
				}
			}
			// We'll also let action requests to UpdateController through as well.
			else if ($this->request->isActionRequest() && (($actionSegs = $this->request->getActionSegments()) !== null) && isset($actionSegs[0]) && $actionSegs[0] == 'update')
			{
				$controller = $actionSegs[0];
				$action = isset($actionSegs[1]) ? $actionSegs[1] : 'index';
				$this->runController($controller.'/'.$action);
				$this->end();
			}
			else
			{
				throw new HttpException(503);
			}
		}

		// Make sure that the system is on, or that the user has permission to access the site/CP while the system is off
		if (Craft::isSystemOn() ||
			($this->request->isActionRequest() && $this->request->getActionSegments() == array('users', 'login')) ||
			($this->request->isSiteRequest() && $this->userSession->checkPermission('accessSiteWhenSystemIsOff')) ||
			($this->request->isCpRequest()) && $this->userSession->checkPermission('accessCpWhenSystemIsOff')
		)
		{
			// Set the target language
			$this->setLanguage($this->_getTargetLanguage());

			// Load the plugins
			$this->plugins;

			// Otherwise maybe it's an action request?
			$this->_processActionRequest();

			// Otherwise run the template controller
			$this->runController('templates');
		}
		else
		{
			// Log out the user
			if ($this->userSession->isLoggedIn())
			{
				$this->userSession->logout();
			}

			if ($this->request->isCpRequest())
			{
				// Redirect them to the login screen
				$this->userSession->requireLogin();
			}
			else
			{
				// Display the offline template
				$this->runController('templates/offline');
			}
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
		$isCpRequest = $this->request->isCpRequest();

		// Are they requesting an installer template/action specifically?
		if ($isCpRequest && $this->request->getSegment(1) === 'install')
		{
			$action = $this->request->getSegment(2, 'index');
			$this->runController('install/'.$action);
			$this->end();
		}
		else if ($isCpRequest && $this->request->isActionRequest())
		{
			$actionSegs = $this->request->getActionSegments();
			if (isset($actionSegs[0]) && $actionSegs[0] == 'install')
			{
				$this->_processActionRequest();
			}
		}

		// Should they be?
		else if (!Craft::isInstalled())
		{
			// Give it to them if accessing the CP
			if ($isCpRequest)
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
	 * Returns the target app language.
	 *
	 * @access private
	 * @return string
	 */
	private function _getTargetLanguage()
	{
		$siteLocaleIds = craft()->i18n->getSiteLocaleIds();

		if (craft()->request->isCpRequest())
		{
			// If the user is logged in *and* has a primary language set, use that
			$user = craft()->userSession->getUser();

			if ($user && $user->preferredLocale)
			{
				return $user->preferredLocale;
			}

			// Otherwise check if the browser's preferred language matches any of the site locales
			$browserLanguages = craft()->request->getBrowserLanguages();

			foreach ($browserLanguages as $language)
			{
				if (in_array($language, $siteLocaleIds))
				{
					return $language;
				}
			}
		}
		else
		{
			// Is CRAFT_LOCALE set to a valid site locale?
			if (defined('CRAFT_LOCALE') && in_array(CRAFT_LOCALE, $siteLocaleIds))
			{
				return CRAFT_LOCALE;
			}
		}

		// Just use the primary site locale
		return craft()->i18n->getPrimarySiteLocale()->getId();
	}

	/**
	 * Processes action requests.
	 *
	 * @access private
	 * @throws HttpException
	 */
	private function _processActionRequest()
	{
		if ($this->request->isActionRequest())
		{
			$actionSegs = $this->request->getActionSegments();

			// See if there is a first segment.
			if (isset($actionSegs[0]))
			{
				$controller = $actionSegs[0];
				$action = isset($actionSegs[1]) ? $actionSegs[1] : '';

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
					$plugin = strtolower($actionSegs[0]);

					if (($plugin = craft()->plugins->getPlugin($plugin)) !== null)
					{
						$pluginHandle = $plugin->getClassHandle();

						// Check to see if the second segment is an existing controller.  If no second segment, check for "PluginHandle"Controller, which is a plugin's default controller.
						// i.e. pluginHandle/testController or pluginHandle/pluginController
						$controller = (isset($actionSegs[1]) ? ucfirst($pluginHandle).'_'.ucfirst($actionSegs[1]) : ucfirst($pluginHandle)).'Controller';

						if (class_exists(__NAMESPACE__.'\\'.$controller))
						{
							// Check to see if there is a 3rd path segment.  If so, use it for the action.  If not, use the default Index for the action.
							// i.e. pluginHandle/pluginController/index or pluginHandle/pluginController/testAction
							$action = isset($actionSegs[2]) ? $actionSegs[2] : 'Index';

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
								$action = $actionSegs[1];

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
	public function createController($route, $owner = null)
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
				Craft::createComponent($class, $controllerId),
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
		if ($this->request->isResourceRequest())
		{
			// Get the path segments, except for the first one which we already know is "resources"
			$segs = array_slice(array_merge($this->request->getSegments()), 1);
			$path = implode('/', $segs);

			craft()->resources->sendResource($path);
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
			$messages[] = Craft::t('The database server name isn’t set in your db config file.');
		}

		if (StringHelper::isNullOrEmpty($databaseAuthName))
		{
			$messages[] = Craft::t('The database user name isn’t set in your db config file.');
		}

		if (StringHelper::isNullOrEmpty($databaseName))
		{
			$messages[] = Craft::t('The database name isn’t set in your db config file.');
		}

		if (StringHelper::isNullOrEmpty($databasePort))
		{
			$messages[] = Craft::t('The database port isn’t set in your db config file.');
		}

		if (StringHelper::isNullOrEmpty($databaseCharset))
		{
			$messages[] = Craft::t('The database charset isn’t set in your db config file.');
		}

		if (StringHelper::isNullOrEmpty($databaseCollation))
		{
			$messages[] = Craft::t('The database collation isn’t set in your db config file.');
		}

		if (!empty($messages))
		{
			throw new Exception(Craft::t('Database configuration errors: {errors}', array('errors' => implode(PHP_EOL, $messages))));
		}

		try
		{
			$connection = $this->db;
			if (!$connection)
			{
				$messages[] = Craft::t('There is a problem connecting to the database with the credentials supplied in your db config file.');
			}
		}
		// Most likely missing PDO in general or the specific database PDO driver.
		catch(\CDbException $e)
		{
			Craft::log($e->getMessage(), \CLogger::LEVEL_ERROR);
			$missingPdo = false;

			// TODO: Multi-db driver check.
			if (!extension_loaded('pdo'))
			{
				$missingPdo = true;
				$messages[] = Craft::t('@@@appName@@@ requires the PDO extension to operate.');
			}

			if (!extension_loaded('pdo_mysql'))
			{
				$missingPdo = true;
				$messages[] = Craft::t('@@@appName@@@ requires the PDO_MYSQL driver to operate.');
			}

			if (!$missingPdo)
			{
				Craft::log($e->getMessage(), \CLogger::LEVEL_ERROR);
				$messages[] = Craft::t('There is a problem connecting to the database with the credentials supplied in your db config file.');
			}
		}
		catch (\Exception $e)
		{
			Craft::log($e->getMessage(), \CLogger::LEVEL_ERROR);
			$messages[] = Craft::t('There is a problem connecting to the database with the credentials supplied in your db config file.');
		}

		if (!empty($messages))
		{
			throw new Exception(Craft::t('Database configuration errors: {errors}', array('errors' => implode(PHP_EOL, $messages))));
		}
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
				$this->_templatePath = CRAFT_TEMPLATES_PATH;
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

		if (craft()->config->get('devMode'))
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
		if(craft()->config->get('devMode'))
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

	/**
	 * Returns whether we are executing in the context on a console app.
	 *
	 * @return bool
	 */
	public function isConsole()
	{
		return false;
	}

	// Remap craft()->getSession() to craft()->httpSession and craft()->getUser() to craft->userSession

	/**
	 * @return HttpSessionService
	 */
	public function getSession()
	{
		return $this->getComponent('httpSession');
	}

	/**
	 * @return UserSessionService
	 */
	public function getUser()
	{
		return $this->getComponent('userSession');
	}
}
