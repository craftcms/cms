<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\web;

use Craft;
use craft\base\ApplicationTrait;
use craft\base\Plugin;
use craft\helpers\App;
use craft\helpers\FileHelper;
use craft\helpers\UrlHelper;
use yii\base\InvalidRouteException;
use yii\web\ForbiddenHttpException;
use yii\web\HttpException;
use yii\web\NotFoundHttpException;
use yii\web\Response;

/**
 * Craft Web Application class
 *
 * @property Request             $request          The request component
 * @property \craft\web\Response $response         The response component
 * @property Session             $session          The session component
 * @property UrlManager          $urlManager       The URL manager for this application
 * @property User                $user             The user component
 *
 * @method Request                                getRequest()      Returns the request component.
 * @method \craft\web\Response                    getResponse()     Returns the response component.
 * @method Session                                getSession()      Returns the session component.
 * @method UrlManager                             getUrlManager()   Returns the URL manager for this application.
 * @method User                                   getUser()         Returns the user component.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class Application extends \yii\web\Application
{
    // Traits
    // =========================================================================

    use ApplicationTrait;

    // Constants
    // =========================================================================

    /**
     * @event \yii\base\Event The event that is triggered after the application has been initialized
     */
    const EVENT_AFTER_INIT = 'afterInit';

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
     *
     * @return void
     */
    public function init()
    {
        parent::init();

        $this->_init();
    }

    /**
     * Handles the specified request.
     *
     * @param Request $request the request to be handled
     *
     * @return Response the resulting response
     * @throws HttpException
     * @throws ServiceUnavailableHttpException
     * @throws \craft\errors\DbConnectException
     * @throws ForbiddenHttpException
     * @throws \yii\web\NotFoundHttpException
     */
    public function handleRequest($request): Response
    {
        // If this is a resource request, we should respond with the resource ASAP
        $this->_processResourceRequest();

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
            $headers->set('X-Powered-By', $this->name);
        } else {
            // In case PHP is already setting one
            header_remove('X-Powered-By');
        }

        // If the system in is maintenance mode and it's a site request, throw a 503.
        if ($this->getIsInMaintenanceMode() && $request->getIsSiteRequest()) {
            $this->_unregisterDebugModule();
            throw new ServiceUnavailableHttpException();
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
        if (!$this->getUpdates()->getIsSchemaVersionCompatible()) {
            $this->_unregisterDebugModule();

            if ($request->getIsCpRequest()) {
                $version = $this->getInfo()->version;
                $url = App::craftDownloadUrl($version);

                throw new HttpException(200, Craft::t('app', 'Craft CMS does not support backtracking to this version. Please upload Craft CMS {url} or later.', [
                    'url' => "[{$version}]({$url})",
                ]));
            } else {
                throw new ServiceUnavailableHttpException();
            }
        }

        // getIsCraftDbMigrationNeeded will return true if we're in the middle of a manual or auto-update for Craft itself.
        // If we're in maintenance mode and it's not a site request, show the manual update template.
        if ($this->getIsUpdating()) {
            return $this->_processUpdateLogic($request) ?: $this->getResponse();
        }

        // If there's a new version, but the schema hasn't changed, just update the info table
        if ($this->getUpdates()->getHasCraftVersionChanged()) {
            $this->getUpdates()->updateCraftVersionInfo();

            // Clear the template caches in case they've been compiled since this release was cut.
            FileHelper::clearDirectory($this->getPath()->getCompiledTemplatesPath());
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
                $plugin = $plugin = $this->getPlugins()->getPlugin($firstSeg);

                if ($plugin && !$user->checkPermission('accessPlugin-'.$plugin->handle)) {
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
     * Tries to find a match between the browser's preferred languages and the languages Craft has been translated into.
     *
     * @return string|false
     */
    public function getTranslatedBrowserLanguage()
    {
        $browserLanguages = $this->getRequest()->getAcceptableLanguages();

        if (!empty($browserLanguages)) {
            $appLanguages = $this->getI18n()->getAppLocaleIds();

            foreach ($browserLanguages as $language) {
                if (in_array($language, $appLanguages, true)) {
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
     * @param array  $params
     *
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
     *
     * @todo Remove this whenever Yii is updated with support for asset-packagist.org.
     */
    public function setVendorPath($path)
    {
        parent::setVendorPath($path);

        // Override the @bower and @npm aliases if using asset-packagist.org
        $altBowerPath = $this->getVendorPath().DIRECTORY_SEPARATOR.'bower-asset';
        $altNpmPath = $this->getVendorPath().DIRECTORY_SEPARATOR.'npm-asset';

        if (is_dir($altBowerPath)) {
            Craft::setAlias('@bower', $altBowerPath);
        }

        if (is_dir($altNpmPath)) {
            Craft::setAlias('@npm', $altNpmPath);
        }
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
     * @throws HttpException
     * @return void
     */
    private function _processResourceRequest()
    {
        $request = $this->getRequest();

        if ($request->getIsResourceRequest()) {
            // Get the path segments, except for the first one which we already know is "resources"
            $segs = array_slice(array_merge($request->getSegments()), 1);
            $uri = implode('/', $segs);

            $this->getResources()->sendResource($uri);
        }
    }

    /**
     * Processes install requests.
     *
     * @param Request $request
     *
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

        // Are they requesting an installer template/action specifically?
        if ($isCpRequest && $request->getSegment(1) === 'install' && !$isInstalled) {
            $action = $request->getSegment(2) ?: 'index';

            return $this->runAction('install/'.$action);
        }

        if ($isCpRequest && $request->getIsActionRequest() && ($request->getSegment(1) !== 'login')) {
            $actionSegs = $request->getActionSegments();
            if (isset($actionSegs[0]) && $actionSegs[0] === 'install') {
                return $this->_processActionRequest($request);
            }
        }

        // Should they be?
        if (!$isInstalled) {
            // Give it to them if accessing the CP
            if ($isCpRequest) {
                $url = UrlHelper::url('install');
                $this->getResponse()->redirect($url);
                $this->end();
            } // Otherwise return a 503
            else {
                throw new ServiceUnavailableHttpException();
            }
        }

        return null;
    }

    /**
     * Processes action requests.
     *
     * @param Request $request
     *
     * @return Response|null
     * @throws NotFoundHttpException if the requested action route is invalid
     */
    private function _processActionRequest(Request $request)
    {
        if ($request->getIsActionRequest()) {
            $route = implode('/', $request->getActionSegments());

            try {
                Craft::trace("Route requested: '$route'", __METHOD__);
                $this->requestedRoute = $route;

                return $this->runAction($route, $_GET);
            } catch (InvalidRouteException $e) {
                throw new NotFoundHttpException(Craft::t('yii', 'Page not found.'), $e->getCode(), $e);
            }
        }

        return null;
    }

    /**
     * @param Request $request
     *
     * @return bool
     */
    private function _isSpecialCaseActionRequest(Request $request): bool
    {
        $segments = $request->getActionSegments();

        return (
            $segments === ['users', 'login'] ||
            $segments === ['users', 'logout'] ||
            $segments === ['users', 'set-password'] ||
            $segments === ['users', 'verify-email'] ||
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
     * @param Request $request
     *
     * @return Response|null
     */
    private function _processRequirementsCheck(Request $request)
    {
        // See if we're in the middle of an update.
        $update = false;

        if ($request->getSegment(1) === 'updates' && $request->getSegment(2) === 'go') {
            $update = true;
        }

        if (($data = $request->getBodyParam('data', null)) !== null && isset($data['handle'])) {
            $update = true;
        }

        // Only run for CP requests and if we're not in the middle of an update.
        if ($request->getIsCpRequest() && !$update) {
            $cachedBasePath = $this->getCache()->get('basePath');

            if ($cachedBasePath === false || $cachedBasePath !== $this->getBasePath()) {
                return $this->runAction('templates/requirements-check');
            }
        }

        return null;
    }

    /**
     * @param Request $request
     *
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
            (!$request->getIsActionRequest() || $request->getActionSegments() == [
                    'users',
                    'login'
                ])
        ) {
            // If this is a request to actually manually update Craft, do it
            if ($request->getSegment(1) === 'manualupdate') {
                return $this->runAction('update/go', [
                    'handle' => Craft::$app->getRequest()->getSegment(2)
                ]);
            }

            if ($this->getUpdates()->getIsBreakpointUpdateNeeded()) {
                $minVersionUrl = App::craftDownloadUrl($this->minVersionRequired);
                throw new HttpException(200, Craft::t('app', 'You need to be on at least Craft CMS {url} before you can manually update to Craft CMS {targetVersion}.', [
                    'url' => "[{$this->minVersionRequired}]($minVersionUrl)",
                    'targetVersion' => Craft::$app->version,
                ]));
            } else {
                if (!$request->getIsAjax()) {
                    if ($request->getPathInfo() !== '') {
                        $this->getUser()->setReturnUrl($request->getPathInfo());
                    }
                }

                // Clear the template caches in case they've been compiled since this release was cut.
                FileHelper::clearDirectory($this->getPath()->getCompiledTemplatesPath());

                // Show the manual update notification template
                return $this->runAction('templates/manual-update-notification');
            }
        } // We'll also let action requests to UpdateController through as well.
        else if ($request->getIsActionRequest() && (($actionSegs = $request->getActionSegments()) !== null) && isset($actionSegs[0]) && $actionSegs[0] === 'update') {
            $controller = $actionSegs[0];
            $action = $actionSegs[1] ?? 'index';

            return $this->runAction($controller.'/'.$action);
        }

        // If an exception gets throw during the rendering of the 503 template, let
        // TemplatesController->actionRenderError() take care of it.
        throw new ServiceUnavailableHttpException();
    }

    /**
     * Checks if the system is off, and if it is, enforces the "Access the site/CP when the system is off" permissions.
     *
     * @param Request $request
     *
     * @return void
     * @throws ServiceUnavailableHttpException
     */
    private function _enforceSystemStatusPermissions(Request $request)
    {
        if (!$this->_checkSystemStatusPermissions()) {
            $error = null;

            if (!$this->getUser()->getIsGuest()) {
                if ($request->getIsCpRequest()) {
                    $error = Craft::t('app', 'Your account doesn’t have permission to access the Control Panel when the system is offline.');
                    $logoutUrl = UrlHelper::cpUrl('logout');
                } else {
                    $error = Craft::t('app', 'Your account doesn’t have permission to access the site when the system is offline.');
                    $logoutUrl = UrlHelper::siteUrl(Craft::$app->getConfig()->getGeneral()->getLogoutPath());
                }

                $error .= ' ['.Craft::t('app', 'Log out?').']('.$logoutUrl.')';
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
     * @return bool
     */
    private function _checkSystemStatusPermissions(): bool
    {
        if ($this->getIsSystemOn()) {
            return true;
        }

        $request = $this->getRequest();
        $actionTrigger = $this->getConfig()->getGeneral()->actionTrigger;

        if ($request->getIsCpRequest() ||

            // Special case because we hide the cpTrigger in emails.
            $request->getPathInfo() === $actionTrigger.'/users/set-password' ||
            $request->getPathInfo() === $actionTrigger.'/users/verify-email' ||
            // Special case because this might be a request with a user that has "Access the site when the system is off"
            // permissions and is in the process of logging in while the system is off.
            $request->getActionSegments() == ['users', 'login']
        ) {
            if ($this->getUser()->checkPermission('accessCpWhenSystemIsOff')) {
                return true;
            }

            if ($request->getSegment(1) === 'manualupdate') {
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
                )
            ) {
                return true;
            }
        } else {
            if ($this->getUser()->checkPermission('accessSiteWhenSystemIsOff')) {
                return true;
            }
        }

        return false;
    }
}
