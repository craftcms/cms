<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\web;

use Craft;
use craft\base\ApplicationTrait;
use craft\db\Query;
use craft\db\Table;
use craft\debug\DeprecatedPanel;
use craft\debug\DumpPanel;
use craft\debug\Module as DebugModule;
use craft\debug\RequestPanel;
use craft\debug\UserPanel;
use craft\enums\LicenseKeyStatus;
use craft\errors\ExitException;
use craft\helpers\App;
use craft\helpers\ArrayHelper;
use craft\helpers\DateTimeHelper;
use craft\helpers\Db;
use craft\helpers\FileHelper;
use craft\helpers\Json;
use craft\helpers\Path;
use craft\helpers\UrlHelper;
use craft\queue\QueueLogBehavior;
use IntlDateFormatter;
use IntlException;
use ReflectionClass;
use Throwable;
use yii\base\Component;
use yii\base\ErrorException;
use yii\base\Exception;
use yii\base\ExitException as YiiExitException;
use yii\base\InvalidArgumentException;
use yii\base\InvalidConfigException;
use yii\base\InvalidRouteException;
use yii\db\Exception as DbException;
use yii\debug\Module as YiiDebugModule;
use yii\debug\panels\AssetPanel;
use yii\debug\panels\DbPanel;
use yii\debug\panels\LogPanel;
use yii\debug\panels\MailPanel;
use yii\debug\panels\ProfilingPanel;
use yii\debug\panels\RouterPanel;
use yii\web\BadRequestHttpException;
use yii\web\ForbiddenHttpException;
use yii\web\HttpException;
use yii\web\NotFoundHttpException;
use yii\web\Response as BaseResponse;
use yii\web\UnauthorizedHttpException;

/**
 * Craft Web Application class
 *
 * An instance of the Web Application class is globally accessible to web requests in Craft via [[\Craft::$app|`Craft::$app`]].
 *
 * @property-read Request $request The request component
 * @property-read Response $response The response component
 * @property-read Session $session The session component
 * @property-read User $user The user component
 * @method Request getRequest() Returns the request component.
 * @method Response getResponse() Returns the response component.
 * @method Session getSession() Returns the session component.
 * @method User getUser() Returns the user component.
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class Application extends \yii\web\Application
{
    use ApplicationTrait;

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
    public const EVENT_INIT = 'init';

    /**
     * @event \craft\events\EditionChangeEvent The event that is triggered after the edition changes
     */
    public const EVENT_AFTER_EDITION_CHANGE = 'afterEditionChange';

    /**
     * Initializes the application.
     */
    public function init(): void
    {
        $this->state = self::STATE_INIT;
        $this->_preInit();

        parent::init();

        if (!App::isEphemeral()) {
            $this->ensureResourcePathExists();
        }

        $this->_postInit();

        // If there's an invalid token on the request, throw an exception now
        if ($this->getRequest()->getHasInvalidToken()) {
            throw new BadRequestHttpException('Invalid token');
        }

        // Process resource requests before we do anything to establish the user session
        $this->_processResourceRequest();

        $this->authenticate();
        $this->debugBootstrap();
    }

    /**
     * @inheritdoc
     */
    public function bootstrap(): void
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
    public function setTimeZone($value): void
    {
        parent::setTimeZone($value);

        if ($value !== 'UTC') {
            // Make sure that ICU supports this timezone
            try {
                /** @noinspection PhpExpressionResultUnusedInspection */
                /** @phpstan-ignore-next-line */
                new IntlDateFormatter($this->language, IntlDateFormatter::NONE, IntlDateFormatter::NONE);
            } catch (IntlException) {
                Craft::warning("Time zone “{$value}” does not appear to be supported by ICU: " . intl_get_error_message());
                parent::setTimeZone('UTC');
            }
        }
    }

    /**
     * Handles the specified request.
     *
     * @param Request $request the request to be handled
     * @param bool $skipSpecialHandling Whether to skip the special case request handling stuff and go straight to
     * the normal routing logic
     * @return BaseResponse the resulting response
     * @throws Throwable if reasons
     */
    public function handleRequest($request, bool $skipSpecialHandling = false): BaseResponse
    {
        if (!$skipSpecialHandling) {
            // Disable read/write splitting for most POST requests
            if (
                $request->getIsPost() &&
                !in_array($request->getActionSegments(), [
                    ['element-indexes', 'count-elements'],
                    ['element-indexes', 'data'],
                    ['element-indexes', 'export'],
                    ['element-indexes', 'get-elements'],
                    ['element-indexes', 'get-more-elements'],
                    ['element-indexes', 'get-source-tree-html'],
                    ['graphql', 'api'],
                ]) &&
                !$request->getIsGraphql()
            ) {
                $this->getDb()->enableReplicas = false;
            }

            $isCpRequest = $request->getIsCpRequest();
            $response = $this->getResponse();
            $headers = $response->getHeaders();
            $generalConfig = $this->getConfig()->getGeneral();

            // Set no-cache headers for all action and CP requests
            if ($request->getIsActionRequest() || $request->getIsCpRequest()) {
                $response->setNoCacheHeaders();
            }

            // Set the permissions policy
            if ($generalConfig->permissionsPolicyHeader && $request->getIsSiteRequest()) {
                $headers->set('Permissions-Policy', $generalConfig->permissionsPolicyHeader);
            }

            // Tell bots not to index/follow control panel and tokenized pages
            if (
                $generalConfig->disallowRobots ||
                $isCpRequest ||
                $request->getToken() !== null ||
                $request->getIsPreview() ||
                ($request->getIsActionRequest() && !($request->getIsLoginRequest() && $request->getIsGet()))
            ) {
                $headers->set('X-Robots-Tag', 'none');
            }

            // Prevent some possible XSS attack vectors
            if ($isCpRequest) {
                $headers->add('Content-Security-Policy', "frame-ancestors 'self'");
                $headers->set('X-Frame-Options', 'SAMEORIGIN');
                $headers->set('X-Content-Type-Options', 'nosniff');
            }

            // Send the X-Powered-By header?
            if ($generalConfig->sendPoweredByHeader) {
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

            // Check if the app path has changed. If so, run the requirements check again.
            if (($response = $this->_processRequirementsCheck($request)) !== null) {
                $this->_unregisterDebugModule();

                return $response;
            }

            // Makes sure that the uploaded files are compatible with the current database schema
            if (!$this->getUpdates()->getIsCraftSchemaVersionCompatible()) {
                $this->_unregisterDebugModule();

                if ($isCpRequest) {
                    $version = $this->getInfo()->version;

                    throw new HttpException(200, Craft::t('app', 'Craft CMS does not support backtracking to this version. Please update to Craft CMS {version} or later.', [
                        'version' => $version,
                    ]));
                }

                throw new ServiceUnavailableHttpException();
            }

            // getIsCraftDbMigrationNeeded will return true if we’re in the middle of a manual or auto-update for Craft itself.
            // If we’re in maintenance mode and it’s not a site request, show the manual update template.
            if ($this->getUpdates()->getIsCraftUpdatePending()) {
                return $this->_processUpdateLogic($request) ?: $response;
            }

            // If there’s a new version, but the schema hasn’t changed, just update the info table
            if ($this->getUpdates()->getHasCraftVersionChanged()) {
                $this->getUpdates()->updateCraftVersionInfo();

                // Delete all compiled templates
                try {
                    FileHelper::clearDirectory($this->getPath()->getCompiledTemplatesPath(false));
                } catch (InvalidArgumentException) {
                    // Directory does not exist
                } catch (ErrorException $e) {
                    Craft::error('Could not delete compiled templates: ' . $e->getMessage());
                    Craft::$app->getErrorHandler()->logException($e);
                }
            }

            // Check if a plugin needs to update the database.
            if ($this->getUpdates()->getIsPluginUpdatePending()) {
                return $this->_processUpdateLogic($request) ?: $response;
            }

            if (!$request->getIsActionRequest()) {
                $userSession = $this->getUser();

                // If this is a plugin template request, make sure the user has access to the plugin
                // If this is a non-login, non-validate, non-setPassword control panel request, make sure the user has access to the control panel
                if (
                    $isCpRequest &&
                    ($firstSeg = $request->getSegment(1)) !== null &&
                    ($plugin = $this->getPlugins()->getPlugin($firstSeg)) !== null
                ) {
                    if ($userSession->getIsGuest()) {
                        return $userSession->loginRequired();
                    }
                    if (!$userSession->checkPermission('accessPlugin-' . $plugin->id)) {
                        throw new ForbiddenHttpException();
                    }
                }

                if (!$userSession->getIsGuest()) {
                    // See if the user is expected to have 2FA enabled
                    if (!$generalConfig->disable2fa) {
                        $auth = $this->getAuth();
                        $user = $userSession->getIdentity();
                        if ($auth->is2faRequired($user) && !$auth->hasActiveMethod($user)) {
                            return $this->runAction('users/setup-2fa');
                        }
                    }

                    if ($isCpRequest && !$this->getCanTestEditions()) {
                        // Are there are any licensing issues cached?
                        $licenseIssues = App::licensingIssues([
                            LicenseKeyStatus::Trial->value,
                            LicenseKeyStatus::Astray->value,
                            'wrong_edition',
                        ]);
                        if (!empty($licenseIssues)) {
                            $hash = App::licensingIssuesHash($licenseIssues);
                            if ($this->_showLicensingIssuesScreen($hash)) {
                                return $this->runAction('app/licensing-issues', [
                                    'issues' => $licenseIssues,
                                    'hash' => $hash,
                                ]);
                            }
                        }
                    }
                }
            }
        }

        // If this is an action request, call the controller
        if (($response = $this->_processActionRequest($request)) !== null) {
            return $response;
        }

        // If we’re still here, finally let Yii do its thing.
        try {
            return parent::handleRequest($request);
        } catch (Throwable $e) {
            $this->_unregisterDebugModule();
            throw $e;
        }
    }

    private function _showLicensingIssuesScreen(string $hash = null): bool
    {
        $cookie = $this->request->getCookies()->get(App::licenseShunCookieName());
        if (!$cookie) {
            return true;
        }

        // the cookie is only valid if it's for the same set of issues we're currently seeing
        $data = Json::decode($cookie->value);
        if ($data['hash'] !== $hash) {
            return true;
        }

        // if the cookie was created earlier today, let them pass
        return !DateTimeHelper::isToday($data['timestamp']);
    }

    /**
     * @inheritdoc
     * @param string $route
     * @param array $params
     * @return BaseResponse|null The result of the action, normalized into a Response object
     */
    public function runAction($route, $params = []): ?BaseResponse
    {
        $result = parent::runAction($route, $params);

        if ($result === null || $result instanceof BaseResponse) {
            return $result;
        }

        $response = $this->getResponse();
        $response->data = $result;
        return $response;
    }

    /**
     * @inheritdoc
     */
    public function get($id, $throwException = true): ?object
    {
        // Is this the first time the queue component is requested?
        $isFirstQueue = $id === 'queue' && !$this->has($id, true);

        $component = parent::get($id, $throwException);

        if ($isFirstQueue && $component instanceof Component) {
            $component->attachBehavior('queueLogger', QueueLogBehavior::class);
        }

        return $component;
    }

    /**
     * Ensures that the resources folder exists and is writable.
     *
     * @throws ErrorException
     * @throws InvalidConfigException
     * @throws Exception
     */
    protected function ensureResourcePathExists(): void
    {
        $generalConfig = $this->getConfig()->getGeneral();

        $resourceBasePath = Craft::getAlias($generalConfig->resourceBasePath);

        if (!@FileHelper::createDirectory($resourceBasePath)) {
            throw new InvalidConfigException("$resourceBasePath doesn’t exist.");
        }
    }

    /**
     * Authenticates the request.
     *
     * @throws UnauthorizedHttpException
     * @since 3.5.0
     */
    protected function authenticate(): void
    {
        if (!Craft::$app->getConfig()->getGeneral()->enableBasicHttpAuth) {
            return;
        }

        // Did the request include user credentials?
        [$username, $password] = $this->getRequest()->getAuthCredentials();

        if (!$username || !$password) {
            return;
        }

        $user = Craft::$app->getUsers()->getUserByUsernameOrEmail(Db::escapeParam($username));

        if (!$user) {
            throw new UnauthorizedHttpException('Your request was made with invalid credentials.');
        }

        if (!$user->authenticate($password)) {
            throw new UnauthorizedHttpException('Your request was made with invalid credentials.');
        }

        $this->getUser()->setIdentity($user);
    }

    /**
     * Bootstraps the Debug Toolbar if necessary.
     */
    protected function debugBootstrap(): void
    {
        $request = $this->getRequest();

        if ($request->getIsLivePreview() || $request->getIsPreview()) {
            return;
        }

        // Only load the debug toolbar if it's enabled for the user, or Dev Mode is enabled and the request wants it
        $user = $this->getUser()->getIdentity();
        $pref = $request->getIsCpRequest() ? 'enableDebugToolbarForCp' : 'enableDebugToolbarForSite';
        if (!(
            ($user && $user->admin && $user->getPreference($pref)) ||
            (App::devMode() && $request->getHeaders()->get('X-Debug') === 'enable')
        )) {
            return;
        }

        $svg = rawurlencode(file_get_contents(dirname(__DIR__) . '/icons/custom-icons/c-debug.svg'));
        DebugModule::setYiiLogo("data:image/svg+xml;charset=utf-8,$svg");

        // Determine the base path using reflection in case it wasn't loaded from @vendor
        $ref = new ReflectionClass(YiiDebugModule::class);
        $basePath = dirname($ref->getFileName());

        $this->setModule('debug', [
            'class' => DebugModule::class,
            'basePath' => $basePath,
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
                    ],
                ],
                'request' => RequestPanel::class,
                'log' => LogPanel::class,
                'dump' => DumpPanel::class,
                'deprecated' => DeprecatedPanel::class,
                'profiling' => ProfilingPanel::class,
                'db' => DbPanel::class,
                'asset' => AssetPanel::class,
                'mail' => MailPanel::class,
            ],
        ]);
        /** @var DebugModule $module */
        $module = $this->getModule('debug');
        $module->bootstrap($this);

        if ($config = Craft::$app->getConfig()->getConfigFromFile('debug')) {
            Craft::configure($module, $config);
        }
    }

    /**
     * Unregisters the Debug module's end body event.
     */
    private function _unregisterDebugModule(): void
    {
        $debug = $this->getModule('debug', false);

        if ($debug !== null) {
            $this->getView()->off(View::EVENT_END_BODY, [$debug, 'renderToolbar']);
        }
    }

    /**
     * Processes resource requests.
     *
     * @throws BadRequestHttpException
     * @throws NotFoundHttpException
     */
    private function _processResourceRequest(): void
    {
        $generalConfig = $this->getConfig()->getGeneral();
        $request = $this->getRequest();

        // Does this look like a resource request?
        $resourceBaseUri = parse_url(Craft::getAlias($generalConfig->resourceBaseUrl), PHP_URL_PATH);
        $requestPath = $request->getFullPath();
        if (!str_starts_with('/' . $requestPath, $resourceBaseUri . '/')) {
            return;
        }

        $resourceUri = substr($requestPath, strlen($resourceBaseUri));
        $slash = strpos($resourceUri, '/');
        $hash = substr($resourceUri, 0, $slash);
        $sourcePath = $this->resourceSourcePathByHash($hash);

        if (!$sourcePath) {
            return;
        }

        $filePath = substr($resourceUri, strlen($hash) + 1);
        if (!Path::ensurePathIsContained($filePath)) {
            throw new BadRequestHttpException('Invalid resource path: ' . $filePath);
        }

        // Publish the directory
        [$publishedDir] = $this->getAssetManager()->publish(Craft::getAlias($sourcePath));

        $publishedPath = $publishedDir . DIRECTORY_SEPARATOR . $filePath;
        if (!file_exists($publishedPath)) {
            throw new NotFoundHttpException("$filePath does not exist.");
        }

        $response = $this->getResponse();

        // Only set cache headers if GeneralConfig::buildId matches the requested URI.
        // This is to prevent caching a stale asset during a rolling deployment (https://github.com/craftcms/cms/issues/9140#issuecomment-877521916)
        if ($generalConfig->buildId && $generalConfig->buildId === $request->getQueryParam('buildId')) {
            $response->setCacheHeaders();
        }

        $response->sendFile($publishedPath, null, [
            'inline' => true,
        ]);
        $this->end();
    }

    private function resourceSourcePathByHash(string $hash): string|false
    {
        try {
            return (new Query())
                ->select(['path'])
                ->from(Table::RESOURCEPATHS)
                ->where(['hash' => $hash])
                ->scalar();
        } catch (DbException) {
            // Craft isn't installed yet. See if it's cached as a fallback.
            return Craft::$app->getCache()->get(Craft::$app->getAssetManager()->getCacheKeyForPathHash($hash));
        }
    }

    /**
     * Processes install requests.
     *
     * @param Request $request
     * @return null|BaseResponse
     * @throws NotFoundHttpException
     * @throws ServiceUnavailableHttpException
     * @throws YiiExitException
     */
    private function _processInstallRequest(Request $request): ?BaseResponse
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
        if ($isCpRequest && $request->getIsActionRequest() && ($request->getSegment(1) !== Request::CP_PATH_LOGIN)) {
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
            if (App::devMode()) {
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
     * @return BaseResponse|null
     * @throws Throwable if reasons
     */
    private function _processActionRequest(Request $request): ?BaseResponse
    {
        if ($request->getIsActionRequest()) {
            $route = implode('/', $request->getActionSegments());

            try {
                Craft::debug("Route requested: '$route'", __METHOD__);
                $this->requestedRoute = $route;
                $response = $this->runAction($route, $_GET);

                // Return the response for OPTIONS requests that return null
                // to support the CORS filter: https://www.yiiframework.com/doc/api/2.0/yii-filters-cors
                return $request->getIsOptions()
                    ? ($response ?? $this->getResponse())
                    : $response;
            } catch (Throwable $e) {
                $this->_unregisterDebugModule();
                if ($e instanceof InvalidRouteException) {
                    throw new NotFoundHttpException(Craft::t('yii', 'Page not found.'), $e->getCode(), $e);
                }
                throw $e;
            }
        }

        return null;
    }

    /**
     * If there is not cached app path or the existing cached app path does not match the current one, let’s run the
     * requirement checker again. This should catch the case where an install is deployed to another server that doesn’t
     * meet Craft’s minimum requirements.
     *
     * @param Request $request
     * @return BaseResponse|null
     */
    private function _processRequirementsCheck(Request $request): ?BaseResponse
    {
        // Only run for control panel requests and if we’re not in the middle of an update.
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
     * @return BaseResponse|null
     * @throws HttpException
     * @throws ServiceUnavailableHttpException
     */
    private function _processUpdateLogic(Request $request): ?BaseResponse
    {
        $this->_unregisterDebugModule();

        // Let all non-action control panel requests through.
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
            } catch (InvalidArgumentException) {
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
                $actionSegments === ['app', 'health-check'] ||
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
     * @inheritdoc
     */
    public function end($status = 0, $response = null)
    {
        // If we're already sending a template response, just throw an exception
        if (
            $this->state === self::STATE_SENDING_RESPONSE &&
            $this->getResponse()->format === TemplateResponseFormatter::FORMAT
        ) {
            throw new ExitException(output: ob_get_contents() ?: null);
        }

        parent::end($status, $response);
    }
}
