<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\web;

use Craft;
use craft\base\ModelInterface;
use craft\elements\User;
use yii\base\Action;
use yii\base\InvalidArgumentException;
use yii\base\InvalidConfigException;
use yii\base\Model;
use yii\web\BadRequestHttpException;
use yii\web\ForbiddenHttpException;
use yii\web\JsonResponseFormatter;
use yii\web\Response as YiiResponse;
use yii\web\UnauthorizedHttpException;

/**
 * Controller is a base class that all controllers in Craft extend.
 * It extends Yii’s [[\yii\web\Controller]], overwriting specific methods as required.
 *
 * @property Request $request
 * @property Response $response
 * @property View $view The view object that can be used to render views or view files
 * @method View getView() Returns the view object that can be used to render views or view files
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
abstract class Controller extends \yii\web\Controller
{
    public const ALLOW_ANONYMOUS_NEVER = 0;
    public const ALLOW_ANONYMOUS_LIVE = 1;
    public const ALLOW_ANONYMOUS_OFFLINE = 2;

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
    protected array|bool|int $allowAnonymous = self::ALLOW_ANONYMOUS_NEVER;

    /**
     * @inheritdoc
     * @throws InvalidConfigException if [[$allowAnonymous]] is set to an invalid value
     */
    public function init(): void
    {
        // Normalize $allowAnonymous
        if (is_bool($this->allowAnonymous)) {
            $this->allowAnonymous = (int)$this->allowAnonymous;
        } elseif (is_array($this->allowAnonymous)) {
            $normalized = [];
            foreach ($this->allowAnonymous as $k => $v) {
                if (
                    (is_int($k) && !is_string($v)) ||
                    (is_string($k) && !is_int($v))
                ) {
                    throw new InvalidArgumentException("Invalid \$allowAnonymous value for key \"$k\"");
                }
                if (is_int($k)) {
                    $normalized[$v] = self::ALLOW_ANONYMOUS_LIVE;
                } else {
                    $normalized[$k] = $v;
                }
            }
            $this->allowAnonymous = $normalized;
        } elseif (!is_int($this->allowAnonymous)) {
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
     * public function beforeAction($action): bool
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
     * @throws ForbiddenHttpException if the user is not logged in or lacks the necessary permissions
     * @throws ServiceUnavailableHttpException if the system is offline and the user isn't allowed to access it
     * @throws UnauthorizedHttpException
     */
    public function beforeAction($action): bool
    {
        // Don't enable CSRF validation for Live Preview requests
        if ($this->request->getIsLivePreview()) {
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
            // If this is a control panel request, make sure they have access to the control panel
            if ($this->request->getIsCpRequest()) {
                $this->requireLogin();
                $this->requirePermission('accessCp');
            } elseif (Craft::$app->getUser()->getIsGuest()) {
                if ($isLive) {
                    throw new ForbiddenHttpException();
                } else {
                    $retryDuration = Craft::$app->getProjectConfig()->get('system.retryDuration');
                    if ($retryDuration) {
                        $this->response->getHeaders()->setDefault('Retry-After', $retryDuration);
                    }
                    throw new ServiceUnavailableHttpException();
                }
            }

            // If the system is offline, make sure they have permission to access the control panel/site
            if (!$isLive) {
                $permission = $this->request->getIsCpRequest() ? 'accessCpWhenSystemIsOff' : 'accessSiteWhenSystemIsOff';
                if (!Craft::$app->getUser()->checkPermission($permission)) {
                    $error = $this->request->getIsCpRequest()
                        ? Craft::t('app', 'Your account doesn’t have permission to access the control panel when the system is offline.')
                        : Craft::t('app', 'Your account doesn’t have permission to access the site when the system is offline.');
                    throw new ServiceUnavailableHttpException($error);
                }
            }
        }

        return true;
    }

    /**
     * Returns the currently logged-in user.
     *
     * @param bool $autoRenew
     * @return ?User
     * @see \yii\web\User::getIdentity()
     * @since 4.3.0
     */
    public static function currentUser(bool $autoRenew = true): ?User
    {
        return Craft::$app->getUser()->getIdentity($autoRenew);
    }

    /**
     * Sends a rendered template response.
     *
     * @param string $template The name of the template to load
     * @param array $variables The variables that should be available to the template
     * @param string|null $templateMode The template mode to use
     * @return YiiResponse
     * @throws InvalidArgumentException if the view file does not exist.
     */
    public function renderTemplate(string $template, array $variables = [], ?string $templateMode = null): YiiResponse
    {
        $this->response->attachBehavior(TemplateResponseBehavior::NAME, [
            'class' => TemplateResponseBehavior::class,
            'template' => $template,
            'variables' => $variables,
            'templateMode' => $templateMode,
        ]);
        $this->response->formatters[TemplateResponseFormatter::FORMAT] = TemplateResponseFormatter::class;
        $this->response->format = TemplateResponseFormatter::FORMAT;
        return $this->response;
    }

    /**
     * Sends a control panel screen response.
     *
     * @return Response
     * @since 4.0.0
     */
    public function asCpScreen(): Response
    {
        $this->response->attachBehavior(CpScreenResponseBehavior::NAME, CpScreenResponseBehavior::class);
        $this->response->formatters[CpScreenResponseFormatter::FORMAT] = CpScreenResponseFormatter::class;
        $this->response->format = CpScreenResponseFormatter::FORMAT;
        return $this->response;
    }

    /**
     * Sends a failure response.
     *
     * @param string|null $message
     * @param array $data Additional data to include in the JSON response
     * @param array $routeParams The route params to send back to the template
     * @return YiiResponse|null
     * @since 4.0.0
     */
    public function asFailure(
        ?string $message = null,
        array $data = [],
        array $routeParams = [],
    ): ?YiiResponse {
        if ($this->request->getAcceptsJson()) {
            $this->response->setStatusCode(400);
            return $this->asJson($data + array_filter([
                    'message' => $message,
                ]));
        }

        $this->setFailFlash($message);

        if (!empty($routeParams)) {
            Craft::$app->getUrlManager()->setRouteParams($routeParams);
        }

        return null;
    }

    /**
     * Sends a success response.
     *
     * @param string|null $message
     * @param array $data Additional data to include in the JSON response
     * @param string|null $redirect The URL to redirect the request
     * @param array $notificationSettings Control panel notification settings
     * @return YiiResponse|null
     * @since 4.0.0
     */
    public function asSuccess(
        ?string $message = null,
        array $data = [],
        ?string $redirect = null,
        array $notificationSettings = [],
    ): ?YiiResponse {
        if ($this->request->getAcceptsJson()) {
            $data += array_filter([
                'message' => $message,
                'redirect' => $redirect,
            ]);
            if ($notificationSettings && $this->request->getIsCpRequest()) {
                $data += [
                    'notificationSettings' => $notificationSettings,
                ];
            }
            return $this->asJson($data);
        }

        $this->setSuccessFlash($message, $notificationSettings);

        if ($redirect !== null) {
            return $this->redirect($redirect);
        }

        return $this->redirectToPostedUrl();
    }

    /**
     * Sends a failure response for a model.
     *
     * @param Model|ModelInterface $model The model that was being operated on
     * @param string|null $message
     * @param string|null $modelName The route param name that the model should be set to
     * @param array $data Additional data to include in the JSON response
     * @param array $routeParams Additional route params that should be set for the next controller action
     * @return YiiResponse|null
     * @since 4.0.0
     */
    public function asModelFailure(
        Model|ModelInterface $model,
        ?string $message = null,
        ?string $modelName = null,
        array $data = [],
        array $routeParams = [],
    ): ?YiiResponse {
        $modelName = $modelName ?? 'model';
        $routeParams += [$modelName => $model];
        $data += [
            'modelName' => $modelName,
            $modelName => $model->toArray(),
            'errors' => $model->getErrors(),
        ];

        return $this->asFailure(
            $message,
            $data,
            $routeParams,
        );
    }

    /**
     * Sends a success response for a model.
     *
     * @param Model|ModelInterface $model The model that was being operated on
     * @param string|null $message
     * @param string|null $modelName The route param name that the model should be set to
     * @param array $data Additional data to include in the JSON response
     * @param string|null $redirect The default URL to redirect the request
     * @return YiiResponse
     * @since 4.0.0
     */
    public function asModelSuccess(
        Model|ModelInterface $model,
        ?string $message = null,
        ?string $modelName = null,
        array $data = [],
        ?string $redirect = null,
    ): YiiResponse {
        $data += array_filter([
            'modelName' => $modelName,
            ($modelName ?? 'model') => $model->toArray(),
        ]);

        return $this->asSuccess(
            $message,
            $data,
            $redirect ?? $this->getPostedRedirectUrl($model),
        );
    }

    /**
     * Redirects the user to the login template if they're not logged in.
     */
    public function requireLogin(): void
    {
        $userSession = Craft::$app->getUser();

        if ($userSession->getIsGuest()) {
            $userSession->loginRequired();
            Craft::$app->end();
        }
    }

    /**
     * Redirects the user to the account template if they are logged in.
     *
     * @since 3.4.0
     */
    public function requireGuest(): void
    {
        $userSession = Craft::$app->getUser();

        if (!$userSession->getIsGuest()) {
            $userSession->guestRequired();
            Craft::$app->end();
        }
    }

    /**
     * Throws a 403 error if the current user is not an admin.
     *
     * @param bool $requireAdminChanges Whether the <config4:allowAdminChanges>
     * config setting must also be enabled.
     * @throws ForbiddenHttpException if the current user is not an admin
     */
    public function requireAdmin(bool $requireAdminChanges = true): void
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
    public function requirePermission(string $permissionName): void
    {
        if (!Craft::$app->getUser()->checkPermission($permissionName)) {
            throw new ForbiddenHttpException('User is not authorized to perform this action.');
        }
    }

    /**
     * Checks whether the current user can perform a given action, and ends the request with a 403 error if they don’t.
     *
     * @param string $action The name of the action to check.
     * @throws ForbiddenHttpException if the current user is not authorized
     */
    public function requireAuthorization(string $action): void
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
    public function requireElevatedSession(): void
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
    public function requirePostRequest(): void
    {
        if (!$this->request->getIsPost()) {
            throw new BadRequestHttpException('Post request required');
        }
    }

    /**
     * Throws a 400 error if the request doesn't accept JSON.
     *
     * @throws BadRequestHttpException if the request doesn't accept JSON
     */
    public function requireAcceptsJson(): void
    {
        if (!$this->request->getAcceptsJson() && !$this->request->getIsOptions()) {
            throw new BadRequestHttpException('Request must accept JSON in response');
        }
    }

    /**
     * Throws a 400 error if the current request doesn’t have a valid Craft token.
     *
     * @throws BadRequestHttpException if the request does not have a valid Craft token
     * @see Request::getToken()
     */
    public function requireToken(): void
    {
        if (!$this->request->getHadToken()) {
            throw new BadRequestHttpException('Valid token required');
        }
    }

    /**
     * Throws a 400 error if the current request isn’t a control panel request.
     *
     * @throws BadRequestHttpException if this is not a control panel request
     * @since 3.1.0
     */
    public function requireCpRequest(): void
    {
        if (!$this->request->getIsCpRequest()) {
            throw new BadRequestHttpException('Request must be a control panel request');
        }
    }

    /**
     * Throws a 400 error if the current request isn’t a site request.
     *
     * @throws BadRequestHttpException if the request is not a site request
     * @since 3.1.0
     */
    public function requireSiteRequest(): void
    {
        if (!$this->request->getIsSiteRequest()) {
            throw new BadRequestHttpException('Request must be a site request');
        }
    }

    /**
     * Sets a success flash message on the user session.
     *
     * If a hashed `successMessage` param was sent with the request, that will be used instead of the provided default.
     *
     * @param string|null $default The default message, if no `successMessage` param was sent
     * @param array $settings Control panel notification settings
     * @since 3.5.0
     */
    public function setSuccessFlash(?string $default = null, array $settings = []): void
    {
        $message = $this->request->getValidatedBodyParam('successMessage') ?? $default;
        if ($message !== null) {
            Craft::$app->getSession()->setSuccess($message, $settings);
        }
    }

    /**
     * Sets an error flash message on the user session.
     *
     * If a hashed `failMessage` param was sent with the request, that will be used instead of the provided default.
     *
     * @param string|null $default The default message, if no `successMessage` param was sent
     * @param array $settings Control panel notification settings
     * @since 3.5.0
     */
    public function setFailFlash(?string $default = null, array $settings = []): void
    {
        $message = $this->request->getValidatedBodyParam('failMessage') ?? $default;
        if ($message !== null) {
            Craft::$app->getSession()->setError($message, $settings);
        }
    }

    /**
     * Gets the `redirect` param specified in the POST data.
     *
     * @param object|null $object Object containing properties that should be parsed for in the URL.
     * @return string|null
     * @throws BadRequestHttpException if the redirect param was tampered with
     * @since 4.0.0
     */
    protected function getPostedRedirectUrl(?object $object = null): ?string
    {
        $url = $this->request->getValidatedBodyParam('redirect');

        if ($url && $object) {
            $url = $this->getView()->renderObjectTemplate($url, $object);
        }

        return $url;
    }

    /**
     * Redirects to the URI specified in the POST.
     *
     * @param object|null $object Object containing properties that should be parsed for in the URL.
     * @param string|null $default The default URL to redirect them to, if no 'redirect' parameter exists. If this is left
     * null, then the current request’s path will be used.
     * @return YiiResponse
     * @throws BadRequestHttpException if the redirect param was tampered with
     */
    public function redirectToPostedUrl(?object $object = null, ?string $default = null): YiiResponse
    {
        $url = $this->getPostedRedirectUrl($object);

        if ($url === null) {
            if ($default !== null) {
                $url = $default;
            } else {
                $url = $this->request->getPathInfo();
            }
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
    public function asJsonP(mixed $data): YiiResponse
    {
        $this->response->data = $data;
        $this->response->format = YiiResponse::FORMAT_JSONP;
        return $this->response;
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
    public function asRaw(mixed $data): YiiResponse
    {
        $this->response->data = $data;
        $this->response->format = YiiResponse::FORMAT_RAW;
        return $this->response;
    }

    /**
     * Responds to the request with a JSON error message.
     *
     * @param string $error The error message.
     * @return YiiResponse
     * @deprecated in 4.0.0. [[asFailure()]] should be used instead.
     */
    public function asErrorJson(string $error): YiiResponse
    {
        return $this->asJson(['error' => $error]);
    }

    /**
     * @inheritdoc
     * @param string|array|null $url
     * @param int $statusCode
     * @return YiiResponse
     */
    public function redirect($url, $statusCode = 302): YiiResponse
    {
        if ($url !== null) {
            return $this->response->redirect($url, $statusCode);
        }

        return $this->goHome();
    }
}
