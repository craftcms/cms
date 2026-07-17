<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\web;

use craft\base\ApplicationTrait;
use craft\errors\ExitException;
use craft\helpers\App;
use craft\queue\QueueLogBehavior;
use CraftCms\Aliases\Aliases;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Http\Routing\ActionRoute;
use CraftCms\Cms\Plugin\Plugins;
use CraftCms\Cms\Route\TemplateRoute;
use CraftCms\Cms\Site\Sites;
use CraftCms\Cms\Support\Url;
use CraftCms\Yii2Adapter\Http\CaptureOriginalActionRequestUri;
use CraftCms\Yii2Adapter\Web\Response as IlluminateBridgeResponse;
use Illuminate\Http\Request as IlluminateRequest;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use IntlDateFormatter;
use IntlException;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
use Throwable;
use yii\base\Component;
use yii\base\ExitException as YiiExitException;
use yii\base\InvalidArgumentException;
use yii\base\InvalidRouteException;
use yii\web\BadRequestHttpException;
use yii\web\NotFoundHttpException;
use yii\web\Response as BaseResponse;
use function CraftCms\Cms\t;

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

        $this->_postInit();

        // If there's an invalid token on the request, throw an exception now
        if ($this->getRequest()->getHasInvalidToken()) {
            throw new BadRequestHttpException('Invalid token');
        }

        // Process resource requests before we do anything to establish the user session
        $this->_processResourceRequest();
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
     * @deprecated 6.0.0 use `Cms::timezone()` instead.
     */
    public function getTimeZone(): string
    {
        return Cms::timezone();
    }

    /**
     * @inheritdoc
     * @deprecated 6.0.0
     */
    public function setTimeZone($value): void
    {
        parent::setTimeZone($value);

        if ($value !== 'UTC') {
            // Make sure that ICU supports this timezone
            try {
                new IntlDateFormatter(app()->getLocale(), IntlDateFormatter::NONE, IntlDateFormatter::NONE);
            } catch (IntlException) {
                Log::info("Time zone “{$value}” does not appear to be supported by ICU: " . intl_get_error_message());
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
            $generalConfig = Cms::config();

            // Process install requests
            if (($response = $this->_processInstallRequest($request)) !== null) {
                return $response;
            }

            if (!$request->getIsActionRequest()) {
                $userSession = $this->getUser();

                // If this is a plugin template request, make sure the user has access to the plugin
                // If this is a non-login, non-validate, non-setPassword control panel request, make sure the user has access to the control panel
                if (
                    $isCpRequest &&
                    ($firstSeg = $request->getSegment(1)) !== null &&
                    ($plugin = app(Plugins::class)->getPlugin($firstSeg)) !== null
                ) {
                    if (Auth::guest()) {
                        return $userSession->loginRequired();
                    }

                    Gate::authorize('accessPlugin-' . $plugin->handle);
                }
            }
        }

        // If this is an action request, call the controller
        if (($response = $this->_processActionRequest($request)) !== null) {
            return $response;
        }

        if ($request->getIsActionRequest() && request()->attributes->has(CaptureOriginalActionRequestUri::ORIGINAL_ACTION_REQUEST_URI)) {
            return $this->handleOriginalLaravelActionRequest();
        }

        // If we’re still here, finally let Yii do its thing.
        return parent::handleRequest($request);
    }

    /**
     * @inheritdoc
     * @param string $route
     * @param array $params
     * @return BaseResponse|null The result of the action, normalized into a Response object
     */
    public function runAction($route, $params = []): ?BaseResponse
    {
        if ($route === 'templates/render') {
            return $this->runTemplateRoute($params);
        }

        try {
            $result = parent::runAction($route, $params);
        } catch (InvalidRouteException $e) {
            [$handled, $result] = $this->runDefaultControllerAction($route, $params);

            if (!$handled) {
                if (($response = $this->runLaravelAction($route, $params)) !== null) {
                    return $response;
                }

                throw $e;
            }
        }

        if ($result === null || $result instanceof BaseResponse) {
            return $result;
        }

        $response = $this->getResponse();
        $response->data = $result;
        return $response;
    }

    /**
     * @return array{bool, mixed}
     */
    private function runDefaultControllerAction(string $route, array $params = []): array
    {
        $segments = explode('/', $route);

        if (count($segments) !== 2 || !$this->hasModule($segments[0])) {
            return [false, null];
        }

        try {
            return [true, parent::runAction(sprintf('%s/default/%s', $segments[0], $segments[1]), $params)];
        } catch (InvalidRouteException) {
            return [false, null];
        }
    }

    private function runTemplateRoute(array $params = []): BaseResponse
    {
        $laravelResponse = new TemplateRoute(
            $params['template'],
            $params['variables'] ?? [],
            publicOnly: false,
        )->handle(request());

        $response = $this->getResponse();

        if ($response instanceof IlluminateBridgeResponse) {
            return $response->setIlluminateResponse($laravelResponse);
        }

        $response->data = $laravelResponse->getContent();
        $response->setStatusCode($laravelResponse->getStatusCode());

        return $response;
    }

    private function runLaravelAction(string $route, array $params = []): ?BaseResponse
    {
        $actionUri = ActionRoute::uriForSegments(explode('/', $route), request()->isCpRequest());

        if (empty($actionUri)) {
            return null;
        }

        /** @var IlluminateRequest $request */
        $request = request();

        if ($request->headers->has('X-Craft-Legacy-Action-Bridge')) {
            return null;
        }

        $query = array_merge($request->query(), $params);

        unset($query['action'], $query['p']);

        $internalRequest = $request->duplicate(
            query: $params,
            request: $request->post(),
            server: array_merge($request->server->all(), [
                'REQUEST_URI' => $actionUri,
                'HTTP_X_CRAFT_LEGACY_ACTION_BRIDGE' => '1',
            ]),
        );

        if ($request->hasSession()) {
            $internalRequest->setLaravelSession($request->session());
        }

        /** @var SymfonyResponse $laravelResponse */
        $laravelResponse = app(Router::class)->dispatch($internalRequest);

        $response = $this->getResponse();

        if ($response instanceof IlluminateBridgeResponse) {
            return $response->setIlluminateResponse($laravelResponse);
        }

        return $response;
    }

    private function handleOriginalLaravelActionRequest(): BaseResponse
    {
        /** @var IlluminateRequest $request */
        $request = request();
        $originalUri = (string) $request->attributes->get(CaptureOriginalActionRequestUri::ORIGINAL_ACTION_REQUEST_URI);

        $originalRequest = $request->duplicateWithUri($originalUri);
        $originalRequest->query->remove('action');
        $originalRequest->request->remove('action');

        app()->instance('request', $originalRequest);

        $template = $originalRequest->isSiteRequest()
            ? app(Sites::class)->getRequestPath($originalRequest)
            : $originalRequest->craftPath();

        $laravelResponse = new TemplateRoute(
            $template,
            $this->getUrlManager()->getRouteParams() ?? [],
        )->handle($originalRequest);

        $response = $this->getResponse();

        if ($response instanceof IlluminateBridgeResponse) {
            return $response->setIlluminateResponse($laravelResponse);
        }

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
     * Processes resource requests.
     *
     * @throws BadRequestHttpException
     * @throws NotFoundHttpException
     */
    private function _processResourceRequest(): void
    {
        $generalConfig = Cms::config();
        $request = $this->getRequest();

        // Does this look like a resource request?
        $resourceBaseUri = parse_url(Aliases::get($generalConfig->resourceBaseUrl), PHP_URL_PATH);
        $requestPath = $request->getFullPath();
        if (!str_starts_with('/' . $requestPath, $resourceBaseUri . '/')) {
            return;
        }

        $resourceUri = substr($requestPath, strlen($resourceBaseUri));

        try {
            $publishedPath = App::resourcePathByUri($resourceUri);
        } catch (InvalidArgumentException $e) {
            throw new BadRequestHttpException($e->getMessage(), previous: $e);
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
        $isInstalled = Cms::isInstalled();

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
            if (app()->hasDebugModeEnabled()) {
                $url = Url::url('install');
                $this->getResponse()->redirect($url);
                $this->end();
            }

            throw new ServiceUnavailableHttpException(t('Craft isn’t installed yet.'));
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
                Log::debug("Route requested: '$route'", [__METHOD__]);
                $this->requestedRoute = $route;
                $response = $this->runAction($route, $_GET);

                // Return the response for OPTIONS requests that return null
                // to support the CORS filter: https://www.yiiframework.com/doc/api/2.0/yii-filters-cors
                return $request->getIsOptions()
                    ? ($response ?? $this->getResponse())
                    : $response;
            } catch (Throwable $e) {
                if ($e instanceof InvalidRouteException) {
                    throw new NotFoundHttpException(t('Page not found.'), $e->getCode(), $e);
                }
                throw $e;
            }
        }

        return null;
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
