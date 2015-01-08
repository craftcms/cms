<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2013 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\web;

use craft\app\Craft;
use craft\app\errors\HttpException;
use craft\app\helpers\HeaderHelper;
use craft\app\helpers\JsonHelper;
use craft\app\helpers\UrlHelper;
use craft\app\i18n\LocaleData;
use craft\app\logging\Logger;

/**
 * Craft Web Application class
 *
 * @property \craft\app\services\AssetIndexing    $assetIndexing    The [[\craft\app\services\AssetIndexing assets indexing service]].
 * @property \craft\app\services\AssetSources     $assetSources     The [[\craft\app\services\AssetSources assets sources service]].
 * @property \craft\app\services\Assets           $assets           The [[\craft\app\services\Assets assets service]].
 * @property \craft\app\services\AssetTransforms  $assetTransforms  The [[\craft\app\services\AssetTransforms assets sizes service]].
 * @property \craft\app\services\Cache            $cache            The [[\craft\app\services\Cache cache service]].
 * @property \craft\app\services\Categories       $categories       The [[\craft\app\services\Categories categories service]].
 * @property \craft\app\services\Components       $components       The [[\craft\app\services\Components components service]].
 * @property \craft\app\services\Config           $config           The [[\craft\app\services\Config config service]].
 * @property \craft\app\services\Content          $content          The [[\craft\app\services\Content content service]].
 * @property \craft\app\services\Dashboard        $dashboard        The [[\craft\app\services\Dashboard dashboard service]].
 * @property \craft\app\db\DbConnection           $db               The [[\craft\app\db\DbConnection database connection]].
 * @property \craft\app\services\Deprecator       $deprecator       The [[\craft\app\services\Deprecator deprecator service]].
 * @property \craft\app\services\Elements         $elements         The [[\craft\app\services\Elements elements service]].
 * @property \craft\app\services\EmailMessages    $emailMessages    The [[\craft\app\services\EmailMessages email messages service]].
 * @property \craft\app\services\Email            $email            The [[\craft\app\services\Email email service]].
 * @property \craft\app\services\Entries          $entries          The [[\craft\app\services\Entries entries service]].
 * @property \craft\app\services\EntryRevisions   $entryRevisions   The [[\craft\app\services\EntryRevisions entry revisions service]].
 * @property \craft\app\services\Et               $et               The [[\craft\app\services\Et E.T. service]].
 * @property \craft\app\services\Feeds            $feeds            The [[\craft\app\services\Feeds feeds service]].
 * @property \craft\app\services\Fields           $fields           The [[\craft\app\services\Fields fields service]].
 * @property \craft\app\cache\FileCache           $fileCache        [[\craft\app\cache\FileCache File caching]].
 * @property \craft\app\services\Globals          $globals          The [[\craft\app\services\Globals globals service]].
 * @property \craft\app\services\HttpRequest      $request          The [[\craft\app\services\HttpRequest request service]].
 * @property \craft\app\services\Images           $images           The [[\craft\app\services\Images images service]].
 * @property \craft\app\services\Install          $install          The [[\craft\app\services\Install install service]].
 * @property \craft\app\services\Localization     $localization     The [[\craft\app\services\Localization localization service]].
 * @property \craft\app\services\Matrix           $matrix           The [[\craft\app\services\Matrix matrix service]].
 * @property \craft\app\services\Migrations       $migrations       The [[\craft\app\services\Migrations migrations service]].
 * @property \craft\app\services\Path             $path             The [[\craft\app\services\Path path service]].
 * @property \craft\app\services\Plugins          $plugins          The [[\craft\app\services\Plugins plugins service]].
 * @property \craft\app\services\Relations        $relations        The [[\craft\app\services\Relations relations service]].
 * @property \craft\app\services\Resources        $resources        The [[\craft\app\services\Resources resources service]].
 * @property \craft\app\services\Routes           $routes           The [[\craft\app\services\Routes routes service]].
 * @property \craft\app\services\Search           $search           The [[\craft\app\services\Search search service]].
 * @property \craft\app\services\Sections         $sections         The [[\craft\app\services\Sections sections service]].
 * @property \craft\app\services\Security         $security         The [[\craft\app\services\Security security service]].
 * @property \craft\app\services\Structures       $structures       The [[\craft\app\services\Structures structures service]].
 * @property \craft\app\services\SystemSettings   $systemSettings   The [[\craft\app\services\SystemSettings system settings service]].
 * @property \craft\app\services\Tags             $tags             The [[\craft\app\services\Tags tags service]].
 * @property \craft\app\services\Tasks            $tasks            The [[\craft\app\services\Tasks tasks service]].
 * @property \craft\app\services\TemplateCache    $templateCache    The [[\craft\app\services\TemplateCache template cache service]].
 * @property \craft\app\services\Templates        $templates        The [[\craft\app\services\Templates template service]].
 * @property \craft\app\services\Tokens           $tokens           The [[\craft\app\services\Tokens tokens service]].
 * @property \craft\app\services\Updates          $updates          The [[\craft\app\services\Updates updates service]].
 * @property \craft\app\services\UserGroups       $userGroups       The [[\craft\app\services\UserGroups user groups service]].
 * @property \craft\app\services\UserPermissions  $userPermissions  The [[\craft\app\services\UserPermissions user permission service]].
 * @property \craft\app\services\Users            $users            The [[\craft\app\services\Users users service]].
 * @property \craft\app\web\Session               $session          The [[\craft\app\web\Session HTTP session service]].
 * @property \craft\app\web\User                  $user             The [[\craft\app\web\User user session service]].
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class Application extends \yii\web\Application
{
	// Traits
	// =========================================================================

	use \craft\app\base\ApplicationTrait;

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

		// Initialize the Cache service, HttpRequest service and LogRouter right away (order is important)
		$this->getComponent('cache');
		$this->getComponent('request');

		// Attach our own custom Logger
		Craft::setLogger(new Logger());

		$this->getComponent('log');

		// So we can try to translate Yii framework strings
		$this->coreMessages->attachEventHandler('onMissingTranslation', ['Craft\LocalizationHelper', 'findMissingTranslation']);

		// Set our own custom runtime path.
		$this->setRuntimePath($this->path->getRuntimePath());

		// If there is a custom appId set, apply it here.
		if ($appId = $this->config->get('appId'))
		{
			$this->setId($appId);
		}

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
		// If this is a resource request, we should respond with the resource ASAP
		$this->_processResourceRequest();

		// If we're not in devMode, or it's a 'dontEnableSession' request, we're going to remove some logging routes.
		if (!$this->config->get('devMode') || (Craft::$app->isInstalled() && !$this->getUser()->shouldExtendSession()))
		{
			$this->log->removeRoute('WebLogRoute');
			$this->log->removeRoute('ProfileLogRoute');
		}

		// Additionally, we don't want these in the log files at all.
		if (Craft::$app->isInstalled() && !$this->getUser()->shouldExtendSession())
		{
			$this->log->removeRoute('FileLogRoute');
		}

		// If this is a CP request, prevent robots from indexing/following the page
		// (see https://developers.google.com/webmasters/control-crawl-index/docs/robots_meta_tag)
		if ($this->request->isCpRequest())
		{
			HeaderHelper::setHeader(['X-Robots-Tag' => 'none']);
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

		// Makes sure that the uploaded files are compatible with the current DB schema
		if (!$this->updates->isSchemaVersionCompatible())
		{
			if ($this->request->isCpRequest())
			{
				$version = $this->getVersion();
				$build = $this->getBuild();
				$url = "http://download.buildwithcraft.com/craft/{$version}/{$version}.{$build}/Craft-{$version}.{$build}.zip";

				throw new HttpException(200, Craft::t('@@@appName@@@ does not support backtracking to this version. Please upload @@@appName@@@ {url} or later.', [
					'url' => '['.$build.']('.$url.')',
				]));
			}
			else
			{
				throw new HttpException(503);
			}
		}

		// Set the edition components
		$this->_setEditionComponents();

		// isCraftDbMigrationNeeded will return true if we're in the middle of a manual or auto-update for Craft itself.
		// If we're in maintenance mode and it's not a site request, show the manual update template.
		if (
			$this->updates->isCraftDbMigrationNeeded() ||
			($this->isInMaintenanceMode() && $this->request->isCpRequest()) ||
			$this->request->getActionSegments() == ['update', 'cleanUp'] ||
			$this->request->getActionSegments() == ['update', 'rollback']
		)
		{
			$this->_processUpdateLogic();
		}

		// If there's a new version, but the schema hasn't changed, just update the info table
		if ($this->updates->hasCraftBuildChanged())
		{
			$this->updates->updateCraftVersionInfo();
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
			$user = $this->getUser();

			// Make sure the user has access to the CP
			if ($user->getIsGuest())
			{
				$user->loginRequired();
			}

			if (!$user->checkPermission('accessCp'))
			{
				throw new HttpException(403);
			}

			// If they're accessing a plugin's section, make sure that they have permission to do so
			$firstSeg = $this->request->getSegment(1);

			if ($firstSeg)
			{
				$plugin = $plugin = $this->plugins->getPlugin($firstSeg);

				if ($plugin)
				{
					if (!$user->checkPermission('accessPlugin-'.$plugin->getClassHandle()))
					{
						throw new HttpException(403);
					}
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
		return $this->_getLanguage();
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
		$this->_setLanguage($language);
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
		if (($route = trim($route, '/')) === '')
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
			return [
				Craft::createComponent($class, $controllerId),
				$this->parseActionParams($action),
			];
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

			$errorArr = [
				'error' => $code.' : '.$message,
				'trace' => $outputTrace,
				'file'  => $file,
				'line'  => $line,
			];
		}
		else
		{
			$errorArr = ['error' => $message];
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
	 * Returns the [[\craft\app\web\Session]] (Craft::$app->getSession()).
	 *
	 * @return \craft\app\web\Session
	 */
	public function getSession()
	{
		return $this->getComponent('httpSession');
	}

	/**
	 * Returns the [[\craft\app\web\User]] (Craft::$app->getUser()).
	 *
	 * @return \craft\app\web\User
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
	 * handler for [[\craft\app\services\Entries::onSaveEntry()]], you would do this:
	 *
	 * ```php
	 * Craft::$app->on('entries.saveEntry', function(Event $event) {
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
			$this->request->getActionSegments() == ['update', 'updateDatabase']
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
	 * do some special logic around creating the `Craft::$app->db` application component.
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
				$dbConnection = $this->_createDbConnection();
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
	 * Returns the system time zone.
	 *
	 * Note that this method cannot be in [[\craft\app\base\ApplicationTrait]], because Yii will check
	 * [[\yii\base\Application::getTimeZone()]] instead.
	 *
	 * @return string
	 */
	public function getTimeZone()
	{
		return $this->_getTimeZone();
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
			// Otherwise return a 404
			else
			{
				throw new HttpException(404);
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
			$segments == ['users', 'login'] ||
			$segments == ['users', 'logout'] ||
			$segments == ['users', 'setpassword'] ||
			$segments == ['users', 'forgotpassword'] ||
			$segments == ['users', 'sendPasswordResetEmail'] ||
			$segments == ['users', 'saveUser'] ||
			$segments == ['users', 'getRemainingSessionTime']
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
				// Flush the data cache, so we're not getting cached CP resource paths.
				Craft::$app->cache->flush();

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
			(!$this->request->isActionRequest() || $this->request->getActionSegments() == ['users', 'login'])
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
					throw new HttpException(200, Craft::t('You need to be on at least @@@appName@@@ {url} before you can manually update to @@@appName@@@ {targetVersion} build {targetBuild}.', [
						'url'           => '<a href="'.CRAFT_MIN_BUILD_URL.'">build '.CRAFT_MIN_BUILD_REQUIRED.'</a>',
						'targetVersion' => CRAFT_VERSION,
						'targetBuild'   => CRAFT_BUILD
					]));
				}
				else
				{
					if (!$this->request->isAjaxRequest())
					{
						if ($this->request->getPathInfo() !== '')
						{
							$this->getUser()->setReturnUrl($this->request->getPath());
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

			if ($this->getUser()->isLoggedIn())
			{
				if ($this->request->isCpRequest())
				{
					$error = Craft::t('Your account doesn’t have permission to access the Control Panel when the system is offline.');
				}
				else
				{
					$error = Craft::t('Your account doesn’t have permission to access the site when the system is offline.');
				}

				$error .= ' ['.Craft::t('Log out?').']('.UrlHelper::getUrl(Craft::$app->config->getLogoutPath()).')';
			}
			else
			{
				// If this is a CP request, redirect to the Login page
				if ($this->request->isCpRequest())
				{
					$this->getUser()->requireLogin();
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
			$this->request->getPath() === Craft::$app->config->get('actionTrigger').'/users/setpassword' ||
			$this->request->getPath() === Craft::$app->config->get('actionTrigger').'/users/verifyemail'
		)
		{
			if ($this->getUser()->checkPermission('accessCpWhenSystemIsOff'))
			{
				return true;
			}

			if ($this->request->getSegment(1) == 'manualupdate')
			{
				return true;
			}

			$actionSegs = $this->request->getActionSegments();

			if ($actionSegs && (
				$actionSegs == ['users', 'login'] ||
				$actionSegs == ['users', 'logout'] ||
				$actionSegs == ['users', 'forgotpassword'] ||
				$actionSegs == ['users', 'sendPasswordResetEmail'] ||
				$actionSegs == ['users', 'setpassword'] ||
				$actionSegs == ['users', 'verifyemail'] ||
				$actionSegs[0] == 'update'
			))
			{
				return true;
			}
		}
		else
		{
			if ($this->getUser()->checkPermission('accessSiteWhenSystemIsOff'))
			{
				return true;
			}
		}

		return false;
	}
}
