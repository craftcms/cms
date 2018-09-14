<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\web;

use Craft;
use craft\base\ApplicationTrait;
use craft\base\Plugin;
use craft\db\Query;
use craft\debug\DeprecatedPanel;
use craft\debug\UserPanel;
use craft\helpers\ArrayHelper;
use craft\helpers\FileHelper;
use craft\helpers\UrlHelper;
use craft\queue\QueueLogBehavior;
use yii\base\Component;
use yii\base\ErrorException;
use yii\base\InvalidArgumentException;
use yii\base\InvalidConfigException;
use yii\base\InvalidRouteException;
use yii\db\Exception as DbException;
use yii\debug\Module as DebugModule;
use yii\debug\panels\AssetPanel;
use yii\debug\panels\DbPanel;
use yii\debug\panels\LogPanel;
use yii\debug\panels\MailPanel;
use yii\debug\panels\ProfilingPanel;
use yii\debug\panels\RequestPanel;
use yii\debug\panels\RouterPanel;
use yii\web\ForbiddenHttpException;
use yii\web\HttpException;
use yii\web\NotFoundHttpException;
use yii\web\Response;

/**
 * Craft Web Application class
 *
 * An instance of the Web Application class is globally accessible to web requests in Craft via [[\Craft::$app|`Craft::$app`]].
 *
 * @property Request $request The request component
 * @property \craft\web\Response $response The response component
 * @property Session $session The session component
 * @property UrlManager $urlManager The URL manager for this application
 * @property User $user The user component
 * @method Request getRequest()      Returns the request component.
 * @method \craft\web\Response getResponse()     Returns the response component.
 * @method Session getSession()      Returns the session component.
 * @method UrlManager getUrlManager()   Returns the URL manager for this application.
 * @method User getUser()         Returns the user component.
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class Application extends \yii\web\Application
{
    // Traits
    // =========================================================================

    use ApplicationTrait;

    // Constants
    // =========================================================================

    /**
     * @event \yii\base\Event The event that is triggered after the application has been fully initialized
     *
     * ---
     * ```php
     * use craft\web\Application;
     *
     * Craft::$app->on(Application::EVENT_INIT, function() {
     *     // ...
     * });
     * ```
     */
    const EVENT_INIT = 'init';

    /**
     * @event \craft\events\EditionChangeEvent The event that is triggered after the edition changes
     */
    const EVENT_AFTER_EDITION_CHANGE = 'afterEditionChange';

    // Public Methods
    // =========================================================================

    /**
     * Constructor.
     *
     * @param array $config
     */
    public function __construct(array $config = [])
    {
        Craft::$app = $this;
        parent::__construct($config);
    }

    /**
     * Initializes the application.
     */
    public function init()
    {
        $this->state = self::STATE_INIT;
        $this->_preInit();
        parent::init();
        $this->ensureResourcePathExists();
        $this->_postInit();
        $this->debugBootstrap();
    }

    /**
     * @inheritdoc
     */
    public function bootstrap()
    {
        // Ensure that the request component has been instantiated
        if (!$this->has('request', true)) {
            $this->getRequest();
        }

        // Skip yii\web\Application::bootstrap, because we've already set @web and
        // @webroot from craft\web\Request::init(), and we like our values better.
        \yii\base\Application::bootstrap();
    }

    /**
     * @inheritdoc
     */
    public function setTimeZone($value)
    {
        parent::setTimeZone($value);

        if ($value !== 'UTC' && $this->getI18n()->getIsIntlLoaded()) {
            // Make sure that ICU supports this timezone
            try {
                new \IntlDateFormatter($this->language, \IntlDateFormatter::NONE, \IntlDateFormatter::NONE);
            } catch (\IntlException $e) {
                Craft::warning("Time zone \"{$value}\" does not appear to be supported by ICU: " . intl_get_error_message());
                parent::setTimeZone('UTC');
            }
        }
    }

    /**
     * Handles the specified request.
     *
     * @param Request $request the request to be handled
     * @return Response the resulting response
     * @throws HttpException
     * @throws ServiceUnavailableHttpException
     * @throws \craft\errors\DbConnectException
     * @throws ForbiddenHttpException
     * @throws \yii\web\NotFoundHttpException
     */
    public function handleRequest($request): Response
    {
        // Process resource requests before anything else
        $this->_processResourceRequest($request);

        $headers = $this->getResponse()->getHeaders();

        if ($request->getIsCpRequest()) {
            // Prevent robots from indexing/following the page
            // (see https://developers.google.com/webmasters/control-crawl-index/docs/robots_meta_tag)
            $headers->set('X-Robots-Tag', 'none');

            // Prevent some possible XSS attack vectors
            $headers->set('X-Frame-Options', 'SAMEORIGIN');
            $headers->set('X-Content-Type-Options', 'nosniff');
        }

        // Send the X-Powered-By header?
        if ($this->getConfig()->getGeneral()->sendPoweredByHeader) {
            $original = $headers->get('X-Powered-By');
            $headers->set('X-Powered-By', $original . ($original ? ',' : '') . $this->name);
        } else {
            // In case PHP is already setting one
            header_remove('X-Powered-By');
        }

        // Process install requests
        if (($response = $this->_processInstallRequest($request)) !== null) {
            return $response;
        }

        // Check if the app path has changed.  If so, run the requirements check again.
        if (($response = $this->_processRequirementsCheck($request)) !== null) {
            $this->_unregisterDebugModule();

            return $response;
        }

        // Makes sure that the uploaded files are compatible with the current database schema
        if (!$this->getUpdates()->getIsCraftSchemaVersionCompatible()) {
            $this->_unregisterDebugModule();

            if ($request->getIsCpRequest()) {
                $version = $this->getInfo()->version;

                throw new HttpException(200, Craft::t('app', 'Craft CMS does not support backtracking to this version. Please update to Craft CMS {version} or later.', [
                    'version' => $version,
                ]));
            }

            throw new ServiceUnavailableHttpException();
        }

        // getIsCraftDbMigrationNeeded will return true if we're in the middle of a manual or auto-update for Craft itself.
        // If we're in maintenance mode and it's not a site request, show the manual update template.
        if ($this->getUpdates()->getIsCraftDbMigrationNeeded()) {
            return $this->_processUpdateLogic($request) ?: $this->getResponse();
        }

        // If there's a new version, but the schema hasn't changed, just update the info table
        if ($this->getUpdates()->getHasCraftVersionChanged()) {
            $this->getUpdates()->updateCraftVersionInfo();

            // Delete all compiled templates
            try {
                FileHelper::clearDirectory($this->getPath()->getCompiledTemplatesPath(false));
            } catch (InvalidArgumentException $e) {
                // the directory doesn't exist
            } catch (ErrorException $e) {
                Craft::error('Could not delete compiled templates: ' . $e->getMessage());
                Craft::$app->getErrorHandler()->logException($e);
            }
        }

        // If the system is offline, make sure they have permission to be here
        $this->_enforceSystemStatusPermissions($request);

        // Check if a plugin needs to update the database.
        if ($this->getUpdates()->getIsPluginDbUpdateNeeded()) {
            return $this->_processUpdateLogic($request) ?: $this->getResponse();
        }

        // If this is a non-login, non-validate, non-setPassword CP request, make sure the user has access to the CP
        if ($request->getIsCpRequest() && !($request->getIsActionRequest() && $this->_isSpecialCaseActionRequest($request))) {
            $user = $this->getUser();

            // Make sure the user has access to the CP
            if ($user->getIsGuest()) {
                return $user->loginRequired();
            }

            if (!$user->checkPermission('accessCp')) {
                throw new ForbiddenHttpException();
            }

            // If they're accessing a plugin's section, make sure that they have permission to do so
            $firstSeg = $request->getSegment(1);

            if ($firstSeg !== null) {
                /** @var Plugin|null $plugin */
                $plugin = $this->getPlugins()->getPlugin($firstSeg);

                if ($plugin && !$user->checkPermission('accessPlugin-' . $plugin->id)) {
                    throw new ForbiddenHttpException();
                }
            }
        }

        // If this is an action request, call the controller
        if (($response = $this->_processActionRequest($request)) !== null) {
            return $response;
        }

        // If we're still here, finally let Yii do it's thing.
        return parent::handleRequest($request);
    }

    /**
     * @inheritdoc
     * @param string $route
     * @param array $params
     * @return Response|null The result of the action, normalized into a Response object
     */
    public function runAction($route, $params = [])
    {
        $result = parent::runAction($route, $params);

        if ($result !== null) {
            if ($result instanceof Response) {
                return $result;
            }

            $response = $this->getResponse();
            $response->data = $result;

            return $response;
        }

        return null;
    }

    /**
     * @inheritdoc
     */
    public function setVendorPath($path)
    {
        parent::setVendorPath($path);

        // Override the @bower and @npm aliases if using asset-packagist.org
        // todo: remove this whenever Yii is updated with support for asset-packagist.org
        $altBowerPath = $this->getVendorPath() . DIRECTORY_SEPARATOR . 'bower-asset';
        $altNpmPath = $this->getVendorPath() . DIRECTORY_SEPARATOR . 'npm-asset';
        if (is_dir($altBowerPath)) {
            Craft::setAlias('@bower', $altBowerPath);
        }
        if (is_dir($altNpmPath)) {
            Craft::setAlias('@npm', $altNpmPath);
        }

        // Override where Yii should find its asset deps
        $libPath = Craft::getAlias('@lib');
        Craft::setAlias('@bower/bootstrap/dist', $libPath . '/bootstrap');
        Craft::setAlias('@bower/jquery/dist', $libPath . '/jquery');
        Craft::setAlias('@bower/inputmask/dist', $libPath . '/inputmask');
        Craft::setAlias('@bower/punycode', $libPath . '/punycode');
        Craft::setAlias('@bower/yii2-pjax', $libPath . '/yii2-pjax');
    }

    /**
     * @inheritdoc
     */
    public function get($id, $throwException = true)
    {
        // Is this the first time the queue component is requested?
        $isFirstQueue = $id === 'queue' && !$this->has($id, true);

        $component = parent::get($id, $throwException);

        if ($isFirstQueue && $component instanceof Component) {
            $component->attachBehavior('queueLogger', QueueLogBehavior::class);
        }

        return $component;
    }

    // Protected Methods
    // =========================================================================

    /**
     * Ensures that the resources folder exists and is writable.
     *
     * @throws InvalidConfigException
     */
    protected function ensureResourcePathExists()
    {
        $resourceBasePath = Craft::getAlias($this->getConfig()->getGeneral()->resourceBasePath);
        @FileHelper::createDirectory($resourceBasePath);

        if (!is_dir($resourceBasePath) || !FileHelper::isWritable($resourceBasePath)) {
            throw new InvalidConfigException($resourceBasePath . ' doesn’t exist or isn’t writable by PHP.');
        }
    }

    /**
     * Bootstraps the Debug Toolbar if necessary.
     */
    protected function debugBootstrap()
    {
        $session = $this->getSession();
        if (!$session->getHasSessionId() && !$session->getIsActive()) {
            return;
        }

        $isCpRequest = $this->getRequest()->getIsCpRequest();
        if (
            ($isCpRequest && !$session->get('enableDebugToolbarForCp')) ||
            (!$isCpRequest && !$session->get('enableDebugToolbarForSite'))
        ) {
            return;
        }

        $svg = rawurlencode(file_get_contents(dirname(__DIR__) . '/icons/c.svg'));
        DebugModule::setYiiLogo("data:image/svg+xml;charset=utf-8,{$svg}");

        $this->setModule('debug', [
            'class' => DebugModule::class,
            'allowedIPs' => ['*'],
            'panels' => [
                'config' => false,
                'user' => UserPanel::class,
                'router' => [
                    'class' => RouterPanel::class,
                    'categories' => [
                        UrlManager::class . '::_getMatchedElementRoute',
                        UrlManager::class . '::_getMatchedUrlRoute',
                        UrlManager::class . '::_getTemplateRoute',
                        UrlManager::class . '::_getTokenRoute',
                    ]
                ],
                'request' => RequestPanel::class,
                'log' => LogPanel::class,
                'deprecated' => DeprecatedPanel::class,
                'profiling' => ProfilingPanel::class,
                'db' => DbPanel::class,
                'assets' => AssetPanel::class,
                'mail' => MailPanel::class,
            ],
        ]);
        /** @var DebugModule $module */
        $module = $this->getModule('debug');
        $module->bootstrap($this);
    }

    // Private Methods
    // =========================================================================

    /**
     * Unregisters the Debug module's end body event.
     */
    private function _unregisterDebugModule()
    {
        $debug = $this->getModule('debug', false);

        if ($debug !== null) {
            $this->getView()->off(View::EVENT_END_BODY,
                [$debug, 'renderToolbar']);
        }
    }

    /**
     * Processes resource requests.
     *
     * @param Request $request
     */
    private function _processResourceRequest(Request $request)
    {
        // Does this look like a resource request?
        $resourceBaseUri = parse_url(Craft::getAlias($this->getConfig()->getGeneral()->resourceBaseUrl), PHP_URL_PATH);
        $pathInfo = $request->getPathInfo();
        if (strpos('/' . $pathInfo, $resourceBaseUri . '/') !== 0) {
            return;
        }

        $resourceUri = substr($pathInfo, strlen($resourceBaseUri));
        $slash = strpos($resourceUri, '/');
        $hash = substr($resourceUri, 0, $slash);

        try {
            $sourcePath = (new Query())
                ->select(['path'])
                ->from('{{%resourcepaths}}')
                ->where(['hash' => $hash])
                ->scalar();
        } catch (DbException $e) {
            // Craft is either not installed or not updated to 3.0.3+ yet
        }

        if (empty($sourcePath)) {
            return;
        }

        // Publish the directory
        $filePath = substr($resourceUri, strlen($hash) + 1);
        $publishedPath = $this->getAssetManager()->getPublishedPath(Craft::getAlias($sourcePath), true) . DIRECTORY_SEPARATOR . $filePath;
        $this->getResponse()
            ->sendFile($publishedPath, null, ['inline' => true]);
        $this->end();
    }

    /**
     * Processes install requests.
     *
     * @param Request $request
     * @return null|Response
     * @throws NotFoundHttpException
     * @throws ServiceUnavailableHttpException
     * @throws \yii\base\ExitException
     */
    private function _processInstallRequest(Request $request)
    {
        $isCpRequest = $request->getIsCpRequest();
        $isInstalled = $this->getIsInstalled();

        if (!$isInstalled) {
            $this->_unregisterDebugModule();
        }

        // Are they requesting the installer?
        if ($isCpRequest && $request->getSegment(1) === 'install') {
            // Is Craft already installed?
            if ($isInstalled) {
                // Redirect to the Dashboard
                $this->getResponse()->redirect('dashboard');
                $this->end();
            } else {
                // Show the installer
                $action = $request->getSegment(2) ?: 'index';
                return $this->runAction('install/' . $action);
            }
        }

        // Is this an installer action request?
        if ($isCpRequest && $request->getIsActionRequest() && ($request->getSegment(1) !== 'login')) {
            $actionSegs = $request->getActionSegments();
            if (isset($actionSegs[0]) && $actionSegs[0] === 'install') {
                return $this->_processActionRequest($request);
            }
        }

        // Should they be accessing the installer?
        if (!$isInstalled) {
            if (!$isCpRequest) {
                throw new ServiceUnavailableHttpException();
            }

            // Redirect to the installer if Dev Mode is enabled
            if (Craft::$app->getConfig()->getGeneral()->devMode) {
                $url = UrlHelper::url('install');
                $this->getResponse()->redirect($url);
                $this->end();
            }

            throw new ServiceUnavailableHttpException(Craft::t('app', 'Craft isn’t installed yet.'));
        }

        return null;
    }

    /**
     * Processes action requests.
     *
     * @param Request $request
     * @return Response|null
     * @throws NotFoundHttpException if the requested action route is invalid
     */
    private function _processActionRequest(Request $request)
    {
        if ($request->getIsActionRequest()) {
            $route = implode('/', $request->getActionSegments());

            try {
                Craft::debug("Route requested: '$route'", __METHOD__);
                $this->requestedRoute = $route;

                return $this->runAction($route, $_GET);
            } catch (InvalidRouteException $e) {
                throw new NotFoundHttpException(Craft::t('yii', 'Page not found.'), $e->getCode(), $e);
            }
        }

        return null;
    }

    /**
     * Returns whether this is a special case request (something dealing with user sessions or updating)
     * where system status / CP permissions shouldn't be taken into effect.
     *
     * @param Request $request
     * @return bool
     */
    private function _isSpecialCaseActionRequest(Request $request): bool
    {
        $actionSegs = $request->getActionSegments();

        if (empty($actionSegs)) {
            return false;
        }

        return (
            $actionSegs === ['app', 'migrate'] ||
            $actionSegs === ['users', 'login'] ||
            $actionSegs === ['users', 'forgot-password'] ||
            $actionSegs === ['users', 'send-password-reset-email'] ||
            $actionSegs === ['users', 'get-remaining-session-time'] ||
            (
                $request->getIsSingleActionRequest() &&
                (
                    $actionSegs === ['users', 'logout'] ||
                    $actionSegs === ['users', 'set-password'] ||
                    $actionSegs === ['users', 'verify-email']
                )
            ) ||
            (
                $request->getIsCpRequest() &&
                (
                    $actionSegs[0] === 'update' ||
                    $actionSegs[0] === 'manualupdate'
                )
            )
        );
    }

    /**
     * If there is not cached app path or the existing cached app path does not match the current one, let’s run the
     * requirement checker again. This should catch the case where an install is deployed to another server that doesn’t
     * meet Craft’s minimum requirements.
     *
     * @param Request $request
     * @return Response|null
     */
    private function _processRequirementsCheck(Request $request)
    {
        // Only run for CP requests and if we're not in the middle of an update.
        if (
            $request->getIsCpRequest() &&
            !(
                $request->getIsActionRequest() &&
                (
                    ArrayHelper::firstValue($request->getActionSegments()) === 'updater' ||
                    $request->getActionSegments() === ['app', 'migrate']
                )
            )
        ) {
            $cachedBasePath = $this->getCache()->get('basePath');

            if ($cachedBasePath === false || $cachedBasePath !== $this->getBasePath()) {
                return $this->runAction('templates/requirements-check');
            }
        }

        return null;
    }

    /**
     * @param Request $request
     * @return Response|null
     * @throws HttpException
     * @throws ServiceUnavailableHttpException
     * @throws \yii\base\ExitException
     */
    private function _processUpdateLogic(Request $request)
    {
        $this->_unregisterDebugModule();

        // Let all non-action CP requests through.
        if (
            $request->getIsCpRequest() &&
            (!$request->getIsActionRequest() || $request->getActionSegments() == ['users', 'login'])
        ) {
            // Did we skip a breakpoint?
            if ($this->getUpdates()->getWasCraftBreakpointSkipped()) {
                throw new HttpException(200, Craft::t('app', 'You need to be on at least Craft CMS {version} before you can manually update to Craft CMS {targetVersion}.', [
                    'version' => $this->minVersionRequired,
                    'targetVersion' => Craft::$app->getVersion(),
                ]));
            }

            // Clear the template caches in case they've been compiled since this release was cut.
            try {
                FileHelper::clearDirectory($this->getPath()->getCompiledTemplatesPath(false));
            } catch (InvalidArgumentException $e) {
                // the directory doesn't exist
            }

            // Show the manual update notification template
            return $this->runAction('templates/manual-update-notification');
        }

        // We'll also let update actions go through
        if ($request->getIsActionRequest()) {
            $actionSegments = $request->getActionSegments();
            if (
                ArrayHelper::firstValue($actionSegments) === 'updater' ||
                $actionSegments === ['app', 'migrate'] ||
                $actionSegments === ['pluginstore', 'install', 'migrate']
            ) {
                return $this->runAction(implode('/', $actionSegments));
            }
        }

        // If an exception gets throw during the rendering of the 503 template, let
        // TemplatesController->actionRenderError() take care of it.
        throw new ServiceUnavailableHttpException();
    }

    /**
     * Checks if the system is off, and if it is, enforces the "Access the site/CP when the system is off" permissions.
     *
     * @param Request $request
     * @throws ServiceUnavailableHttpException
     */
    private function _enforceSystemStatusPermissions(Request $request)
    {
        if (!$this->_checkSystemStatusPermissions($request)) {
            $error = null;

            if (!$this->getUser()->getIsGuest()) {
                if ($request->getIsCpRequest()) {
                    $error = Craft::t('app', 'Your account doesn’t have permission to access the Control Panel when the system is offline.');
                    $logoutUrl = UrlHelper::cpUrl('logout');
                } else {
                    $error = Craft::t('app', 'Your account doesn’t have permission to access the site when the system is offline.');
                    $logoutUrl = UrlHelper::siteUrl(Craft::$app->getConfig()->getGeneral()->getLogoutPath());
                }

                $error .= ' [' . Craft::t('app', 'Log out?') . '](' . $logoutUrl . ')';
            } else {
                // If this is a CP request, redirect to the Login page
                if ($this->getRequest()->getIsCpRequest()) {
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
     * @param Request $request
     * @return bool
     */
    private function _checkSystemStatusPermissions(Request $request): bool
    {
        if ($this->getIsSystemOn() || $this->_isSpecialCaseActionRequest($request)) {
            return true;
        }

        $permission = $request->getIsCpRequest() ? 'accessCpWhenSystemIsOff' : 'accessSiteWhenSystemIsOff';
        return $this->getUser()->checkPermission($permission);
    }
}
