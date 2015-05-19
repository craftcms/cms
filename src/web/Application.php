<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2015 Pixel & Tonic, Inc.
 * @license http://buildwithcraft.com/license
 */

namespace craft\app\web;

use Craft;
use craft\app\base\ApplicationTrait;
use craft\app\errors\HttpException;
use craft\app\helpers\HeaderHelper;
use craft\app\helpers\JsonHelper;
use craft\app\helpers\StringHelper;
use craft\app\helpers\UrlHelper;
use yii\base\InvalidRouteException;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;

/**
 * Craft Web Application class
 *
 * @property Request                              $request          The request component
 * @property Response                             $response         The response component
 * @property Session                              $session          The session component
 * @property UrlManager                           $urlManager       The URL manager for this application
 * @property User                                 $user             The user component
 *
 * @method Request                                getRequest()      Returns the request component.
 * @method Response                               getResponse()     Returns the response component.
 * @method Session                                getSession()      Returns the session component.
 * @method UrlManager                             getUrlManager()   Returns the URL manager for this application.
 * @method User                                   getUser()         Returns the user component.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class Application extends \yii\web\Application
{
	// Traits
	// =========================================================================

	use ApplicationTrait;

	// Public Methods
	// =========================================================================

	/**
	 * Constructor.
	 *
	 * @param array $config
	 */
	public function __construct($config = [])
	{
		Craft::$app = $this;
		parent::__construct($config);
	}

	/**
	 * Initializes the application.
	 *
	 * @return null
	 */
	public function init()
	{
		parent::init();

		// NOTE: Nothing that triggers a database connection should be made here until *after* _processResourceRequest()
		// in handleRequest() is called.

		// Initialize the Cache service, Request and Logger right away (order is important)
		$this->getCache();
		$this->getRequest();
		$this->processLogTargets();

		// So we can try to translate Yii framework strings
		//$this->coreMessages->attachEventHandler('onMissingTranslation', ['Craft\LocalizationHelper', 'findMissingTranslation']);

		// If there is a custom appId set, apply it here.
		if ($appId = $this->getConfig()->get('appId'))
		{
			$this->setId($appId);
		}

		// Set the timezone
		$this->_setTimeZone();

		// Validate some basics on the database configuration file.
		$this->validateDbConfigFile();

		// Load the plugins
		$this->getPlugins()->loadPlugins();

		// Set the language
		$this->_setLanguage();
	}

	/**
	 * Handles the specified request.
	 *
	 * @param Request $request the request to be handled
	 *
	 * @return \yii\web\Response the resulting response
	 * @throws HttpException
	 * @throws ServiceUnavailableHttpException
	 * @throws \craft\app\errors\DbConnectException
	 * @throws ForbiddenHttpException
	 * @throws \yii\web\NotFoundHttpException
	 */
	public function handleRequest($request)
	{
		// If this is a resource request, we should respond with the resource ASAP
		$this->_processResourceRequest();

		if ($request->getIsCpRequest())
		{
			// Prevent robots from indexing/following the page
			// (see https://developers.google.com/webmasters/control-crawl-index/docs/robots_meta_tag)
			HeaderHelper::setHeader(['X-Robots-Tag' => 'none']);
			// Prevent some possible XSS attack vectors
			HeaderHelper::setHeader(['X-Frame-Options' => 'SAMEORIGIN']);
			HeaderHelper::setHeader(['X-Content-Type-Options' => 'nosniff']);
		}

		HeaderHelper::setHeader(array('X-Powered-By' => 'Craft CMS'));

		// If the system in is maintenance mode and it's a site request, throw a 503.
		if ($this->isInMaintenanceMode() && $request->getIsSiteRequest())
		{
			$this->_unregisterDebugModule();
			throw new ServiceUnavailableHttpException();
		}

		// Process install requests
		if (($response = $this->_processInstallRequest($request)) !== null)
		{
			return $response;
		}

		// Check if the app path has changed.  If so, run the requirements check again.
		if (($response = $this->_processRequirementsCheck($request)) !== null)
		{
			$this->_unregisterDebugModule();
			return $response;
		}

		// Makes sure that the uploaded files are compatible with the current DB schema
		if (!$this->getUpdates()->isSchemaVersionCompatible())
		{
			$this->_unregisterDebugModule();

			if ($request->getIsCpRequest())
			{
				$version = $this->getInfo('version');
				$build = $this->getInfo('build');
				$url = "http://download.buildwithcraft.com/craft/{$version}/{$version}.{$build}/Craft-{$version}.{$build}.zip";

				throw new HttpException(200, Craft::t('app', 'Craft does not support backtracking to this version. Please upload Craft {url} or later.', [
					'url' => '['.$build.']('.$url.')',
				]));
			}
			else
			{
				throw new ServiceUnavailableHttpException();
			}
		}

		// Set the edition components
		$this->_setEditionComponents();

		// isCraftDbMigrationNeeded will return true if we're in the middle of a manual or auto-update for Craft itself.
		// If we're in maintenance mode and it's not a site request, show the manual update template.
		if ($this->_isCraftUpdating())
		{
			return $this->_processUpdateLogic($request) ?: $this->getResponse();
		}

		// If there's a new version, but the schema hasn't changed, just update the info table
		if ($this->getUpdates()->hasCraftBuildChanged())
		{
			$this->getUpdates()->updateCraftVersionInfo();
		}

		// If the system is offline, make sure they have permission to be here
		$this->_enforceSystemStatusPermissions($request);

		// Check if a plugin needs to update the database.
		if ($this->getUpdates()->isPluginDbUpdateNeeded())
		{
			return $this->_processUpdateLogic($request) ?: $this->getResponse();
		}

		// If this is a non-login, non-validate, non-setPassword CP request, make sure the user has access to the CP
		if ($request->getIsCpRequest() && !($request->getIsActionRequest() && $this->_isSpecialCaseActionRequest($request)))
		{
			$user = $this->getUser();

			// Make sure the user has access to the CP
			if ($user->getIsGuest())
			{
				return $user->loginRequired();
			}

			if (!$user->checkPermission('accessCp'))
			{
				throw new ForbiddenHttpException();
			}

			// If they're accessing a plugin's section, make sure that they have permission to do so
			$firstSeg = $request->getSegment(1);

			if ($firstSeg)
			{
				$plugin = $plugin = $this->getPlugins()->getPlugin($firstSeg);

				if ($plugin)
				{
					if (!$user->checkPermission('accessPlugin-'.$plugin->getHandle()))
					{
						throw new ForbiddenHttpException();
					}
				}
			}
		}

		// If this is an action request, call the controller
		if (($response = $this->_processActionRequest($request)) !== null)
		{
			return $response;
		}

		// If we're still here, finally let Yii do it's thing.
		return parent::handleRequest($request);
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

		if ($this->getConfig()->get('devMode'))
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
		if($this->getConfig()->get('devMode'))
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
	 * @inheritdoc
	 */
	public function get($id, $throwException = true)
	{
		if (!$this->has($id, true))
		{
			if (($definition = $this->_getComponentDefinition($id)) !== null)
			{
				$this->set($id, $definition);
			}
		}

		return parent::get($id, $throwException);
	}

	/**
	 * Tries to find a match between the browser's preferred locales and the locales Craft has been translated into.
	 *
	 * @return string
	 */
	public function getTranslatedBrowserLanguage()
	{
		$browserLanguages = $this->getRequest()->getAcceptableLanguages();

		if ($browserLanguages)
		{
			$appLocaleIds = $this->getI18n()->getAppLocaleIds();

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

	/**
	 * @inheritdoc
	 *
	 * @param string $route
	 * @return array|boolean
	 * @throws \yii\base\InvalidConfigException
	 */
	public function createController($route)
	{
		// Convert Yii 1-styled routes to Yii 2, and log them as deprecation errors
		if (StringHelper::hasUpperCase($route))
		{
			$requestedRoute = $route;
			$parts = preg_split('/(?=[\p{Lu}])+/u', $route);
			$route = StringHelper::toLowerCase(implode('-', $parts));
			$this->getDeprecator()->log('yii1-route', 'A Yii 1-styled route was requested: "'.$requestedRoute.'". It should be changed to: "'.$route.'".');
		}

		return parent::createController($route);
	}

	/**
	 * @inheritdoc
	 * @return \yii\web\Response|null The result of the action, normalized into a Response object
	 */
	public function runAction($route, $params = [])
	{
		$result = parent::runAction($route, $params);

		if ($result !== null)
		{
			if ($result instanceof \yii\web\Response)
			{
				return $result;
			}
			else
			{
				$response = $this->getResponse();
				$response->data = $result;
				return $response;
			}
		}

		return null;
	}

	// Private Methods
	// =========================================================================

	/**
	 * Unregisters the Debug module's end body event.
	 */
	private function _unregisterDebugModule()
	{
		$debug = $this->getModule('debug', false);

		if ($debug !== null)
		{
			$this->getView()->off(View::EVENT_END_BODY, [$debug, 'renderToolbar']);
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
		$request = $this->getRequest();

		if ($request->getIsResourceRequest())
		{
			// Get the path segments, except for the first one which we already know is "resources"
			$segs = array_slice(array_merge($request->getSegments()), 1);
			$path = implode('/', $segs);

			$this->getResources()->sendResource($path);
		}
	}

	/**
	 * Processes install requests.
	 *
	 * @param Request $request
	 *
	 * @return \yii\web\Response|null
	 * @throws NotFoundHttpException
	 * @throws \yii\base\ExitException
	 */
	private function _processInstallRequest($request)
	{
		$isCpRequest = $request->getIsCpRequest();
		$isInstalled = $this->isInstalled();

		if (!$isInstalled)
		{
			$this->_unregisterDebugModule();
		}

		// Are they requesting an installer template/action specifically?
		if ($isCpRequest && $request->getSegment(1) === 'install' && !$isInstalled)
		{
			$action = $request->getSegment(2, 'index');
			return $this->runAction('install/'.$action);
		}
		else if ($isCpRequest && $request->getIsActionRequest() && ($request->getSegment(1) !== 'login'))
		{
			$actionSegs = $request->getActionSegments();
			if (isset($actionSegs[0]) && $actionSegs[0] == 'install')
			{
				return $this->_processActionRequest($request);
			}
		}

		// Should they be?
		else if (!$isInstalled)
		{
			// Give it to them if accessing the CP
			if ($isCpRequest)
			{
				$url = UrlHelper::getUrl('install');
				$this->getResponse()->redirect($url);
				$this->end();
			}
			// Otherwise return a 404
			else
			{
				throw new NotFoundHttpException();
			}
		}

		return null;
	}

	/**
	 * Processes action requests.
	 *
	 * @param Request $request
	 * @return \yii\web\Response|null
	 * @throws NotFoundHttpException if the requested action route is invalid
	 */
	private function _processActionRequest($request)
	{
		if ($request->getIsActionRequest())
		{
			$route = implode('/', $request->getActionSegments());

			try
			{
				Craft::trace("Route requested: '$route'", __METHOD__);
				$this->requestedRoute = $route;
				return $this->runAction($route, $_GET);
			}
			catch (InvalidRouteException $e)
			{
				throw new NotFoundHttpException(Craft::t('yii', 'Page not found.'), $e->getCode(), $e);
			}
		}

		return null;
	}

	/**
	 * @param Request $request
	 * @return bool
	 */
	private function _isSpecialCaseActionRequest($request)
	{
		$segments = $request->getActionSegments();

		return (
			$segments === ['users', 'login'] ||
			$segments === ['users', 'logout'] ||
			$segments === ['users', 'set-password'] ||
			$segments === ['users', 'forgot-password'] ||
			$segments === ['users', 'send-password-reset-email'] ||
			$segments === ['users', 'save-user'] ||
			$segments === ['users', 'get-remaining-session-time'] ||
			$segments[0] === 'update'
		);
	}

	/**
	 * If there is not cached app path or the existing cached app path does not match the current one, let’s run the
	 * requirement checker again. This should catch the case where an install is deployed to another server that doesn’t
	 * meet Craft’s minimum requirements.
	 *
	 * @param Request|null $request
	 * @return null
	 */
	private function _processRequirementsCheck($request)
	{
		// See if we're in the middle of an update.
		$update = false;

		if ($request->getSegment(1) == 'updates' && $request->getSegment(2) == 'go')
		{
			$update = true;
		}

		if (($data = $request->getBodyParam('data', null)) !== null && isset($data['handle']))
		{
			$update = true;
		}

		// Only run for CP requests and if we're not in the middle of an update.
		if ($request->getIsCpRequest() && !$update)
		{
			$cachedAppPath = $this->getCache()->get('appPath');
			$appPath = $this->getPath()->getAppPath();

			if ($cachedAppPath === false || $cachedAppPath !== $appPath)
			{
				// Flush the data cache, so we're not getting cached CP resource paths.
				$this->getCache()->flush();

				return $this->runAction('templates/requirements-check');
			}
		}

		return null;
	}

	/**
	 * @param Request $request
	 * @return \yii\web\Response|null
	 * @throws HttpException
	 * @throws ServiceUnavailableHttpException
	 * @throws \yii\base\ExitException
	 */
	private function _processUpdateLogic($request)
	{
		$this->_unregisterDebugModule();

		// Let all non-action CP requests through.
		if (
			$request->getIsCpRequest() &&
			(!$request->getIsActionRequest() || $request->getActionSegments() == ['users', 'login'])
		)
		{
			// If this is a request to actually manually update Craft, do it
			if ($request->getSegment(1) == 'manualupdate')
			{
				return $this->runAction('templates/manual-update');
			}
			else
			{
				if ($this->getUpdates()->isBreakpointUpdateNeeded())
				{
					throw new HttpException(200, Craft::t('app', 'You need to be on at least Craft {url} before you can manually update to Craft {targetVersion} build {targetBuild}.', [
						'url'           => '[build '.Craft::$app->minBuildRequired.']('.Craft::$app->minBuildUrl.')',
						'targetVersion' => Craft::$app->version,
						'targetBuild'   => Craft::$app->build
					]));
				}
				else
				{
					if (!$request->getIsAjax())
					{
						if ($request->getPathInfo() !== '')
						{
							$this->getUser()->setReturnUrl($request->getPathInfo());
						}
					}

					// Show the manual update notification template
					return $this->runAction('templates/manual-update-notification');
				}
			}
		}
		// We'll also let action requests to UpdateController through as well.
		else if ($request->getIsActionRequest() && (($actionSegs = $request->getActionSegments()) !== null) && isset($actionSegs[0]) && $actionSegs[0] == 'update')
		{
			$controller = $actionSegs[0];
			$action = isset($actionSegs[1]) ? $actionSegs[1] : 'index';
			return $this->runAction($controller.'/'.$action);
		}
		else
		{
			// If an exception gets throw during the rendering of the 503 template, let
			// TemplatesController->actionRenderError() take care of it.
			throw new ServiceUnavailableHttpException();
		}
	}

	/**
	 * Checks if the system is off, and if it is, enforces the "Access the site/CP when the system is off" permissions.
	 *
	 * @param Request $request
	 * @return null
	 * @throws ServiceUnavailableHttpException
	 */
	private function _enforceSystemStatusPermissions($request)
	{
		if (!$this->_checkSystemStatusPermissions())
		{
			$error = null;

			if ($this->getUser()->isLoggedIn())
			{
				if ($request->getIsCpRequest())
				{
					$error = Craft::t('app', 'Your account doesn’t have permission to access the Control Panel when the system is offline.');
				}
				else
				{
					$error = Craft::t('app', 'Your account doesn’t have permission to access the site when the system is offline.');
				}

				$error .= ' ['.Craft::t('app', 'Log out?').']('.UrlHelper::getUrl($this->getConfig()->getLogoutPath()).')';
			}
			else
			{
				// If this is a CP request, redirect to the Login page
				if ($this->getRequest()->getIsCpRequest())
				{
					$this->getUser()->loginRequired();
					$this->end();
				}
			}

			$this->_unregisterDebugModule();
			throw new ServiceUnavailableHttpException($error);
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

		$request = $this->getRequest();
		$actionTrigger = $this->getConfig()->get('actionTrigger');

		if ($request->getIsCpRequest() ||

			// Special case because we hide the cpTrigger in emails.
			$request->getPathInfo() === $actionTrigger.'/users/set-password' ||
			$request->getPathInfo() === $actionTrigger.'/users/verify-email'
		)
		{
			if ($this->getUser()->checkPermission('accessCpWhenSystemIsOff'))
			{
				return true;
			}

			if ($request->getSegment(1) == 'manualupdate')
			{
				return true;
			}

			$actionSegs = $request->getActionSegments();

			if ($actionSegs && (
				$actionSegs === ['users', 'login'] ||
				$actionSegs === ['users', 'logout'] ||
				$actionSegs === ['users', 'forgot-password'] ||
				$actionSegs === ['users', 'send-password-reset-email'] ||
				$actionSegs === ['users', 'set-password'] ||
				$actionSegs === ['users', 'verify-email'] ||
				$actionSegs[0] === 'update'
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
