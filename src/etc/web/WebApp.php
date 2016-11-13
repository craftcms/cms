<?php
namespace Craft;

/**
 * @property AssetIndexingService    $assetIndexing    The {@link AssetIndexingService assets indexing service}.
 * @property AssetSourcesService     $assetSources     The {@link AssetSourcesService assets sources service}.
 * @property AssetsService           $assets           The {@link AssetsService assets service}.
 * @property AssetTransformsService  $assetTransforms  The {@link AssetTransformsService assets sizes service}.
 * @property CacheService            $cache            The {@link CacheService cache service}.
 * @property CategoriesService       $categories       The {@link CategoriesService categories service}.
 * @property ComponentsService       $components       The {@link ComponentsService components service}.
 * @property ConfigService           $config           The {@link ConfigService config service}.
 * @property ContentService          $content          The {@link ContentService content service}.
 * @property DashboardService        $dashboard        The {@link DashboardService dashboard service}.
 * @property DbConnection            $db               The {@link DbConnection database connection}.
 * @property DeprecatorService       $deprecator       The {@link DeprecatorService deprecator service}.
 * @property ElementsService         $elements         The {@link ElementsService elements service}.
 * @property EmailMessagesService    $emailMessages    The {@link EmailMessagesService email messages service}.
 * @property EmailService            $email            The {@link EmailService email service}.
 * @property EntriesService          $entries          The {@link EntriesService entries service}.
 * @property EntryRevisionsService   $entryRevisions   The {@link EntryRevisionsService entry revisions service}.
 * @property EtService               $et               The {@link EtService E.T. service}.
 * @property FeedsService            $feeds            The {@link FeedsService feeds service}.
 * @property FieldsService           $fields           The {@link FieldsService fields service}.
 * @property FileCache               $fileCache        {@link FileCache File caching}.
 * @property GlobalsService          $globals          The {@link GlobalsService globals service}.
 * @property HttpRequestService      $request          The {@link HttpRequestService request service}.
 * @property HttpSessionService      $httpSession      The {@link HttpSessionService HTTP session service}.
 * @property ImagesService           $images           The {@link ImagesService images service}.
 * @property InstallService          $install          The {@link InstallService install service}.
 * @property LocalizationService     $localization     The {@link LocalizationService localization service}.
 * @property MatrixService           $matrix           The {@link MatrixService matrix service}.
 * @property MigrationsService       $migrations       The {@link MigrationsService migrations service}.
 * @property PathService             $path             The {@link PathService path service}.
 * @property PluginsService          $plugins          The {@link PluginsService plugins service}.
 * @property RelationsService        $relations        The {@link RelationsService relations service}.
 * @property ResourcesService        $resources        The {@link ResourcesService resources service}.
 * @property RoutesService           $routes           The {@link RoutesService routes service}.
 * @property SearchService           $search           The {@link SearchService search service}.
 * @property SectionsService         $sections         The {@link SectionsService sections service}.
 * @property SecurityService         $security         The {@link SecurityService security service}.
 * @property StructuresService       $structures       The {@link StructuresService structures service}.
 * @property SystemSettingsService   $systemSettings   The {@link SystemSettingsService system settings service}.
 * @property TagsService             $tags             The {@link TagsService tags service}.
 * @property TasksService            $tasks            The {@link TasksService tasks service}.
 * @property TemplateCacheService    $templateCache    The {@link TemplateCacheService template cache service}.
 * @property TemplatesService        $templates        The {@link TemplatesService template service}.
 * @property TokensService           $tokens           The {@link TokensService tokens service}.
 * @property UpdatesService          $updates          The {@link UpdatesService updates service}.
 * @property UserGroupsService       $userGroups       The {@link UserGroupsService user groups service}.
 * @property UserPermissionsService  $userPermissions  The {@link UserPermissionsService user permission service}.
 * @property UserSessionService      $userSession      The {@link UserSessionService user session service}.
 * @property UsersService            $users            The {@link UsersService users service}.
 *
 * @author    Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license   http://craftcms.com/license Craft License Agreement
 * @see       http://craftcms.com
 * @package   craft.app.etc.web
 * @since     1.0
 */
class WebApp extends \CWebApplication
{
	// Properties
	// =========================================================================

	/**
	 * The language that the application is written in. This mainly refers to the language that the messages and view
	 * files are in.
	 *
	 * Setting it here even though CApplication already defaults to 'en_us', so it's clear and in case they change it
	 * down the road.
	 *
	 * @var string
	 */
	public $sourceLanguage = 'en_us';

	/**
	 * List of built-in component aliases to be imported.
	 *
	 * @var array
	 */
	public $componentAliases;

	/**
	 * @var
	 */
	private $_editionComponents;

	/**
	 * @var
	 */
	private $_pendingEvents;

	// Public Methods
	// =========================================================================

	/**
	 * Initializes the application.
	 *
	 * @return null
	 */
	public function init()
	{
		// NOTE: Nothing that triggers a database connection should be made here until *after* _processResourceRequest()
		// in processRequest() is called.

		// Import all the built-in components
		foreach ($this->componentAliases as $alias)
		{
			Craft::import($alias);
		}

		// Attach our Craft app behavior.
		$this->attachBehavior('AppBehavior', new AppBehavior());

		// If there is a custom validationKey set, apply it here.
		if ($validationKey = $this->config->get('validationKey'))
		{
			$this->security->setValidationKey($validationKey);

			// Make sure any instances of Yii's CSecurityManager class are using the custom validation
			// key as well
			$this->getComponent('securityManager')->setValidationKey($validationKey);
		}

		// Attach our own custom Logger
		Craft::setLogger(new Logger());

		// If there is a custom appId set, apply it here.
		if ($appId = $this->config->get('appId'))
		{
			$this->setId($appId);
		}

		// Initialize Cache, HttpRequestService and LogRouter right away (order is important)
		$this->getComponent('cache');
		$this->getComponent('request');
		$this->getComponent('log');

		// So we can try to translate Yii framework strings
		$this->coreMessages->attachEventHandler('onMissingTranslation', array('Craft\LocalizationHelper', 'findMissingTranslation'));

		// Set our own custom runtime path.
		$this->setRuntimePath($this->path->getRuntimePath());

		// Set the edition components
		$this->_setEditionComponents();

		parent::init();
	}

	/**
	 * Processes the request.
	 *
	 * @throws HttpException
	 * @return null
	 */
	public function processRequest()
	{
		// If this is a resource request, we should respond with the resource ASAP.
		$this->_processResourceRequest();

		$configService = $this->config;

		// If we're not in devMode, or it's a 'dontExtendSession' request, we're going to remove some logging routes.
		if (!$configService->get('devMode') || (craft()->isInstalled() && !$this->userSession->shouldExtendSession()))
		{
			$this->log->removeRoute('WebLogRoute');
			$this->log->removeRoute('ProfileLogRoute');
		}

		// Additionally, we don't want these in the log files at all.
		if (craft()->isInstalled() && !$this->userSession->shouldExtendSession())
		{
			$this->log->removeRoute('FileLogRoute');
		}

		// If this is a CP request, prevent robots from indexing/following the page
		// (see https://developers.google.com/webmasters/control-crawl-index/docs/robots_meta_tag)
		if ($this->request->isCpRequest())
		{
			HeaderHelper::setHeader(array('X-Robots-Tag' => 'none'));
			HeaderHelper::setHeader(array('X-Frame-Options' => 'SAMEORIGIN'));
			HeaderHelper::setHeader(array('X-Content-Type-Options' => 'nosniff'));
		}

		// Send the X-Powered-By header?
		if ($configService->get('sendPoweredByHeader'))
		{
			HeaderHelper::setHeader(array('X-Powered-By' => 'Craft CMS'));
		}
		else
		{
			// In case PHP is already setting one
			HeaderHelper::removeHeader('X-Powered-By');
		}

		// Validate some basics on the database configuration file.
		$this->validateDbConfigFile();

		// Process install requests
		$this->_processInstallRequest();

		// If the system in is maintenance mode and it's a site request, throw a 503.
		if ($this->isInMaintenanceMode() && $this->request->isSiteRequest())
		{
			throw new HttpException(503);
		}

		// Check if the app path has changed.  If so, run the requirements check again.
		$this->_processRequirementsCheck();

		// Makes sure that the uploaded files are compatible with the current database schema
		if (!$this->updates->isSchemaVersionCompatible())
		{
			if ($this->request->isCpRequest())
			{
				$version = $this->getVersion();
                $url = AppHelper::getCraftDownloadUrl($version);

				throw new HttpException(200, Craft::t('Craft CMS does not support backtracking to this version. Please upload Craft CMS {url} or later.', array(
					'url' => '['.$version.']('.$url.')',
				)));
			}
			else
			{
				throw new HttpException(503);
			}
		}

		// isCraftDbMigrationNeeded will return true if we're in the middle of a manual or auto-update for Craft itself.
		// If we're in maintenance mode and it's not a site request, show the manual update template.
		if (
			$this->updates->isCraftDbMigrationNeeded() ||
			($this->isInMaintenanceMode() && $this->request->isCpRequest()) ||
			$this->request->getActionSegments() == array('update', 'cleanUp') ||
			$this->request->getActionSegments() == array('update', 'rollback')
		)
		{
			$this->_processUpdateLogic();
		}

		// If there's a new version, but the schema hasn't changed, just update the info table
		if ($this->updates->hasCraftVersionChanged())
		{
			$this->updates->updateCraftVersionInfo();

			// Clear the template caches in case they've been compiled since this release was cut.
			IOHelper::clearFolder($this->path->getCompiledTemplatesPath());
		}

		// If the system is offline, make sure they have permission to be here
		$this->_enforceSystemStatusPermissions();

		// Load the plugins
		$this->plugins->loadPlugins();

		// Check if a plugin needs to update the database.
		if ($this->updates->isPluginDbUpdateNeeded())
		{
			$this->_processUpdateLogic();
		}

		// If this is a non-login, non-validate, non-setPassword CP request, make sure the user has access to the CP
		if ($this->request->isCpRequest() && !($this->request->isActionRequest() && $this->_isSpecialCaseActionRequest()))
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

		// If this is an action request, call the controller
		$this->_processActionRequest();

		// If we're still here, finally let UrlManager do it's thing.
		parent::processRequest();
	}

	/**
	 * Returns the target application language.
	 *
	 * @return string
	 */
	public function getLanguage()
	{
		return $this->asa('AppBehavior')->getLanguage();
	}

	/**
	 * Sets the target application language.
	 *
	 * @param string $language
	 *
	 * @return null
	 */
	public function setLanguage($language)
	{
		$this->asa('AppBehavior')->setLanguage($language);
	}

	/**
	 * Returns the localization data for a given locale.
	 *
	 * @param string $localeId
	 *
	 * @return LocaleData
	 */
	public function getLocale($localeId = null)
	{
		return $this->i18n->getLocaleData($localeId);
	}

	/**
	 * Creates a controller instance based on a route.
	 *
	 * @param string $route
	 * @param mixed  $owner
	 *
	 * @return array|null
	 */
	public function createController($route, $owner = null)
	{
		if ((array)$route === $route || ($route = trim($route, '/')) === '')
		{
			$route = $this->defaultController;
		}

		$routeParts = array_filter(explode('/', $route));

		// First check if the controller class is a combination of the first two segments. That way FooController won't
		// steal all of Foo_BarController's requests.
		if (isset($routeParts[1]))
		{
			$controllerId = ucfirst($routeParts[0]).'_'.ucfirst($routeParts[1]);
			$class = __NAMESPACE__.'\\'.$controllerId.'Controller';

			if (class_exists($class))
			{
				$action = implode('/', array_slice($routeParts, 2));
			}
		}

		// If that didn't work, now look for that FooController.
		if (!isset($action))
		{
			$controllerId = ucfirst($routeParts[0]);
			$class = __NAMESPACE__.'\\'.$controllerId.'Controller';

			if (class_exists($class))
			{
				$action = implode('/', array_slice($routeParts, 1));
			}
		}

		// Did we find a valid controller?
		if (isset($action))
		{
			return array(
				Craft::createComponent($class, $controllerId),
				$this->parseActionParams($action),
			);
		}
	}

	/**
	 * Formats an exception into JSON before returning it to the client.
	 *
	 * @param array $data
	 *
	 * @return null
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
	 * @param int    $code    The error code.
	 * @param string $message The error message.
	 * @param string $file    The error file.
	 * @param string $line    The error line.
	 *
	 * @return null
	 */
	public function returnAjaxError($code, $message, $file, $line)
	{
		if($this->config->get('devMode'))
		{
			$outputTrace = '';
			$trace = debug_backtrace();

			// skip the first 3 stacks as they do not tell the error position
			if(count($trace) > 3)
			{
				$trace = array_slice($trace, 3);
			}

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

	/**
	 * Returns the {@link HttpSessionService} (craft()->httpSession).
	 *
	 * @return HttpSessionService
	 */
	public function getSession()
	{
		return $this->getComponent('httpSession');
	}

	/**
	 * Returns the {@link UserSessionService} (craft()->userSession).
	 *
	 * @return UserSessionService
	 */
	public function getUser()
	{
		return $this->getComponent('userSession');
	}

	/**
	 * Sets the application components.
	 *
	 * @param      $components
	 * @param bool $merge
	 *
	 * @return null
	 */
	public function setComponents($components, $merge = true)
	{
		if (isset($components['editionComponents']))
		{
			$this->_editionComponents = $components['editionComponents'];
			unset($components['editionComponents']);
		}

		parent::setComponents($components, $merge);
	}

	/**
	 * Attaches an event handler, or remembers it for later if the component has not been initialized yet.
	 *
	 * The event should be identified in a `serviceHandle.eventName` format. For example, if you want to add an event
	 * handler for {@link EntriesService::onSaveEntry()}, you would do this:
	 *
	 * ```php
	 * craft()->on('entries.saveEntry', function(Event $event) {
	 *     // ...
	 * });
	 * ```
	 *
	 * Note that the actual event name (`saveEntry`) does not need to include the “`on`”.
	 *
	 * By default, event handlers will not get attached if Craft is current in the middle of updating itself or a
	 * plugin. If you want the event to fire even in that condition, pass `true` to the $evenDuringUpdates argument.
	 *
	 * @param string $event             The event to listen for.
	 * @param mixed  $handler           The event handler.
	 * @param bool   $evenDuringUpdates Whether the event handler should be attached when Craft’s updater is running.
	 *                                  Default is `false`.
	 *
	 * @return null
	 */
	public function on($event, $handler, $evenDuringUpdates = false)
	{
		if (
			!$evenDuringUpdates &&
			$this->request->getActionSegments() == array('update', 'updateDatabase')
		)
		{
			return;
		}

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
	 * Override getComponent() so we can attach any pending events if the component is getting initialized as well as
	 * do some special logic around creating the `craft()->db` application component.
	 *
	 * @param string $id
	 * @param bool   $createIfNull
	 *
	 * @return mixed
	 */
	public function getComponent($id, $createIfNull = true)
	{
		$component = parent::getComponent($id, false);

		if (!$component && $createIfNull)
		{
			if ($id === 'db')
			{
				$dbConnection = $this->asa('AppBehavior')->createDbConnection();
				$this->setComponent('db', $dbConnection);
			}

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
	 *
	 * @return null
	 */
	public function setComponent($id, $component, $merge = true)
	{
		parent::setComponent($id, $component, $merge);
		$this->_attachEventListeners($id);
	}

	/**
	 * Returns the system time zone.  Note that this method cannot be in {@link AppBehavior}, because Yii will check
	 * {@link \CApplication::getTimeZone()} instead.
	 *
	 * @return string
	 */
	public function getTimeZone()
	{
		return $this->asa('AppBehavior')->getTimezone();
	}

	/**
	 * Tries to find a match between the browser's preferred locales and the locales Craft has been translated into.
	 *
	 * @return string
	 */
	public function getTranslatedBrowserLanguage()
	{
		$browserLanguages = $this->request->getBrowserLanguages();

		if ($browserLanguages)
		{
			$appLocaleIds = $this->i18n->getAppLocaleIds();

			foreach ($browserLanguages as $language)
			{
				if (in_array($language, $appLocaleIds))
				{
					return $language;
				}
			}
		}

		return false;
	}

	// Events
	// =========================================================================

	/**
	 * Fires an onEditionChange event.
	 *
	 * @param Event $event
	 *
	 * @throws \CException
	 */
	public function onEditionChange(Event $event)
	{
		$this->raiseEvent('onEditionChange', $event);
	}

	/**
	 * @todo Remove for Craft 3.
	 *
	 * @param int    $code The level of the error raised.
	 * @param string $message The error message.
	 * @param string $file The filename that the error was raised in.
	 * @param int    $line The line number the error was raised at.
	 */
	public function handleError($code, $message, $file, $line)
	{
		// PHP 7 turned some E_STRICT messages to E_WARNINGs. Code 2 is for all warnings
		// and since there are no messages specific codes we have to parse the string for what
		// we're looking for. Lame, but it works since all PHP error messages are always in English.
		// https://stackoverflow.com/questions/11556375/is-there-a-way-to-localize-phps-error-output
		if (version_compare(PHP_VERSION, '7', '>=') && $code === 2 && strpos($message, 'should be compatible with') !== false)
		{
			return;
		}

		parent::handleError($code, $message, $file, $line);
	}

	// Private Methods
	// =========================================================================

	/**
	 * Attaches any pending event listeners to the newly-initialized component.
	 *
	 * @param string $componentId
	 *
	 * @return null
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
	 * @throws HttpException
	 * @return null
	 */
	private function _processResourceRequest()
	{
		if ($this->request->isResourceRequest())
		{
			// Don't want to log anything on a resource request.
			$this->log->removeRoute('FileLogRoute');

			// Get the path segments, except for the first one which we already know is "resources"
			$segs = array_slice(array_merge($this->request->getSegments()), 1);
			$path = implode('/', $segs);

			$this->resources->sendResource($path);
		}
	}

	/**
	 * Sets the edition components.
	 *
	 * @return null
	 */
	private function _setEditionComponents()
	{
		// Set the appropriate edition components
		if (isset($this->_editionComponents))
		{
			foreach ($this->_editionComponents as $edition => $editionComponents)
			{
				if ($this->getEdition() >= $edition)
				{
					$this->setComponents($editionComponents);
				}
			}

			unset($this->_editionComponents);
		}
	}

	/**
	 * Processes install requests.
	 *
	 * @throws HttpException
	 * @return null
	 */
	private function _processInstallRequest()
	{
		$isCpRequest = $this->request->isCpRequest();

		// Are they requesting an installer template/action specifically?
		if ($isCpRequest && $this->request->getSegment(1) === 'install' && !$this->isInstalled())
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
		else if (!$this->isInstalled())
		{
			// Give it to them if accessing the CP
			if ($isCpRequest)
			{
				$url = UrlHelper::getUrl('install');
				$this->request->redirect($url);
			}
			// Otherwise return a 503
			else
			{
				throw new HttpException(503);
			}
		}
	}

	/**
	 * Processes action requests.
	 *
	 * @throws HttpException
	 * @return null
	 */
	private function _processActionRequest()
	{
		if ($this->request->isActionRequest())
		{
			$actionSegs = $this->request->getActionSegments();
			$route = implode('/', $actionSegs);
			$this->runController($route);
		}
	}

	/**
	 * @return bool
	 */
	private function _isSpecialCaseActionRequest()
	{
		$segments = $this->request->getActionSegments();

		if (
			$segments == array('users', 'login') ||
			$segments == array('users', 'logout') ||
			$segments == array('users', 'setpassword') ||
			$segments == array('users', 'forgotpassword') ||
			$segments == array('users', 'sendPasswordResetEmail') ||
			$segments == array('users', 'saveUser') ||
			$segments == array('users', 'getAuthTimeout')
		)
		{
			return true;
		}

		return false;
	}

	/**
	 * If there is not cached app path or the existing cached app path does not match the current one, let’s run the
	 * requirement checker again. This should catch the case where an install is deployed to another server that doesn’t
	 * meet Craft’s minimum requirements.
	 *
	 * @return null
	 */
	private function _processRequirementsCheck()
	{
		// See if we're in the middle of an update.
		$update = false;

		if ($this->request->getSegment(1) == 'updates' && $this->request->getSegment(2) == 'go')
		{
			$update = true;
		}

		if (($data = $this->request->getPost('data', null)) !== null && isset($data['handle']))
		{
			$update = true;
		}

		// Only run for CP requests and if we're not in the middle of an update.
		if ($this->request->isCpRequest() && !$update)
		{
			$cachedAppPath = $this->cache->get('appPath');
			$appPath = $this->path->getAppPath();

			if ($cachedAppPath === false || $cachedAppPath !== $appPath)
			{
				$this->runController('templates/requirementscheck');
			}
		}
	}

	/**
	 * @throws HttpException
	 * @return null
	 */
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
					$minVersionUrl = AppHelper::getCraftDownloadUrl(CRAFT_MIN_VERSION_REQUIRED);

					throw new HttpException(200, Craft::t('You need to be on at least Craft CMS {url} before you can manually update to Craft CMS {targetVersion}.', array(
						'url'           => '['.CRAFT_MIN_VERSION_REQUIRED.']('.$minVersionUrl.')',
						'targetVersion' => CRAFT_VERSION,
					)));
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

					// Clear the template caches in case they've been compiled since this release was cut.
					IOHelper::clearFolder($this->path->getCompiledTemplatesPath());

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
			// If an exception gets throw during the rendering of the 503 template, let
			// TemplatesController->actionRenderError() take care of it.
			throw new HttpException(503);
		}

		// <Gandalf> YOU SHALL NOT PASS!
		$this->end();
	}

	/**
	 * Checks if the system is off, and if it is, enforces the "Access the site/CP when the system is off" permissions.
	 *
	 * @throws HttpException
	 * @return null
	 */
	private function _enforceSystemStatusPermissions()
	{
		if (!$this->_checkSystemStatusPermissions())
		{
			$error = null;

			if ($this->userSession->isLoggedIn())
			{
				if ($this->request->isCpRequest())
				{
					$error = Craft::t('Your account doesn’t have permission to access the Control Panel when the system is offline.');
				}
				else
				{
					$error = Craft::t('Your account doesn’t have permission to access the site when the system is offline.');
				}

				$error .= ' ['.Craft::t('Log out?').']('.UrlHelper::getUrl(craft()->config->getLogoutPath()).')';
			}
			else
			{
				// If this is a CP request, redirect to the Login page
				if ($this->request->isCpRequest())
				{
					$this->userSession->requireLogin();
				}
			}

			throw new HttpException(503, $error);
		}
	}

	/**
	 * Returns whether the user has permission to be accessing the site/CP while it's offline, if it is.
	 *
	 * @return bool
	 */
	private function _checkSystemStatusPermissions()
	{
		if ($this->isSystemOn())
		{
			return true;
		}

		if ($this->request->isCpRequest() ||

			// Special case because we hide the cpTrigger in emails.
			$this->request->getPath() === craft()->config->get('actionTrigger').'/users/setpassword' ||
			$this->request->getPath() === craft()->config->get('actionTrigger').'/users/verifyemail' ||
			// Special case because this might be a request with a user that has "Access the site when the system is off"
			// permissions and is in the process of logging in while the system is off.
			$this->request->getActionSegments() == array('users', 'login')
		)
		{
			if ($this->userSession->checkPermission('accessCpWhenSystemIsOff'))
			{
				return true;
			}

			if ($this->request->getSegment(1) == 'manualupdate')
			{
				return true;
			}

			$actionSegs = $this->request->getActionSegments();

			if ($actionSegs && (
				$actionSegs == array('users', 'login') ||
				$actionSegs == array('users', 'logout') ||
				$actionSegs == array('users', 'forgotpassword') ||
				$actionSegs == array('users', 'sendPasswordResetEmail') ||
				$actionSegs == array('users', 'setpassword') ||
				$actionSegs == array('users', 'verifyemail') ||
				$actionSegs[0] == 'update'
			))
			{
				return true;
			}
		}
		else
		{
			if ($this->userSession->checkPermission('accessSiteWhenSystemIsOff'))
			{
				return true;
			}
		}

		return false;
	}
}
