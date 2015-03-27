<?php
/**
 * @link http://buildwithcraft.com/
 * @copyright Copyright (c) 2013 Pixel & Tonic, Inc.
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
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;

/**
 * Craft Web Application class
 *
 * @property \craft\app\services\Assets           $assets           The assets service.
 * @property \craft\app\services\AssetIndexing    $assetIndexing    The asset indexing service.
 * @property \craft\app\services\Volumes          $volumes          The asset sources service.
 * @property \craft\app\services\AssetTransforms  $assetTransforms  The asset transforms service.
 * @property \craft\app\services\Categories       $categories       The categories service.
 * @property \craft\app\services\Config           $config           The config service.
 * @property \craft\app\services\Content          $content          The content service.
 * @property \craft\app\services\Dashboard        $dashboard        The dashboard service.
 * @property \craft\app\db\Connection             $db               The database connection component.
 * @property \craft\app\services\Deprecator       $deprecator       The deprecator service.
 * @property \craft\app\services\Elements         $elements         The elements service.
 * @property \craft\app\services\EmailMessages    $emailMessages    The email messages service.
 * @property \craft\app\services\Email            $email            The email service.
 * @property \craft\app\services\Entries          $entries          The entries service.
 * @property \craft\app\services\EntryRevisions   $entryRevisions   The entry revisions service.
 * @property \craft\app\errors\ErrorHandler       $errorHandler     The error handler component.
 * @property \craft\app\services\Et               $et               The E.T. service.
 * @property \craft\app\services\Feeds            $feeds            The feeds service.
 * @property \craft\app\services\Fields           $fields           The fields service.
 * @property \craft\app\cache\FileCache           $fileCache        [[\craft\app\cache\FileCache File caching]].
 * @property \craft\app\i18n\Formatter            $formatter        The formatter component.
 * @property \craft\app\services\Globals          $globals          The globals service.
 * @property \craft\app\i18n\I18N                 $i18n             The internationalization (i18n) component.
 * @property \craft\app\services\Images           $images           The images service.
 * @property \craft\app\services\Install          $install          The install service.
 * @property \craft\app\i18n\Locale               $locale           The locale component.
 * @property \craft\app\services\Matrix           $matrix           The matrix service.
 * @property \craft\app\services\Migrations       $migrations       The migrations service.
 * @property \craft\app\services\Path             $path             The path service.
 * @property \craft\app\services\Plugins          $plugins          The plugins service.
 * @property \craft\app\services\Relations        $relations        The relations service.
 * @property Request                              $request          The request component.
 * @property \craft\app\services\Resources        $resources        The resources service.
 * @property Response                             $response         The response component.
 * @property \craft\app\services\Routes           $routes           The routes service.
 * @property \craft\app\services\Search           $search           The search service.
 * @property \craft\app\services\Sections         $sections         The sections service.
 * @property \craft\app\services\Security         $security         The security component.
 * @property Session                              $session          The session component.
 * @property \craft\app\services\Structures       $structures       The structures service.
 * @property \craft\app\services\SystemSettings   $systemSettings   The system settings service.
 * @property \craft\app\services\Tags             $tags             The tags service.
 * @property \craft\app\services\Tasks            $tasks            The tasks service.
 * @property \craft\app\services\TemplateCache    $templateCache    The template cache service.
 * @property \craft\app\services\Templates        $templates        The template service.
 * @property \craft\app\services\Tokens           $tokens           The tokens service.
 * @property \craft\app\services\Updates          $updates          The updates service.
 * @property UrlManager                           $urlManager       The URL manager for this application.
 * @property \craft\app\services\UserGroups       $userGroups       The user groups service.
 * @property \craft\app\services\UserPermissions  $userPermissions  The user permission service.
 * @property \craft\app\services\Users            $users            The users service.
 * @property User                                 $user             The user component.
 * @method \craft\app\db\Connection               getDb()           Returns the database connection component.
 * @method \craft\app\errors\ErrorHandler         getErrorHandler() Returns the error handler component.
 * @method \craft\app\i18n\Formatter              getFormatter()    Returns the formatter component.
 * @method \craft\app\i18n\I18N                   getI18n()         Returns the internationalization (i18n) component.
 * @method Request                                getRequest()      Returns the request component.
 * @method Response                               getResponse()     Returns the response component.
 * @method \craft\app\services\Security           getSecurity()     Returns the security component.
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
		// NOTE: Nothing that triggers a database connection should be made here until *after* _processResourceRequest()
		// in handleRequest() is called.

		// Initialize the Cache service, Request and Logger right away (order is important)
		$this->getCache();
		$this->getRequest();
		$this->processLogTargets();

		// So we can try to translate Yii framework strings
		//$this->coreMessages->attachEventHandler('onMissingTranslation', ['Craft\LocalizationHelper', 'findMissingTranslation']);

		// If there is a custom appId set, apply it here.
		if ($appId = $this->config->get('appId'))
		{
			$this->setId($appId);
		}

		parent::init();
	}

	/**
	 * Handles the specified request.
	 *
	 * @param Request $request the request to be handled
	 *
	 * @return Response the resulting response
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

		// If this is a CP request, prevent robots from indexing/following the page
		// (see https://developers.google.com/webmasters/control-crawl-index/docs/robots_meta_tag)
		if ($request->getIsCpRequest())
		{
			HeaderHelper::setHeader(['X-Robots-Tag' => 'none']);
		}

		// Validate some basics on the database configuration file.
		$this->validateDbConfigFile();

		// Process install requests
		$this->_processInstallRequest($request);

		// If the system in is maintenance mode and it's a site request, throw a 503.
		if ($this->isInMaintenanceMode() && $request->getIsSiteRequest())
		{
			throw new ServiceUnavailableHttpException();
		}

		// Check if the app path has changed.  If so, run the requirements check again.
		$this->_processRequirementsCheck($request);

		// Makes sure that the uploaded files are compatible with the current DB schema
		if (!$this->updates->isSchemaVersionCompatible())
		{
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
			$this->_processUpdateLogic($request);
		}

		// If there's a new version, but the schema hasn't changed, just update the info table
		if ($this->updates->hasCraftBuildChanged())
		{
			$this->updates->updateCraftVersionInfo();
		}

		// If the system is offline, make sure they have permission to be here
		$this->_enforceSystemStatusPermissions($request);

		// Load the plugins
		$this->plugins->loadPlugins();

		// Check if a plugin needs to update the database.
		if ($this->updates->isPluginDbUpdateNeeded())
		{
			$this->_processUpdateLogic($request);
		}

		// If this is a non-login, non-validate, non-setPassword CP request, make sure the user has access to the CP
		if ($request->getIsCpRequest() && !($request->getIsActionRequest() && $this->_isSpecialCaseActionRequest($request)))
		{
			$user = $this->getUser();

			// Make sure the user has access to the CP
			if ($user->getIsGuest())
			{
				$user->loginRequired();
			}

			if (!$user->checkPermission('accessCp'))
			{
				throw new ForbiddenHttpException();
			}

			// If they're accessing a plugin's section, make sure that they have permission to do so
			$firstSeg = $request->getSegment(1);

			if ($firstSeg)
			{
				$plugin = $plugin = $this->plugins->getPlugin($firstSeg);

				if ($plugin)
				{
					if (!$user->checkPermission('accessPlugin-'.$this->templates->formatInputId($plugin::className())))
					{
						throw new ForbiddenHttpException();
					}
				}
			}
		}

		// If this is an action request, call the controller
		$this->_processActionRequest($request);

		// If we're still here, finally let Yii do it's thing.
		return parent::handleRequest($request);
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
	 * @inheritDoc \yii\di\ServiceLocator::get()
	 *
	 * @param string $id
	 * @param boolean $throwException
	 * @return object|null
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
	 * Returns the system time zone.
	 *
	 * Note that this method cannot be in [[\craft\app\base\ApplicationTrait]], because Yii will check
	 * [[\yii\base\Application::getTimeZone()]] instead.
	 *
	 * @return string
	 */
	//public function getTimeZone()
	//{
	//	return $this->_getTimeZone();
	//}

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
			$this->deprecator->log('yii1-route', 'A Yii 1-styled route was requested: "'.$requestedRoute.'". It should be changed to: "'.$route.'".');
		}

		return parent::createController($route);
	}

	// Private Methods
	// =========================================================================

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

			$this->resources->sendResource($path);
		}
	}

	/**
	 * Processes install requests.
	 *
	 * @param Request $request
	 *
	 * @return null
	 * @throws NotFoundHttpException
	 * @throws \yii\base\ExitException
	 */
	private function _processInstallRequest($request)
	{
		$isCpRequest = $request->getIsCpRequest();

		// Are they requesting an installer template/action specifically?
		if ($isCpRequest && $request->getSegment(1) === 'install' && !$this->isInstalled())
		{
			$action = $request->getSegment(2, 'index');
			$this->runAction('install/'.$action);
			$this->end();
		}
		else if ($isCpRequest && $request->getIsActionRequest() && ($request->getSegment(1) !== 'login'))
		{
			$actionSegs = $request->getActionSegments();
			if (isset($actionSegs[0]) && $actionSegs[0] == 'install')
			{
				$this->_processActionRequest($request);
			}
		}

		// Should they be?
		else if (!$this->isInstalled())
		{
			// Give it to them if accessing the CP
			if ($isCpRequest)
			{
				$url = UrlHelper::getUrl('install');
				$this->getResponse()->redirect($url);
			}
			// Otherwise return a 404
			else
			{
				throw new NotFoundHttpException();
			}
		}
	}

	/**
	 * Processes action requests.
	 *
	 * @param Request $request
	 * @throws HttpException
	 * @return null
	 */
	private function _processActionRequest($request)
	{
		if ($request->getIsActionRequest())
		{
			$actionSegs = $request->getActionSegments();
			$route = implode('/', $actionSegs);
			$this->runAction($route);
		}
	}

	/**
	 * @param Request $request
	 * @return bool
	 */
	private function _isSpecialCaseActionRequest($request)
	{
		$segments = $request->getActionSegments();

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
	 * @param Request $request
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
			$appPath = $this->path->getAppPath();

			if ($cachedAppPath === false || $cachedAppPath !== $appPath)
			{
				// Flush the data cache, so we're not getting cached CP resource paths.
				$this->getCache()->flush();

				$this->runAction('templates/requirements-check');
			}
		}
	}

	/**
	 * @param Request $request
	 * @return null
	 * @throws HttpException
	 * @throws ServiceUnavailableHttpException
	 * @throws \yii\base\ExitException
	 */
	private function _processUpdateLogic($request)
	{
		// Let all non-action CP requests through.
		if (
			$request->getIsCpRequest() &&
			(!$request->getIsActionRequest() || $request->getActionSegments() == ['users', 'login'])
		)
		{
			// If this is a request to actually manually update Craft, do it
			if ($request->getSegment(1) == 'manualupdate')
			{
				$this->runAction('templates/manual-update');
				$this->end();
			}
			else
			{
				if ($this->updates->isBreakpointUpdateNeeded())
				{
					throw new HttpException(200, Craft::t('app', 'You need to be on at least Craft {url} before you can manually update to Craft {targetVersion} build {targetBuild}.', [
						'url'           => '<a href="'.Craft::$app->minBuildUrl.'">build '.Craft::$app->minBuildRequired.'</a>',
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
							$this->getUser()->setReturnUrl($request->getPath());
						}
					}

					// Show the manual update notification template
					$this->runAction('templates/manual-update-notification');
				}
			}
		}
		// We'll also let action requests to UpdateController through as well.
		else if ($request->getIsActionRequest() && (($actionSegs = $request->getActionSegments()) !== null) && isset($actionSegs[0]) && $actionSegs[0] == 'update')
		{
			$controller = $actionSegs[0];
			$action = isset($actionSegs[1]) ? $actionSegs[1] : 'index';
			$this->runAction($controller.'/'.$action);
		}
		else
		{
			// If an exception gets throw during the rendering of the 503 template, let
			// TemplatesController->actionRenderError() take care of it.
			throw new ServiceUnavailableHttpException();
		}

		// <Gandalf> YOU SHALL NOT PASS!
		$this->end();
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

				$error .= ' ['.Craft::t('app', 'Log out?').']('.UrlHelper::getUrl($this->config->getLogoutPath()).')';
			}
			else
			{
				// If this is a CP request, redirect to the Login page
				if ($this->getRequest()->getIsCpRequest())
				{
					$this->getUser()->requireLogin();
				}
			}

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
		$actionTrigger = $this->config->get('actionTrigger');

		if ($request->getIsCpRequest() ||

			// Special case because we hide the cpTrigger in emails.
			$request->getPath() === $actionTrigger.'/users/setpassword' ||
			$request->getPath() === $actionTrigger.'/users/verifyemail'
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
