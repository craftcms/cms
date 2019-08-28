<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\web;

use Craft;
use craft\helpers\FileHelper;
use craft\helpers\Json;
use craft\helpers\UrlHelper;
use GuzzleHttp\Exception\ClientException;
use yii\base\Action;
use yii\base\InvalidArgumentException;
use yii\base\InvalidConfigException;
use yii\base\UserException;
use yii\web\BadRequestHttpException;
use yii\web\ForbiddenHttpException;
use yii\web\HttpException;
use yii\web\JsonResponseFormatter;
use yii\web\Response as YiiResponse;

/**
 * Controller is a base class that all controllers in Craft extend.
 * It extends Yii’s [[\yii\web\Controller]], overwriting specific methods as required.
 *
 * @property View $view The view object that can be used to render views or view files
 * @method View getView() Returns the view object that can be used to render views or view files
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
abstract class Controller extends \yii\web\Controller
{
    // Constants
    // =========================================================================

    const ALLOW_ANONYMOUS_NEVER = 0;
    const ALLOW_ANONYMOUS_LIVE = 1;
    const ALLOW_ANONYMOUS_OFFLINE = 2;

    // Properties
    // =========================================================================

    /**
     * @var int|bool|int[]|string[] Whether this controller’s actions can be accessed anonymously.
     *
     * This can be set to any of the following:
     *
     * - `false` or `self::ALLOW_ANONYMOUS_NEVER` (default) – indicates that all controller actions should never be
     *   accessed anonymously
     * - `true` or `self::ALLOW_ANONYMOUS_LIVE` – indicates that all controller actions can be accessed anonymously when
     *    the system is live
     * - `self::ALLOW_ANONYMOUS_OFFLINE` – indicates that all controller actions can be accessed anonymously when the
     *    system is offline
     * - `self::ALLOW_ANONYMOUS_LIVE | self::ALLOW_ANONYMOUS_OFFLINE` – indicates that all controller actions can be
     *    accessed anonymously when the system is live or offline
     * - An array of action IDs (e.g. `['save-guest-entry', 'edit-guest-entry']`) – indicates that the listed action IDs
     *   can be accessed anonymously when the system is live
     * - An array of action ID/bitwise pairs (e.g. `['save-guest-entry' => self::ALLOW_ANONYMOUS_OFFLINE]` – indicates
     *   that the listed action IDs can be accessed anonymously per the bitwise int assigned to it.
     */
    protected $allowAnonymous = self::ALLOW_ANONYMOUS_NEVER;

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     * @throws InvalidConfigException if [[$allowAnonymous]] is set to an invalid value
     */
    public function init()
    {
        // Normalize $allowAnonymous
        if (is_bool($this->allowAnonymous)) {
            $this->allowAnonymous = (int)$this->allowAnonymous;
        } else if (is_array($this->allowAnonymous)) {
            $normalized = [];
            foreach ($this->allowAnonymous as $k => $v) {
                if (
                    (is_int($k) && !is_string($v)) ||
                    (is_string($k) && !is_int($v))
                ) {
                    throw new InvalidArgumentException("Invalid \$allowAnonymous value for key \"{$k}\"");
                }
                if (is_int($k)) {
                    $normalized[$v] = self::ALLOW_ANONYMOUS_LIVE;
                } else {
                    $normalized[$k] = $v;
                }
            }
            $this->allowAnonymous = $normalized;
        } else if (!is_int($this->allowAnonymous)) {
            throw new InvalidConfigException('Invalid $allowAnonymous value');
        }

        parent::init();
    }

    /**
     * This method is invoked right before an action is executed.
     *
     * The method will trigger the [[EVENT_BEFORE_ACTION]] event. The return value of the method
     * will determine whether the action should continue to run.
     *
     * In case the action should not run, the request should be handled inside of the `beforeAction` code
     * by either providing the necessary output or redirecting the request. Otherwise the response will be empty.
     *
     * If you override this method, your code should look like the following:
     *
     * ```php
     * public function beforeAction($action)
     * {
     *     // your custom code here, if you want the code to run before action filters,
     *     // which are triggered on the [[EVENT_BEFORE_ACTION]] event, e.g. PageCache or AccessControl
     *
     *     if (!parent::beforeAction($action)) {
     *         return false;
     *     }
     *
     *     // other custom code here
     *
     *     return true; // or false to not run the action
     * }
     * ```
     *
     * @param Action $action the action to be executed.
     * @return bool whether the action should continue to run.
     * @throws BadRequestHttpException if the request is missing a valid CSRF token
     * @throws ForbiddenHttpException if the user is not logged in or locks the necessary permissions
     * @throws ServiceUnavailableHttpException if the system is offline and the user isn't allowed to access it
     */
    public function beforeAction($action)
    {
        $request = Craft::$app->getRequest();

        // Don't enable CSRF validation for Live Preview requests
        if ($request->getIsLivePreview()) {
            $this->enableCsrfValidation = false;
        }

        if (!parent::beforeAction($action)) {
            return false;
        }

        // Enforce $allowAnonymous
        $isLive = Craft::$app->getIsLive();
        $test = $isLive ? self::ALLOW_ANONYMOUS_LIVE : self::ALLOW_ANONYMOUS_OFFLINE;

        if (is_int($this->allowAnonymous)) {
            $allowAnonymous = $this->allowAnonymous;
        } else {
            $allowAnonymous = $this->allowAnonymous[$action->id] ?? self::ALLOW_ANONYMOUS_NEVER;
        }

        if (!($test & $allowAnonymous)) {
            // If this is a CP request, make sure they have access to the CP
            if ($request->getIsCpRequest()) {
                $this->requireLogin();
                $this->requirePermission('accessCp');
            } else if (Craft::$app->getUser()->getIsGuest()) {
                throw new ServiceUnavailableHttpException();
            }

            // If the system is offline, make sure they have permission to access the CP/site
            if (!$isLive) {
                $permission = $request->getIsCpRequest() ? 'accessCpWhenSystemIsOff' : 'accessSiteWhenSystemIsOff';
                if (!Craft::$app->getUser()->checkPermission($permission)) {
                    $error = $request->getIsCpRequest()
                        ? Craft::t('app', 'Your account doesn’t have permission to access the Control Panel when the system is offline.')
                        : Craft::t('app', 'Your account doesn’t have permission to access the site when the system is offline.');
                    throw new ServiceUnavailableHttpException($error);
                }
            }
        }

        return true;
    }

    /**
     * @inheritdoc
     */
    public function runAction($id, $params = [])
    {
        try {
            return parent::runAction($id, $params);
        } catch (\Throwable $e) {
            if (Craft::$app->getRequest()->getAcceptsJson()) {
                Craft::$app->getErrorHandler()->logException($e);
                if (!YII_DEBUG && !$e instanceof UserException) {
                    $message = Craft::t('app', 'An unknown error occurred.');
                } else {
                    $message = $e->getMessage();
                }
                if ($e instanceof ClientException) {
                    $statusCode = $e->getCode();
                    if (($response = $e->getResponse()) !== null) {
                        $body = Json::decodeIfJson((string)$response->getBody());
                        if (isset($body['message'])) {
                            $message = $body['message'];
                        }
                    }
                } else if ($e instanceof HttpException) {
                    $statusCode = $e->statusCode;
                } else {
                    $statusCode = 500;
                }
                return $this->asErrorJson($message)
                    ->setStatusCode($statusCode);
            }
            throw $e;
        }
    }

    /**
     * Renders a template.
     *
     * @param string $template The name of the template to load
     * @param array $variables The variables that should be available to the template
     * @return YiiResponse
     * @throws InvalidArgumentException if the view file does not exist.
     */
    public function renderTemplate(string $template, array $variables = []): YiiResponse
    {
        $response = Craft::$app->getResponse();
        $headers = $response->getHeaders();

        // Set the MIME type for the request based on the matched template's file extension (unless the
        // Content-Type header was already set, perhaps by the template via the {% header %} tag)
        if (!$headers->has('content-type')) {
            $templateFile = Craft::$app->getView()->resolveTemplate($template);
            $extension = pathinfo($templateFile, PATHINFO_EXTENSION) ?: 'html';

            if (($mimeType = FileHelper::getMimeTypeByExtension('.' . $extension)) === null) {
                $mimeType = 'text/html';
            }

            $headers->set('content-type', $mimeType . '; charset=' . $response->charset);
        }

        // Render and return the template
        $response->data = $this->getView()->renderPageTemplate($template, $variables);

        // Prevent a response formatter from overriding the content-type header
        $response->format = YiiResponse::FORMAT_RAW;

        return $response;
    }

    /**
     * Redirects the user to the login template if they're not logged in.
     */
    public function requireLogin()
    {
        $userSession = Craft::$app->getUser();

        if ($userSession->getIsGuest()) {
            $userSession->loginRequired();
            Craft::$app->end();
        }
    }

    /**
     * Throws a 403 error if the current user is not an admin.
     *
     * @param bool $requireAdminChanges Whether the [[\craft\config\GeneralConfig::$allowAdminChanges|`allowAdminChanges`]]
     * config setting must also be enabled.
     * @throws ForbiddenHttpException if the current user is not an admin
     */
    public function requireAdmin(bool $requireAdminChanges = true)
    {
        // First make sure someone's actually logged in
        $this->requireLogin();

        // Make sure they're an admin
        if (!Craft::$app->getUser()->getIsAdmin()) {
            throw new ForbiddenHttpException('User is not permitted to perform this action.');
        }

        // Make sure admin changes are allowed
        if ($requireAdminChanges && !Craft::$app->getConfig()->getGeneral()->allowAdminChanges) {
            throw new ForbiddenHttpException('Administrative changes are disallowed in this environment.');
        }
    }

    /**
     * Checks whether the current user has a given permission, and ends the request with a 403 error if they don’t.
     *
     * @param string $permissionName The name of the permission.
     * @throws ForbiddenHttpException if the current user doesn’t have the required permission
     */
    public function requirePermission(string $permissionName)
    {
        if (!Craft::$app->getUser()->checkPermission($permissionName)) {
            throw new ForbiddenHttpException('User is not permitted to perform this action');
        }
    }

    /**
     * Checks whether the current user can perform a given action, and ends the request with a 403 error if they don’t.
     *
     * @param string $action The name of the action to check.
     * @throws ForbiddenHttpException if the current user is not authorized
     */
    public function requireAuthorization(string $action)
    {
        if (!Craft::$app->getSession()->checkAuthorization($action)) {
            throw new ForbiddenHttpException('User is not authorized to perform this action');
        }
    }

    /**
     * Requires that the user has an elevated session.
     *
     * @throws ForbiddenHttpException if the current user does not have an elevated session
     */
    public function requireElevatedSession()
    {
        if (!Craft::$app->getUser()->getHasElevatedSession()) {
            throw new ForbiddenHttpException(Craft::t('app', 'This action may only be performed with an elevated session.'));
        }
    }

    /**
     * Throws a 400 error if this isn’t a POST request
     *
     * @throws BadRequestHttpException if the request is not a post request
     */
    public function requirePostRequest()
    {
        if (!Craft::$app->getRequest()->getIsPost()) {
            throw new BadRequestHttpException('Post request required');
        }
    }

    /**
     * Throws a 400 error if the request doesn't accept JSON.
     *
     * @throws BadRequestHttpException if the request doesn't accept JSON
     */
    public function requireAcceptsJson()
    {
        if (!Craft::$app->getRequest()->getAcceptsJson()) {
            throw new BadRequestHttpException('Request must accept JSON in response');
        }
    }

    /**
     * Throws a 400 error if the current request doesn’t have a valid Craft token.
     *
     * @throws BadRequestHttpException if the request does not have a valid Craft token
     */
    public function requireToken()
    {
        if (Craft::$app->getRequest()->getToken() === null) {
            throw new BadRequestHttpException('Valid token required');
        }
    }

    /**
     * Throws a 400 error if the current request isn’t a Control Panel request.
     *
     * @throws BadRequestHttpException if the request is not a CP request
     */
    public function requireCpRequest()
    {
        if (!Craft::$app->getRequest()->getIsCpRequest()) {
            throw new BadRequestHttpException('Request must be a Control Panel request');
        }
    }

    /**
     * Throws a 400 error if the current request isn’t a site request.
     *
     * @throws BadRequestHttpException if the request is not a site request
     */
    public function requireSiteRequest()
    {
        if (!Craft::$app->getRequest()->getIsSiteRequest()) {
            throw new BadRequestHttpException('Request must be a site request');
        }
    }

    /**
     * Redirects to the URI specified in the POST.
     *
     * @param mixed $object Object containing properties that should be parsed for in the URL.
     * @param string|null $default The default URL to redirect them to, if no 'redirect' parameter exists. If this is left
     * null, then the current request’s path will be used.
     * @return YiiResponse
     * @throws BadRequestHttpException if the redirect param was tampered with
     */
    public function redirectToPostedUrl($object = null, string $default = null): YiiResponse
    {
        $url = Craft::$app->getRequest()->getValidatedBodyParam('redirect');

        if ($url === null) {
            if ($default !== null) {
                $url = $default;
            } else {
                $url = Craft::$app->getRequest()->getPathInfo();
            }
        }

        if ($object) {
            $url = Craft::$app->getView()->renderObjectTemplate($url, $object);
        }

        return $this->redirect($url);
    }

    /** @noinspection ArrayTypeOfParameterByDefaultValueInspection */
    /**
     * Sets the response format of the given data as JSONP.
     *
     * @param mixed $data The data that should be formatted.
     * @return YiiResponse A response that is configured to send `$data` formatted as JSON.
     * @see YiiResponse::$format
     * @see YiiResponse::FORMAT_JSONP
     * @see JsonResponseFormatter
     */
    public function asJsonP($data): YiiResponse
    {
        $response = Craft::$app->getResponse();
        $response->data = $data;
        $response->format = YiiResponse::FORMAT_JSONP;

        return $response;
    }

    /** @noinspection ArrayTypeOfParameterByDefaultValueInspection */
    /**
     * Sets the response format of the given data as RAW.
     *
     * @param mixed $data The data that should *not* be formatted.
     * @return YiiResponse A response that is configured to send `$data` without formatting.
     * @see YiiResponse::$format
     * @see YiiResponse::FORMAT_RAW
     */
    public function asRaw($data): YiiResponse
    {
        $response = Craft::$app->getResponse();
        $response->data = $data;
        $response->format = YiiResponse::FORMAT_RAW;

        return $response;
    }

    /**
     * Responds to the request with a JSON error message.
     *
     * @param string $error The error message.
     * @return YiiResponse
     */
    public function asErrorJson(string $error): YiiResponse
    {
        return $this->asJson(['error' => $error]);
    }

    /**
     * @inheritdoc
     * @return YiiResponse
     */
    public function redirect($url, $statusCode = 302): YiiResponse
    {
        if (is_string($url)) {
            $url = UrlHelper::url($url);
        }

        if ($url !== null) {
            return Craft::$app->getResponse()->redirect($url, $statusCode);
        }

        return $this->goHome();
    }
}
