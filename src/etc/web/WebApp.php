<?php
namespace Craft;

/**
 * @property AssetIndexingService        $assetIndexing        The assets indexing service
 * @property AssetSourcesService         $assetSources         The assets sources service
 * @property AssetsService               $assets               The assets service
 * @property AssetTransformsService      $assetTransforms      The assets sizes service
 * @property ComponentsService           $components           The components service
 * @property ConfigService               $config               The config service
 * @property ContentService              $content              The content service
 * @property DashboardService            $dashboard            The dashboard service
 * @property DbConnection                $db                   The database
 * @property ElementsService             $elements             The elements service
 * @property EmailMessagesService        $emailMessages        The email messages service
 * @property EmailService                $email                The email service
 * @property EntriesService              $entries              The entries service
 * @property EntryRevisionsService       $entryRevisions       The entry revisions service
 * @property EtService                   $et                   The E.T. service
 * @property FeedsService                $feeds                The feeds service
 * @property FieldsService               $fields               The fields service
 * @property GlobalsService              $globals              The globals service
 * @property HttpRequestService          $request              The request service
 * @property HttpSessionService          $httpSession          The HTTP session service
 * @property ImagesService               $images               The images service
 * @property InstallService              $install              The images service
 * @property LocalizationService         $localization         The localization service
 * @property MigrationsService           $migrations           The migrations service
 * @property PathService                 $path                 The path service
 * @property PluginsService              $plugins              The plugins service
 * @property RelationsService            $relations            The relations service
 * @property ResourcesService            $resources            The resources service
 * @property RoutesService               $routes               The routes service
 * @property SectionsService             $sections             The sections service
 * @property SecurityService             $security             The security service
 * @property SystemSettingsService       $systemSettings       The system settings service
 * @property TemplatesService            $templates            The template service
 * @property TagsService                 $tags                 The tags service
 * @property UpdatesService              $updates              The updates service
 * @property UserGroupsService           $userGroups           The user groups service
 * @property UserPermissionsService      $userPermissions      The user permission service
 * @property UserSessionService          $userSession          The user session service
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
	private $_isDbConfigValid = false;
	private $_pendingEvents;

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

		// Initialize HttpRequestService and LogRouter right away
		$this->getComponent('request');
		$this->getComponent('log');

		// Set our own custom runtime path.
		$this->setRuntimePath($this->path->getRuntimePath());

		// Attach our own custom Logger
		Craft::setLogger(new Logger());

		// If we're not in devMode or this is a resource request, we're going to remove some logging routes.
		if (!$this->config->get('devMode') || ($resourceRequest = $this->request->isResourceRequest()) == true)
		{
			// If it's a resource request, we don't want any logging routes, including craft.log
			// If it's not a resource request, we'll keep the FileLogRoute around.
			if ($resourceRequest)
			{
				$this->log->removeRoute('FileLogRoute');
			}

			// Don't need either of these if not in devMode or it's a resource request.
			$this->log->removeRoute('WebLogRoute');
			$this->log->removeRoute('ProfileLogRoute');
		}

		parent::init();
	}

	/**
	 * Returns the current target timezone.
	 *
	 * @return string
	 */
	public function getTimezone()
	{
		return Craft::getTimezone();
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

		// Set the target language
		$this->setLanguage($this->_getTargetLanguage());

		// Check if the app path has changed.  If so, run the requirements check again.
		$this->_processRequirementsCheck();

		// If the track has changed, put the brakes on the request.
		if (!$this->updates->isTrackValid())
		{
			if ($this->request->isCpRequest())
			{
				$this->runController('templates/invalidtrack');
				$this->end();
			}
			else
			{
				throw new HttpException(503);
			}
		}

		// isCraftDbUpdateNeeded will return true if we're in the middle of a manual or auto-update for Craft itself.
		// If we're in maintenance mode and it's not a site request, show the manual update template.
		if ($this->updates->isCraftDbUpdateNeeded() || (Craft::isInMaintenanceMode() && $this->request->isCpRequest()) || $this->request->getActionSegments() == array('update', 'cleanUp'))
		{
			$this->_processUpdateLogic();
		}

		// Make sure that the system is on, or that the user has permission to access the site/CP while the system is off
		if (Craft::isSystemOn() ||
			($this->request->isActionRequest() && $this->request->getActionSegments() == array('users', 'login')) ||
			($this->request->isSiteRequest() && $this->userSession->checkPermission('accessSiteWhenSystemIsOff')) ||
			($this->request->isCpRequest()) && $this->userSession->checkPermission('accessCpWhenSystemIsOff')
		)
		{
			// Set the package components
			$this->_setPackageComponents();

			// Check if a plugin needs to update the database.
			if ($this->updates->isPluginDbUpdateNeeded())
			{
				$this->_processUpdateLogic();
			}

			// If this is a non-login, non-validate, non-setPassword CP request, make sure the user has access to the CP
			if ($this->request->isCpRequest() && !($this->request->isActionRequest() && $this->_isValidActionRequest()))
			{
				// Make sure the user has access to the CP
				$this->userSession->requireLogin();
				$this->userSession->requirePermission('accessCp');

				// If they're accessing a plugin's section, make sure that they have permission to do so
				$firstSeg = $this->request->getSegment(1);
				if ($firstSeg)
				{
					$plugin = $plugin = $this->plugins->getPlugin($firstSeg);
					if ($plugin)
					{
						$this->userSession->requirePermission('accessPlugin-'.$plugin->getClassHandle());
					}
				}
			}

			// Load the plugins
			$this->plugins;

			// If this is an action request, call the controller
			$this->_processActionRequest();

			// If we're still here, finally let UrlManager do it's thing.
			parent::processRequest();
		}
		else
		{
			// Log out the user
			if ($this->userSession->isLoggedIn())
			{
				$this->userSession->logout(false);
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
	 * Creates a controller instance based on a route.
	 */
	public function createController($route, $owner = null)
	{
		if (($route = trim($route, '/')) === '')
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
	 * Returns whether the current db configuration is valid.
	 *
	 * @return bool
	 */
	public function isDbConfigValid()
	{
		return $this->_isDbConfigValid;
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

		if ($this->config->get('devMode'))
		{
			$exceptionArr['trace']  = $data['trace'];
			$exceptionArr['traces'] = (isset($data['traces']) ? $data['traces'] : null);
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
		if($this->config->get('devMode'))
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

	// Remap $this->getSession() to $this->httpSession and $this->getUser() to craft->userSession

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
	 * Attaches an event listener, or remembers it for later if the component has not been initialized yet.
	 *
	 * @param string $event
	 * @param mixed  $handler
	 */
	public function on($event, $handler)
	{
		list($componentId, $eventName) = explode('.', $event, 2);

		$component = $this->getComponent($componentId, false);

		// Normalize the event name
		if (strncmp($eventName, 'on', 2) !== 0)
		{
			$eventName = 'on'.ucfirst($eventName);
		}

		if ($component)
		{
			$component->$eventName = $handler;
		}
		else
		{
			$this->_pendingEvents[$componentId][$eventName][] = $handler;
		}
	}

	/**
	 * Override getComponent() so we can attach any pending events if the component is getting initialized.
	 *
	 * @param string $id
	 * @param boolean $createIfNull
	 * @return mixed
	 */
	public function getComponent($id, $createIfNull = true)
	{
		$component = parent::getComponent($id, false);

		if (!$component && $createIfNull)
		{
			$component = parent::getComponent($id, true);
			$this->_attachEventListeners($id);
		}

		return $component;
	}

	/**
	 * Override setComponent so we can attach any pending events.
	 *
	 * @param string $id
	 * @param mixed  $component
	 * @param bool   $merge
	 */
	public function setComponent($id, $component, $merge = true)
	{
		parent::setComponent($id, $component, $merge);
		$this->_attachEventListeners($id);
	}

	/**
	 * Attaches any pending event listeners to the newly-initialized component.
	 *
	 * @access private
	 * @param string $componentId
	 */
	private function _attachEventListeners($componentId)
	{
		if (isset($this->_pendingEvents[$componentId]))
		{
			$component = $this->getComponent($componentId, false);

			if ($component)
			{
				foreach ($this->_pendingEvents[$componentId] as $eventName => $handlers)
				{
					foreach ($handlers as $handler)
					{
						$component->$eventName = $handler;
					}
				}
			}
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

			$this->resources->sendResource($path);
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
			throw new DbConnectException(Craft::t('Database configuration errors: {errors}', array('errors' => implode(PHP_EOL, $messages))));
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
			Craft::log($e->getMessage(), LogLevel::Error);
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
				Craft::log($e->getMessage(), LogLevel::Error);
				$messages[] = Craft::t('There is a problem connecting to the database with the credentials supplied in your db config file.');
			}
		}
		catch (\Exception $e)
		{
			Craft::log($e->getMessage(), LogLevel::Error);
			$messages[] = Craft::t('There is a problem connecting to the database with the credentials supplied in your db config file.');
		}

		if (!empty($messages))
		{
			throw new DbConnectException(Craft::t('Database configuration errors: {errors}', array('errors' => implode(PHP_EOL, $messages))));
		}

		$this->_isDbConfigValid = true;
	}

	/**
	 * Sets the package components.
	 */
	private function _setPackageComponents()
	{
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
		else if ($isCpRequest && $this->request->isActionRequest() && ($this->request->getSegment(1) !== 'login'))
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
		// CP requests should get "auto" by default
		if ($this->request->isCpRequest() && !defined('CRAFT_LOCALE'))
		{
			define('CRAFT_LOCALE', 'auto');
		}

		if (defined('CRAFT_LOCALE'))
		{
			$locale = strtolower(CRAFT_LOCALE);

			// Get the list of actual site locale IDs
			$siteLocaleIds = $this->i18n->getSiteLocaleIds();

			// Is it set to "auto"?
			if ($locale == 'auto')
			{
				// If the user is logged in *and* has a primary language set, use that
				$user = $this->userSession->getUser();

				if ($user && $user->preferredLocale)
				{
					return $user->preferredLocale;
				}

				// Otherwise check if the browser's preferred language matches any of the site locales
				$browserLanguages = $this->request->getBrowserLanguages();

				if ($browserLanguages)
				{
					foreach ($browserLanguages as $language)
					{
						if (in_array($language, $siteLocaleIds))
						{
							return $language;
						}
					}
				}
			}

			// Is it set to a valid site locale?
			else if (in_array($locale, $siteLocaleIds))
			{
				return $locale;
			}
		}

		// Use the primary site locale by default
		return $this->i18n->getPrimarySiteLocaleId();
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
					return;
				}
				else
				{
					// Mayhaps this is a plugin action request.
					$plugin = strtolower($actionSegs[0]);

					if (($plugin = $this->plugins->getPlugin($plugin)) !== null)
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
							return;
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
								return;
							}
						}
					}
				}
			}

			throw new HttpException(404);
		}
	}

	/**
	 * @return bool
	 */
	private function _isValidActionRequest()
	{
		if (
			$this->request->getActionSegments() == array('users', 'login') ||
			$this->request->getActionSegments() == array('users', 'validate') ||
			$this->request->getActionSegments() == array('users', 'setPassword') ||
			$this->request->getActionSegments() == array('users', 'forgotPassword') ||
			$this->request->getActionSegments() == array('users', 'saveUser'))
		{
			return true;
		}

		return false;
	}

	/**
	 * If there is not cached app path or the existing cached app path does not match the current one, let’s run the requirement checker again.
	 * This should catch the case where an install is deployed to another server that doesn’t meet Craft’s minimum requirements.
	 */
	private function _processRequirementsCheck()
	{
		$cachedAppPath = craft()->fileCache->get('appPath');
		$appPath = $this->path->getAppPath();

		if ($cachedAppPath === false || $cachedAppPath !== $appPath)
		{
			$this->runController('templates/requirementscheck');
		}
	}

	private function _processUpdateLogic()
	{
		// Let all non-action CP requests through.
		if (
			$this->request->isCpRequest() &&
			(!$this->request->isActionRequest() || $this->request->getActionSegments() == array('users', 'login'))
		)
		{
			// If this is a request to actually manually update Craft, do it
			if ($this->request->getSegment(1) == 'manualupdate')
			{
				$this->runController('templates/manualUpdate');
				$this->end();
			}
			else
			{
				if ($this->updates->isBreakpointUpdateNeeded())
				{
					// Load the breakpoint update template
					$this->runController('templates/breakpointUpdateNotification');
				}
				else
				{
					if (!$this->request->isAjaxRequest())
					{
						if ($this->request->getPathInfo() !== '')
						{
							$this->userSession->setReturnUrl($this->request->getPath());
						}
					}

					// Show the manual update notification template
					$this->runController('templates/manualUpdateNotification');
				}
			}
		}
		// We'll also let action requests to UpdateController through as well.
		else if ($this->request->isActionRequest() && (($actionSegs = $this->request->getActionSegments()) !== null) && isset($actionSegs[0]) && $actionSegs[0] == 'update')
		{
			$controller = $actionSegs[0];
			$action = isset($actionSegs[1]) ? $actionSegs[1] : 'index';
			$this->runController($controller.'/'.$action);
		}
		else
		{
			throw new HttpException(503);
		}

		// YOU SHALL NOT PASS
		$this->end();
	}
}
