<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\controllers;

use Craft;
use craft\auth\methods\AuthMethodInterface;
use craft\base\Element;
use craft\base\ModelInterface;
use craft\base\NameTrait;
use craft\elements\Address;
use craft\elements\Asset;
use craft\elements\Entry;
use craft\elements\User;
use craft\enums\CmsEdition;
use craft\errors\InvalidElementException;
use craft\errors\UploadFailedException;
use craft\errors\WrongEditionException;
use craft\events\DefineUserContentSummaryEvent;
use craft\events\FindLoginUserEvent;
use craft\events\InvalidUserTokenEvent;
use craft\events\LoginFailureEvent;
use craft\events\UserEvent;
use craft\helpers\App;
use craft\helpers\ArrayHelper;
use craft\helpers\Assets;
use craft\helpers\Db;
use craft\helpers\FileHelper;
use craft\helpers\Html;
use craft\helpers\Image;
use craft\helpers\Json;
use craft\helpers\Session;
use craft\helpers\StringHelper;
use craft\helpers\UrlHelper;
use craft\helpers\User as UserHelper;
use craft\i18n\Locale;
use craft\models\UserGroup;
use craft\records\WebAuthn as WebAuthnRecord;
use craft\services\Users;
use craft\web\Application;
use craft\web\assets\authmethodsetup\AuthMethodSetupAsset;
use craft\web\assets\passkeysetup\PasskeySetupAsset;
use craft\web\Controller;
use craft\web\CpScreenResponseBehavior;
use craft\web\Request;
use craft\web\ServiceUnavailableHttpException;
use craft\web\UploadedFile;
use craft\web\View;
use DateTime;
use thamtech\ratelimiter\Context;
use thamtech\ratelimiter\handlers\TooManyRequestsHttpExceptionHandler;
use thamtech\ratelimiter\limit\RateLimit;
use thamtech\ratelimiter\RateLimiter;
use Throwable;
use yii\base\Exception;
use yii\base\InvalidArgumentException;
use yii\base\InvalidConfigException;
use yii\base\Model;
use yii\web\BadRequestHttpException;
use yii\web\ForbiddenHttpException;
use yii\web\HttpException;
use yii\web\NotFoundHttpException;
use yii\web\Response;
use yii\web\ServerErrorHttpException;

/** @noinspection ClassOverridesFieldOfSuperClassInspection */

/**
 * The UsersController class is a controller that handles various user account related tasks such as logging-in,
 * impersonating a user, logging out, forgetting passwords, setting passwords, validating accounts, activating
 * accounts, creating users, saving users, processing user avatars, deleting, suspending and unsuspending users.
 * Note that all actions in the controller, except [[actionLogin]], [[actionLogout]], [[actionGetRemainingSessionTime]],
 * [[actionSendPasswordResetEmail]], [[actionSetPassword]], [[actionVerifyEmail]] and [[actionSaveUser]] require an
 * authenticated Craft session via [[allowAnonymous]].
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class UsersController extends Controller
{
    use EditUserTrait;

    /**
     * @event FindLoginUserEvent The event that is triggered before attempting to find a user to sign in
     *
     * ```php
     * use Craft;
     * use craft\controllers\UsersController;
     * use craft\elements\User;
     * use craft\events\FindLoginUserEvent;
     * use yii\base\Event;
     *
     * Event::on(
     *     UsersController::class,
     *     UsersController::EVENT_BEFORE_FIND_LOGIN_USER,
     *     function(FindLoginUserEvent $event) {
     *         // force username-based login
     *         $event->user = User::find()
     *             ->username($event->loginName)
     *             ->addSelect(['users.password', 'users.passwordResetRequired'])
     *             ->one();
     *     }
     * );
     * ```
     *
     * @since 4.2.0
     */
    public const EVENT_BEFORE_FIND_LOGIN_USER = 'beforeFindLoginUser';

    /**
     * @event FindLoginUserEvent The event that is triggered after attempting to find a user to sign in
     * @since 4.2.0
     */
    public const EVENT_AFTER_FIND_LOGIN_USER = 'afterFindLoginUser';

    /**
     * @event LoginFailureEvent The event that is triggered when a failed login attempt was made
     */
    public const EVENT_LOGIN_FAILURE = 'loginFailure';

    /**
     * @event \craft\events\DefineEditUserScreensEvent The event that is triggered when defining the screens that should be
     * shown for the user being edited.
     * @since 5.1.0
     */
    public const EVENT_DEFINE_EDIT_SCREENS = 'defineEditScreens';

    /**
     * @event UserEvent The event that is triggered BEFORE user groups and permissions ARE assigned to the user getting saved
     * @since 3.5.13
     */
    public const EVENT_BEFORE_ASSIGN_GROUPS_AND_PERMISSIONS = 'beforeAssignGroupsAndPermissions';

    /**
     * @event UserEvent The event that is triggered after user groups and permissions have been assigned to the user getting saved
     * @since 3.5.13
     */
    public const EVENT_AFTER_ASSIGN_GROUPS_AND_PERMISSIONS = 'afterAssignGroupsAndPermissions';

    /**
     * @event DefineUserContentSummaryEvent The event that is triggered when defining a summary of content owned by a user(s), before they are deleted
     *
     * ---
     * ```php
     * use craft\controllers\UsersController;
     * use craft\events\DefineUserContentSummaryEvent;
     * use yii\base\Event;
     *
     * Event::on(UsersController::class, UsersController::EVENT_DEFINE_CONTENT_SUMMARY, function(DefineUserContentSummaryEvent $e) {
     *     $e->contentSummary[] = 'A pair of sneakers';
     * });
     * ```
     *
     * @since 3.0.13
     */
    public const EVENT_DEFINE_CONTENT_SUMMARY = 'defineContentSummary';

    /**
     * @event InvalidUserTokenEvent The event that is triggered when an invalid user token is sent.
     * @since 3.6.5
     */
    public const EVENT_INVALID_USER_TOKEN = 'invalidUserToken';

    /**
     * @inheritdoc
     */
    protected array|bool|int $allowAnonymous = [
        'get-remaining-session-time' => self::ALLOW_ANONYMOUS_LIVE | self::ALLOW_ANONYMOUS_OFFLINE,
        'session-info' => self::ALLOW_ANONYMOUS_LIVE | self::ALLOW_ANONYMOUS_OFFLINE,
        'login-modal' => self::ALLOW_ANONYMOUS_LIVE | self::ALLOW_ANONYMOUS_OFFLINE,
        'get-user-for-login' => self::ALLOW_ANONYMOUS_LIVE | self::ALLOW_ANONYMOUS_OFFLINE,
        'login' => self::ALLOW_ANONYMOUS_LIVE | self::ALLOW_ANONYMOUS_OFFLINE,
        'auth-form' => self::ALLOW_ANONYMOUS_LIVE | self::ALLOW_ANONYMOUS_OFFLINE,
        'login-with-passkey' => self::ALLOW_ANONYMOUS_LIVE | self::ALLOW_ANONYMOUS_OFFLINE,
        'logout' => self::ALLOW_ANONYMOUS_LIVE | self::ALLOW_ANONYMOUS_OFFLINE,
        'impersonate-with-token' => self::ALLOW_ANONYMOUS_LIVE | self::ALLOW_ANONYMOUS_OFFLINE,
        'save-user' => self::ALLOW_ANONYMOUS_LIVE,
        'send-password-reset-email' => self::ALLOW_ANONYMOUS_LIVE | self::ALLOW_ANONYMOUS_OFFLINE,
        'set-password' => self::ALLOW_ANONYMOUS_LIVE | self::ALLOW_ANONYMOUS_OFFLINE,
        'verify-email' => self::ALLOW_ANONYMOUS_LIVE | self::ALLOW_ANONYMOUS_OFFLINE,
    ];

    /**
     * @inheritdoc
     */
    public function behaviors(): array
    {
        return parent::behaviors() + [
            'rateLimiter' => [
                'class' => RateLimiter::class,
                'only' => ['send-password-reset-email'],
                'components' => [
                    'rateLimit' => [
                        'definitions' => [
                            'reset-password' => [
                                'class' => RateLimit::class,
                                'limit' => 1,
                                'window' => 1,
                                'identifier' => fn(Context $context, $rateLimitId) => sprintf(
                                    '%s:%s',
                                    $rateLimitId,
                                    $context->request->getUserIP(),
                                ),
                            ],
                        ],
                    ],
                    'allowanceStorage' => [
                        'cache' => 'cache',
                    ],
                ],
                'as tooManyRequestsException' => TooManyRequestsHttpExceptionHandler::class,
            ],
        ];
    }

    /**
     * @inheritdoc
     */
    public function beforeAction($action): bool
    {
        // Don't enable CSRF validation for login requests if the user is already logged-in.
        // (Guards against double-clicking a Login button.)
        if ($action->id === 'login' && !Craft::$app->getUser()->getIsGuest()) {
            $this->enableCsrfValidation = false;
        }

        return parent::beforeAction($action);
    }

    /**
     * Displays the login template, and handles login post requests for logging in with a password.
     *
     * @return Response|null
     * @throws BadRequestHttpException
     * @throws ServiceUnavailableHttpException
     */
    public function actionLogin(): ?Response
    {
        // Set the default response format to HTML, in case it was set to JSON for headless mode
        if (!$this->request->getAcceptsJson()) {
            $this->response->format = Response::FORMAT_HTML;
        }

        if ($this->request->getIsGet()) {
            // see if they're already logged in
            $user = static::currentUser();
            if ($user) {
                return $this->_handleSuccessfulLogin($user);
            }

            // should we be showing the 2FA form?
            if ($this->request->getQueryParam('verify')) {
                return $this->runAction('auth-form');
            }

            return $this->_rerouteWithFallbackTemplate('login.twig');
        }

        $loginName = $this->request->getRequiredBodyParam('loginName');
        $password = $this->request->getRequiredBodyParam('password');
        $rememberMe = (bool)$this->request->getBodyParam('rememberMe');

        $user = $this->_findLoginUser($loginName);

        if (!$user || $user->password === null) {
            // Match $user->authenticate()'s delay
            $this->_hashCheck();
            return $this->_handleLoginFailure(User::AUTH_INVALID_CREDENTIALS);
        }

        // Did they submit a valid password, and is the user capable of being logged-in?
        if (!$user->authenticate($password)) {
            return $this->_handleLoginFailure($user->authError, $user);
        }

        // Get the session duration
        $generalConfig = Craft::$app->getConfig()->getGeneral();
        if ($rememberMe && $generalConfig->rememberedUserSessionDuration !== 0) {
            $duration = $generalConfig->rememberedUserSessionDuration;
        } else {
            $duration = $generalConfig->userSessionDuration;
        }

        $userSession = Craft::$app->getUser();

        // if user has an active 2SV method, move on to that
        if (!$generalConfig->disable2fa) {
            $authService = Craft::$app->getAuth();
            if ($authService->hasActiveMethod($user)) {
                $authService->setUser($user, $duration);

                if ($this->request->getIsSiteRequest() && !$this->request->getAcceptsJson()) {
                    $loginPath = $generalConfig->getLoginPath();
                    if (!$loginPath) {
                        $authService->setUser(null);
                        throw new InvalidConfigException('User requires two-step verification, but the loginPath config setting is disabled.');
                    }
                    return $this->redirect(UrlHelper::siteUrl($loginPath, array_filter([
                        'verify' => 1,
                        'returnUrl' => $this->getPostedRedirectUrl($user),
                    ])));
                }

                return $this->runAction('auth-form');
            }
        }

        // if we're impersonating, pass the user we're impersonating to the complete method
        $impersonator = $userSession->getImpersonator();
        if ($impersonator !== null) {
            $user = Craft::$app->getUser()->getIdentity() ?? $user;
        }

        return $this->_completeLogin($user, $duration);
    }

    /**
     * Logs a user in with a passkey.
     *
     * @return Response|null
     * @since 5.0.0
     */
    public function actionLoginWithPasskey(): ?Response
    {
        $this->requirePostRequest();
        $this->requireAcceptsJson();

        $duration = Craft::$app->getConfig()->getGeneral()->userSessionDuration;

        $requestOptions = $this->request->getRequiredBodyParam('requestOptions');
        $response = $this->request->getRequiredBodyParam('response');
        $credential = WebAuthnRecord::findOne(['credentialId' => Json::decode($response)['id']]);

        if ($credential === null) {
            return $this->asFailure(Craft::t('app', 'Passkey authentication failed.'));
        }

        $user = User::findOne(['id' => $credential['userId']]);

        if ($user === null) {
            return $this->_handleLoginFailure();
        }

        if (!$user->authenticateWithPasskey($requestOptions, $response)) {
            return $this->_handleLoginFailure($user->authError, $user);
        }

        // if we're impersonating, pass the user we're impersonating to the complete method
        $userSession = Craft::$app->getUser();
        if ($userSession->getImpersonator() !== null) {
            $user = $userSession->getIdentity();
        }

        return $this->_completeLogin($user, $duration);
    }

    /**
     * Finish logging user in.
     *
     * Used for logging in with a password or passkey.
     *
     * @param User $user
     * @param int $duration
     * @return Response
     * @throws ServiceUnavailableHttpException
     * @since 5.0.0
     */
    private function _completeLogin(User $user, int $duration): Response
    {
        $userSession = Craft::$app->getUser();

        // Try logging them in
        if (!$userSession->login($user, $duration)) {
            // Unknown error
            return $this->_handleLoginFailure(null, $user);
        }

        return $this->_handleSuccessfulLogin($user);
    }

    private function _findLoginUser(string $loginName): ?User
    {
        // Fire a 'beforeFindLoginUser' event
        if ($this->hasEventHandlers(self::EVENT_BEFORE_FIND_LOGIN_USER)) {
            $event = new FindLoginUserEvent(['loginName' => $loginName]);
            $this->trigger(self::EVENT_BEFORE_FIND_LOGIN_USER, $event);
            $user = $event->user;
        } else {
            $user = null;
        }

        $user ??= Craft::$app->getUsers()->getUserByUsernameOrEmail($loginName);

        // Fire an 'afterFindLoginUser' event
        if ($this->hasEventHandlers(self::EVENT_AFTER_FIND_LOGIN_USER)) {
            $event = new FindLoginUserEvent([
                'loginName' => $loginName,
                'user' => $user,
            ]);
            $this->trigger(self::EVENT_AFTER_FIND_LOGIN_USER, $event);
            return $event->user;
        }

        return $user;
    }

    /**
     * Redirects the user to the default post-login URL.
     *
     * @return Response
     */
    public function actionRedirect(): Response
    {
        return $this->redirect(Craft::$app->getUser()->getDefaultReturnUrl());
    }

    /**
     * Logs a user in for impersonation.
     *
     * @return Response|null
     * @throws BadRequestHttpException
     * @throws ForbiddenHttpException
     */
    public function actionImpersonate(): ?Response
    {
        $this->userActionChecks();
        $this->requireElevatedSession();

        $userSession = Craft::$app->getUser();
        $userId = $this->request->getRequiredBodyParam('userId');
        $user = Craft::$app->getUsers()->getUserById($userId);

        if (!$user) {
            throw new BadRequestHttpException("Invalid user ID: $userId");
        }

        // Make sure they're allowed to impersonate this user
        $this->_enforceImpersonatePermission($user);

        // Save the original user ID to the session now so User::findIdentity()
        // knows not to worry if the user isn't active yet
        $userSession->setImpersonatorId($userSession->getId());

        if (!$userSession->loginByUserId($userId)) {
            $userSession->setImpersonatorId(null);
            $this->setFailFlash(Craft::t('app', 'There was a problem impersonating this user.'));
            Craft::error($userSession->getIdentity()->username . ' tried to impersonate userId: ' . $userId . ' but something went wrong.', __METHOD__);
            return null;
        }

        return $this->_handleSuccessfulLogin($user);
    }

    /**
     * Generates and returns a new impersonation URL
     *
     * @return Response
     * @throws BadRequestHttpException
     * @throws ForbiddenHttpException
     * @throws ServerErrorHttpException
     * @since 3.6.0
     */
    public function actionGetImpersonationUrl(): Response
    {
        $this->userActionChecks();
        $this->requireElevatedSession();

        $userId = $this->request->getBodyParam('userId');
        $user = Craft::$app->getUsers()->getUserById($userId);

        if (!$user) {
            throw new BadRequestHttpException("Invalid user ID: $userId");
        }

        // Make sure they're allowed to impersonate this user
        $this->_enforceImpersonatePermission($user);

        // Create a single-use token that expires in an hour
        $token = Craft::$app->getTokens()->createToken([
            'users/impersonate-with-token', [
                'userId' => $userId,
                'prevUserId' => Craft::$app->getUser()->getId(),
            ],
        ], 1, new DateTime('+1 hour'));

        if (!$token) {
            throw new ServerErrorHttpException('Unable to create the invalidation token.');
        }

        $url = $user->can('accessCp') ? UrlHelper::cpUrl() : UrlHelper::siteUrl();
        $url = UrlHelper::urlWithToken($url, $token);

        return $this->asJson(compact('url'));
    }

    /**
     * Logs a user in for impersonation via an impersonation token.
     *
     * @param int $userId
     * @param int $prevUserId
     * @return Response|null
     * @throws BadRequestHttpException
     * @throws ForbiddenHttpException
     * @since 3.6.0
     */
    public function actionImpersonateWithToken(int $userId, int $prevUserId): ?Response
    {
        $this->requireToken();

        $userSession = Craft::$app->getUser();
        $user = Craft::$app->getUsers()->getUserById($userId);
        $success = false;

        if ($user) {
            // Save the original user ID to the session now so User::findIdentity()
            // knows not to worry if the user isn't active yet
            $userSession->setImpersonatorId($prevUserId);
            $success = $userSession->login($user);
            if (!$success) {
                $userSession->setImpersonatorId(null);
            }
        }

        if (!$success) {
            $this->setFailFlash(Craft::t('app', 'There was a problem impersonating this user.'));
            Craft::error(sprintf('%s tried to impersonate userId: %s but something went wrong.',
                $userSession->getIdentity()->username, $userId), __METHOD__);
            return null;
        }

        return $this->_handleSuccessfulLogin($user);
    }

    /**
     * Ensures that the current user has permission to impersonate the given user.
     *
     * @param User $user
     * @throws ForbiddenHttpException
     */
    private function _enforceImpersonatePermission(User $user): void
    {
        if (!Craft::$app->getUsers()->canImpersonate(static::currentUser(), $user)) {
            throw new ForbiddenHttpException('You do not have sufficient permissions to impersonate this user');
        }
    }

    /**
     * Returns information about the current user session, if any.
     *
     * @return Response
     * @since 3.4.0
     */
    public function actionSessionInfo(): Response
    {
        $this->requireAcceptsJson();

        $userSession = Craft::$app->getUser();
        /** @var User|null $user */
        $user = $userSession->getIdentity();

        $return = [
            'isGuest' => $user === null,
            'timeout' => $userSession->getRemainingSessionTime(),
        ];

        if (Craft::$app->getConfig()->getGeneral()->enableCsrfProtection) {
            $return['csrfTokenName'] = Craft::$app->getConfig()->getGeneral()->csrfTokenName;
            $return['csrfTokenValue'] = $this->request->getCsrfToken();
        }

        if ($user !== null) {
            $return['id'] = $user->id;
            $return['uid'] = $user->uid;
            $return['username'] = $user->username;
            $return['email'] = $user->email;
        }

        return $this->asJson($return);
    }

    /**
     * Renders the login modal for logged-out control panel uses.
     *
     * @return Response
     * @since 5.0.0
     */
    public function actionLoginModal(): Response
    {
        $this->requireAcceptsJson();
        $this->requirePostRequest();

        $forElevatedSession = (bool)$this->request->getBodyParam('forElevatedSession');

        // If the current user is being impersonated, get the impersonator instead
        if ($forElevatedSession && ($impersonator = Craft::$app->getUser()->getImpersonator())) {
            $staticEmail = $impersonator->email;
        } else {
            $staticEmail = $this->request->getRequiredBodyParam('email');
        }

        $view = $this->getView();
        $html = $view->renderTemplate('_special/login-modal.twig', [
            'staticEmail' => $staticEmail,
            'forElevatedSession' => $forElevatedSession,
        ], View::TEMPLATE_MODE_CP);

        return $this->asJson([
            'html' => $html,
            'headHtml' => $view->getHeadHtml(),
            'bodyHtml' => $view->getBodyHtml(),
        ]);
    }

    /**
     * Returns how many seconds are left in the current elevated user session.
     *
     * @return Response
     */
    public function actionGetElevatedSessionTimeout(): Response
    {
        $timeout = Craft::$app->getUser()->getElevatedSessionTimeout();

        return $this->asJson([
            'timeout' => $timeout,
        ]);
    }

    /**
     * @return Response
     */
    public function actionLogout(): Response
    {
        // Set the default response format to HTML, in case it was set to JSON for headless mode
        if (!$this->request->getAcceptsJson()) {
            $this->response->format = Response::FORMAT_HTML;
        }

        // Passing false here for reasons.
        Craft::$app->getUser()->logout(false);

        $data = [];

        if ($this->request->getAcceptsJson()) {
            if (Craft::$app->getConfig()->getGeneral()->enableCsrfProtection) {
                $data['csrfTokenValue'] = $this->request->getCsrfToken();
            }

            return $this->asSuccess(
                data: $data,
            );
        }

        // Redirect to the login page if this is a control panel request
        if ($this->request->getIsCpRequest()) {
            return $this->redirect(Request::CP_PATH_LOGIN);
        }

        return $this->asSuccess(
            data: $data,
            redirect: Craft::$app->getConfig()->getGeneral()->getPostLogoutRedirect()
        );
    }

    /**
     * Sends a password reset email.
     *
     * @return Response|null
     * @throws NotFoundHttpException if the requested user cannot be found
     */
    public function actionSendPasswordResetEmail(): ?Response
    {
        $this->requirePostRequest();
        $errors = [];
        $loginName = null;

        // If someone's logged in and they're allowed to edit other users, then see if a userId was submitted
        if (Craft::$app->getUser()->checkPermission('editUsers')) {
            $userId = $this->request->getBodyParam('userId');

            if ($userId) {
                $user = Craft::$app->getUsers()->getUserById($userId);

                if (!$user) {
                    throw new NotFoundHttpException('User not found');
                }
            }
        }

        /** @noinspection UnSafeIsSetOverArrayInspection - FP */
        if (!isset($user)) {
            $loginName = $this->request->getBodyParam('loginName');

            if (!$loginName) {
                // If they didn't even enter a username/email, just bail now.
                $errors[] = Craft::$app->getConfig()->getGeneral()->useEmailAsUsername
                    ? Craft::t('app', 'Email is required.')
                    : Craft::t('app', 'Username or email is required.');

                return $this->_handleSendPasswordResetError($errors);
            }

            $user = Craft::$app->getUsers()->getUserByUsernameOrEmail($loginName);

            if (
                !$user?->getIsCredentialed() ||
                (!$user->getHasPassword() && $user->getHasSsoIdentity())
            ) {
                $errors[] = Craft::$app->getConfig()->getGeneral()->useEmailAsUsername
                    ? Craft::t('app', 'Invalid email.')
                    : Craft::t('app', 'Invalid username or email.');
            }
        }

        // keep track of how long email sending takes
        $time = microtime(true);

        // Don't try to send the email if there are already error or there is no user
        try {
            if (empty($errors) && !empty($user) && !Craft::$app->getUsers()->sendPasswordResetEmail($user)) {
                throw new Exception();
            }
        } catch (Exception) {
            $errors[] = Craft::t('app', 'There was a problem sending the password reset email.');
        }

        if (Craft::$app->getConfig()->getGeneral()->preventUserEnumeration) {
            // Randomly delay the response
            $this->_randomlyDelayResponse(microtime(true) - $time);

            if (!empty($errors)) {
                $list = implode("\n", array_map(fn(string $error) => sprintf('- %s', $error), $errors));
                Craft::warning(sprintf("Password reset email not sent:\n%s", $list), __METHOD__);
                $errors = [];
            }
        }

        if (empty($errors)) {
            return $this->asSuccess(Craft::t('app', 'Password reset email sent.'));
        }

        // Handle the errors.
        return $this->_handleSendPasswordResetError($errors, $loginName);
    }

    /**
     * Generates a new verification code for a given user, and returns its URL.
     *
     * @return Response
     * @throws BadRequestHttpException if the existing password submitted with the request is invalid
     */
    public function actionGetPasswordResetUrl(): Response
    {
        $this->userActionChecks();
        $this->requirePermission('administrateUsers');

        if (!$this->_verifyElevatedSession()) {
            throw new BadRequestHttpException('Existing password verification failed');
        }

        $userId = $this->request->getRequiredParam('userId');
        $user = Craft::$app->getUsers()->getUserById($userId);

        if (!$user) {
            $this->_noUserExists();
        }

        try {
            $url = Craft::$app->getUsers()->getPasswordResetUrl($user);
        } catch (InvalidElementException $e) {
            if (in_array($user->getStatus(), [User::STATUS_INACTIVE, User::STATUS_PENDING])) {
                $message = Craft::t('app', 'Couldn’t generate an activation URL: {error}', [
                    'error' => $e->getMessage(),
                ]);
            } else {
                $message = Craft::t('app', 'Couldn’t generate a password reset URL: {error}', [
                    'error' => $e->getMessage(),
                ]);
            }
            return $this->asFailure($message);
        }

        return $this->asJson([
            'url' => $url,
        ]);
    }

    /**
     * Requires a user to reset their password on next login.
     *
     * @return Response|null
     * @since 5.0.0
     */
    public function actionRequirePasswordReset(): ?Response
    {
        $this->requirePermission('administrateUsers');

        $userId = $this->request->getRequiredParam('userId');
        $user = Craft::$app->getUsers()->getUserById($userId);

        if (!$user) {
            $this->_noUserExists();
        }

        $user->passwordResetRequired = true;

        if (!Craft::$app->getElements()->saveElement($user, false)) {
            return $this->asFailure(StringHelper::upperCaseFirst(Craft::t('app', 'Couldn’t save {type}.', [
                'type' => User::lowerDisplayName(),
            ])));
        }

        return $this->asSuccess(Craft::t('app', '{type} saved.', [
            'type' => User::displayName(),
        ]));
    }

    /**
     * Removes the requirement for a user to reset their password on next login.
     *
     * @return Response|null
     * @since 5.0.0
     */
    public function actionRemovePasswordResetRequirement(): ?Response
    {
        $this->requirePermission('administrateUsers');

        $userId = $this->request->getRequiredParam('userId');
        $user = Craft::$app->getUsers()->getUserById($userId);

        if (!$user) {
            $this->_noUserExists();
        }

        $user->passwordResetRequired = false;

        if (!Craft::$app->getElements()->saveElement($user, false)) {
            return $this->asFailure(StringHelper::upperCaseFirst(Craft::t('app', 'Couldn’t save {type}.', [
                'type' => User::lowerDisplayName(),
            ])));
        }

        return $this->asSuccess(Craft::t('app', '{type} saved.', [
            'type' => User::displayName(),
        ]));
    }

    /**
     * Sets a user’s password once they’ve verified they have access to their email.
     *
     * @return Response
     */
    public function actionSetPassword(): Response
    {
        // Set the default response format to HTML, in case it was set to JSON for headless mode
        if (!$this->request->getAcceptsJson()) {
            $this->response->format = Response::FORMAT_HTML;
        }

        // Have they just submitted a password, or are we just displaying the page?
        if (!$this->request->getIsPost()) {
            if (!is_array($info = $this->_processTokenRequest())) {
                return $info;
            }

            /** @var User $user */
            /** @var string $uid */
            /** @var string $code */
            [$user, $uid, $code] = $info;

            Craft::$app->getUser()->sendUsernameCookie($user);

            // Send them to the set password template.
            return $this->_renderSetPasswordTemplate([
                'code' => $code,
                'id' => $uid,
                'newUser' => !$user->password,
            ]);
        }

        // POST request. They've just set the password.
        $code = $this->request->getRequiredBodyParam('code');
        $uid = $this->request->getRequiredParam('id');
        $user = User::find()
            ->uid($uid)
            ->status(null)
            ->addSelect(['users.password'])
            ->one();

        if (!$user) {
            throw new BadRequestHttpException("Invalid user UUID: $uid");
        }

        // Make sure we still have a valid token.
        if (!Craft::$app->getUsers()->isVerificationCodeValidForUser($user, $code)) {
            return $this->_processInvalidToken($user);
        }

        $user->newPassword = $this->request->getRequiredBodyParam('newPassword');
        $user->setScenario(User::SCENARIO_PASSWORD);

        if (!Craft::$app->getElements()->saveElement($user)) {
            return $this->asFailure(
                Craft::t('app', 'Couldn’t update password.'),
                $user->getErrors('newPassword'),
            ) ?? $this->_renderSetPasswordTemplate([
                'errors' => $user->getErrors('newPassword'),
                'code' => $code,
                'id' => $uid,
                'newUser' => !$user->password,
            ]);
        }

        // If they're pending, try to activate them, and maybe treat this as an activation request
        if ($user->getStatus() === User::STATUS_PENDING) {
            try {
                Craft::$app->getUsers()->activateUser($user);
                $response = $this->_onAfterActivateUser($user);
                if ($response !== null) {
                    return $response;
                }
            } catch (InvalidElementException) {
                // NBD
            }
        }

        if ($this->request->getAcceptsJson()) {
            $return = [
                'status' => $user->getStatus(),
            ];
            if (!Craft::$app->getUser()->getIsGuest() && Craft::$app->getConfig()->getGeneral()->enableCsrfProtection) {
                $return['csrfTokenValue'] = $this->request->getCsrfToken();
            }
            return $this->asSuccess(data: $return);
        }

        if ($this->request->getIsCpRequest()) {
            // Send them to the control panel login page by default
            $url = UrlHelper::cpUrl(Request::CP_PATH_LOGIN);
        } else {
            // Send them to the 'setPasswordSuccessPath' by default
            $setPasswordSuccessPath = Craft::$app->getConfig()->getGeneral()->getSetPasswordSuccessPath();
            $url = UrlHelper::siteUrl($setPasswordSuccessPath);
        }

        return $this->redirectToPostedUrl($user, $url);
    }

    /**
     * Verifies that a user has access to an email address.
     *
     * @return Response
     */
    public function actionVerifyEmail(): Response
    {
        // Set the default response format to HTML, in case it was set to JSON for headless mode
        if (!$this->request->getAcceptsJson()) {
            $this->response->format = Response::FORMAT_HTML;
        }

        if (!$this->request->getIsPost()) {
            if (!is_array($info = $this->_processTokenRequest())) {
                return $info;
            }

            /** @var User $user */
            /** @var string $uid */
            /** @var string $code */
            [$user, $uid, $code] = $info;

            Craft::$app->getUser()->sendUsernameCookie($user);

            // Send them to the set verify-email template
            return $this->_rerouteWithFallbackTemplate('verify-email.twig', [
                'id' => $uid,
                'code' => $code,
            ]);
        }

        // POST request
        $code = $this->request->getRequiredBodyParam('code');
        $uid = $this->request->getRequiredParam('uid');
        $user = Craft::$app->getUsers()->getUserByUid($uid);

        if (!$user) {
            throw new BadRequestHttpException("Invalid user UUID: $uid");
        }

        // Make sure we still have a valid token.
        if (!Craft::$app->getUsers()->isVerificationCodeValidForUser($user, $code)) {
            return $this->_processInvalidToken($user);
        }

        $pending = $user->pending;
        $usersService = Craft::$app->getUsers();

        // Do they have an unverified email?
        if ($user->unverifiedEmail) {
            try {
                $usersService->verifyEmailForUser($user);
            } catch (InvalidElementException) {
                return $this->renderTemplate('_special/emailtaken.twig', [
                    'email' => $user->unverifiedEmail,
                ]);
            }
        } elseif ($pending) {
            // No unverified email so just get on with activating their account
            $usersService->activateUser($user);
        }

        // If they're logged in, give them a success notice
        if (!Craft::$app->getUser()->getIsGuest()) {
            $this->setSuccessFlash(Craft::t('app', 'Email verified'));
        }

        // Were they just activated?
        if ($pending && ($response = $this->_onAfterActivateUser($user)) !== null) {
            return $response;
        }

        return $this->_redirectUserToCp($user) ?? $this->_redirectUserAfterEmailVerification($user);
    }

    /**
     * Enables a user that is currently disabled or archived.
     *
     * @return Response|null
     * @since 4.3.2
     */
    public function actionEnableUser(): ?Response
    {
        $this->userActionChecks();

        $userId = $this->request->getRequiredBodyParam('userId');
        $user = Craft::$app->getUsers()->getUserById($userId);

        if (!$user) {
            $this->_noUserExists();
        }

        $elementsService = Craft::$app->getElements();

        if (!$elementsService->canSave($user)) {
            throw new ForbiddenHttpException('User is not authorized to perform this action.');
        }

        $user->enabled = true;
        $user->enabledForSite = true;
        $user->archived = false;

        if (!$elementsService->saveElement($user, false)) {
            return $this->asFailure(StringHelper::upperCaseFirst(Craft::t('app', 'Couldn’t save {type}.', [
                'type' => User::lowerDisplayName(),
            ])));
        }

        return $this->asSuccess(Craft::t('app', '{type} saved.', [
            'type' => User::displayName(),
        ]));
    }

    /**
     * Manually activates a user account. Only admins have access.
     *
     * @return Response
     */
    public function actionActivateUser(): ?Response
    {
        $this->userActionChecks();
        $this->requirePermission('administrateUsers');
        $userVariable = $this->request->getValidatedBodyParam('userVariable') ?? 'user';

        $userId = $this->request->getRequiredBodyParam('userId');
        $user = Craft::$app->getUsers()->getUserById($userId);

        if (!$user) {
            $this->_noUserExists();
        }

        try {
            Craft::$app->getUsers()->activateUser($user);
        } catch (InvalidElementException $e) {
            return $this->asModelFailure(
                $user,
                Craft::t('app', 'There was a problem activating the user: {error}', [
                    'error' => $e->getMessage(),
                ]),
                $userVariable,
            );
        }

        return $this->asModelSuccess(
            $user,
            Craft::t('app', 'Successfully activated the user.'),
            $userVariable,
        );
    }

    /**
     * User index
     *
     * @param string|null $source
     * @return Response
     * @since 5.3.0
     */
    public function actionIndex(?string $source = null): Response
    {
        $this->requirePermission('viewUsers');
        return $this->renderTemplate('users/_index.twig', [
            'title' => Craft::t('app', 'Users'),
            'buttonLabel' => StringHelper::upperCaseFirst(Craft::t('app', 'New {type}', [
                'type' => User::lowerDisplayName(),
            ])),
            'source' => $source,
        ]);
    }

    /**
     * Creates a new unpublished draft of a user and redirects to its edit page.
     *
     * @return Response
     * @since 5.0.0
     */
    public function actionCreate(): Response
    {
        Craft::$app->requireEdition(CmsEdition::Team);

        $user = Craft::createObject(User::class);

        // Make sure the user is allowed to create this user
        if (!Craft::$app->getElements()->canSave($user)) {
            throw new ForbiddenHttpException('User not authorized to save this user.');
        }

        $user->setScenario(Element::SCENARIO_ESSENTIALS);
        if (!Craft::$app->getDrafts()->saveElementAsDraft($user, Craft::$app->getUser()->getId(), null, null, false)) {
            $response = $this->asModelFailure($user, StringHelper::upperCaseFirst(Craft::t('app', 'Couldn’t create {type}.', [
                'type' => User::lowerDisplayName(),
            ])), 'user');
            if ($response === null) {
                throw new InvalidElementException($user, sprintf('Couldn’t create user: %s', implode(', ', $user->getFirstErrors())));
            }
            return $response;
        }

        $editUrl = $user->getCpEditUrl();

        $response = $this->asModelSuccess($user, Craft::t('app', '{type} created.', [
            'type' => User::displayName(),
        ]), 'user', array_filter([
            'cpEditUrl' => $this->request->getIsCpRequest() ? $editUrl : null,
        ]));

        if (!$this->request->getAcceptsJson()) {
            $response->redirect(UrlHelper::urlWithParams($editUrl, [
                'fresh' => 1,
            ]));
        }

        return $response;
    }

    /**
     * User profile screen
     *
     * @param int|null $userId The user’s ID.
     * @param User|null $element The user being edited, if there were any validation errors.
     * @return Response
     * @since 5.0.0
     */
    public function actionProfile(?int $userId = null, ?User $element = null): Response
    {
        $this->requireCpRequest();

        $element ??= $this->editedUser($userId);

        // let the elements/edit action do most of the work
        Craft::$app->runAction('elements/edit', [
            'element' => $element,
        ]);

        if ($element->getIsUnpublishedDraft() && $this->showPermissionsScreen()) {
            $this->response
                ->submitButtonLabel(Craft::t('app', 'Create and set permissions'))
                ->redirectUrl($this->editUserScreenUrl($element, self::SCREEN_PERMISSIONS));
        }

        return $this->asEditUserScreen($element, self::SCREEN_PROFILE);
    }

    /**
     * User addresses screen.
     *
     * @param int|null $userId The user’s ID
     * @return Response
     * @since 5.0.0
     */
    public function actionAddresses(?int $userId = null): Response
    {
        $this->requireCpRequest();
        $user = $this->editedUser($userId);
        /**
         * @var Response|CpScreenResponseBehavior $response
         * @phpstan-ignore varTag.nativeType
         */
        $response = $this->asEditUserScreen($user, self::SCREEN_ADDRESSES);

        $response->contentHtml(function() use ($user) {
            $canEditUsers = Craft::$app->getUser()->checkPermission('editUsers');
            $config = [
                'showInGrid' => true,
                'canCreate' => $canEditUsers,
            ];

            // Use an element index view if there's more than 50 addresses
            $total = Address::find()->owner($user)->count();
            if ($total > 50) {
                return $user->getAddressManager()->getIndexHtml($user, $config);
            }

            return Html::tag('h2', Craft::t('app', 'Addresses')) .
                $user->getAddressManager()->getCardsHtml($user, $config);
        });

        return $response;
    }

    /**
     * User permissions screen.
     *
     * @param int|null $userId The user’s ID
     * @return Response
     * @since 5.0.0
     */
    public function actionPermissions(?int $userId = null): Response
    {
        $this->requireCpRequest();
        $user = $this->editedUser($userId);
        /**
         * @var Response|CpScreenResponseBehavior $response
         * @phpstan-ignore varTag.nativeType
         */
        $response = $this->asEditUserScreen($user, self::SCREEN_PERMISSIONS);

        $response->action('users/save-permissions');
        $response->contentTemplate('users/_permissions', [
            'user' => $user,
            'currentGroupIds' => array_map(fn(UserGroup $group) => $group->id, $user->getGroups()),
        ]);

        if (!$user->getIsCredentialed() && $user->username && static::currentUser()->can('moderateUsers')) {
            $response->additionalButtonsHtml(
                Html::button(Craft::t('app', 'Save and send activation email'), [
                    'class' => ['btn', 'secondary', 'formsubmit'],
                    'data' => [
                        'param' => 'sendActivationEmail',
                        'value' => '1',
                    ],
                ])
            );
        }

        return $response;
    }

    /**
     * Saves a user’s permissions.
     *
     * @return Response
     * @since 5.0.0
     */
    public function actionSavePermissions(): Response
    {
        $this->requireCpRequest();

        $currentUser = static::currentUser();
        $user = $this->editedUser((int)$this->request->getRequiredBodyParam('userId'));

        // Is their admin status changing?
        if ($currentUser->admin) {
            $adminParam = (bool)($this->request->getBodyParam('admin') ?? $user->admin);
            if ($adminParam !== $user->admin) {
                if ($adminParam) {
                    $this->requireElevatedSession();
                }

                $user->admin = $adminParam;
                Craft::$app->getElements()->saveElement($user, false);
            }
        }

        if (Craft::$app->edition->value >= CmsEdition::Pro->value) {
            // Fire an 'beforeAssignGroupsAndPermissions' event
            if ($this->hasEventHandlers(self::EVENT_BEFORE_ASSIGN_GROUPS_AND_PERMISSIONS)) {
                $this->trigger(self::EVENT_BEFORE_ASSIGN_GROUPS_AND_PERMISSIONS, new UserEvent([
                    'user' => $user,
                ]));
            }

            // Assign user groups and permissions if the current user is allowed to do that
            $this->_saveUserGroups($user, $currentUser);
            $this->_saveUserPermissions($user, $currentUser);

            // Fire an 'afterAssignGroupsAndPermissions' event
            if ($this->hasEventHandlers(self::EVENT_AFTER_ASSIGN_GROUPS_AND_PERMISSIONS)) {
                $this->trigger(self::EVENT_AFTER_ASSIGN_GROUPS_AND_PERMISSIONS, new UserEvent([
                    'user' => $user,
                ]));
            }
        }

        if (
            !$user->getIsCredentialed() &&
            $currentUser->can('administrateUsers') &&
            $this->request->getBodyParam('sendActivationEmail')
        ) {
            try {
                if (!Craft::$app->getUsers()->sendActivationEmail($user)) {
                    $this->setFailFlash(Craft::t('app', 'Couldn’t send activation email. Check your email settings.'));
                }
            } catch (InvalidElementException $e) {
                $this->setFailFlash(Craft::t('app', 'Couldn’t send the activation email: {error}', [
                    'error' => $e->getMessage(),
                ]));
            }
        }

        return $this->asSuccess(Craft::t('app', 'Permissions saved.'));
    }

    /**
     * User preferences screen.
     *
     * @return Response
     * @since 5.0.0
     */
    public function actionPreferences(): Response
    {
        $this->requireCpRequest();
        $user = static::currentUser();
        /**
         * @var Response|CpScreenResponseBehavior $response
         * @phpstan-ignore varTag.nativeType
         */
        $response = $this->asEditUserScreen($user, self::SCREEN_PREFERENCES);

        $i18n = Craft::$app->getI18n();

        // user language
        $userLanguage = $user->getPreferredLanguage();

        if (
            !$userLanguage ||
            !ArrayHelper::contains($i18n->getAppLocales(), fn(Locale $locale) => $locale->id === App::parseEnv($userLanguage))
        ) {
            $userLanguage = Craft::$app->language;
        }

        // user locale
        $userLocale = $user->getPreferredLocale();

        if (
            !$userLocale ||
            !ArrayHelper::contains($i18n->getAllLocales(), fn(Locale $locale) => $locale->id === App::parseEnv($userLocale))
        ) {
            $userLocale = Craft::$app->getConfig()->getGeneral()->defaultCpLocale;
        }

        $response->action('users/save-preferences');
        $response->contentTemplate('users/_preferences', compact(
            'userLanguage',
            'userLocale',
        ));

        return $response;
    }

    /**
     * Saves a user’s preferences.
     *
     * @return Response
     * @since 5.0.0
     */
    public function actionSavePreferences(): Response
    {
        $this->requireCpRequest();

        $user = static::currentUser();
        $preferredLocale = $this->request->getBodyParam('preferredLocale', $user->getPreference('locale')) ?: null;
        if ($preferredLocale === '__blank__') {
            $preferredLocale = null;
        }
        $preferences = [
            'language' => $this->request->getBodyParam('preferredLanguage', $user->getPreference('language')),
            'locale' => $preferredLocale,
            'weekStartDay' => $this->request->getBodyParam('weekStartDay', $user->getPreference('weekStartDay')),
            'useShapes' => (bool)$this->request->getBodyParam('useShapes', $user->getPreference('useShapes')),
            'underlineLinks' => (bool)$this->request->getBodyParam('underlineLinks', $user->getPreference('underlineLinks')),
            'disableAutofocus' => $this->request->getBodyParam('disableAutofocus', $user->getPreference('disableAutofocus')),
            'notificationDuration' => $this->request->getBodyParam('notificationDuration', $user->getPreference('notificationDuration')),
            'notificationPosition' => $this->request->getBodyParam('notificationPosition', $user->getPreference('notificationPosition')),
            'slideoutPosition' => $this->request->getBodyParam('slideoutPosition', $user->getPreference('slideoutPosition')),
        ];

        if ($user->admin) {
            $preferences = array_merge($preferences, [
                'showFieldHandles' => (bool)$this->request->getBodyParam('showFieldHandles', $user->getPreference('showFieldHandles')),
                'enableDebugToolbarForSite' => (bool)$this->request->getBodyParam('enableDebugToolbarForSite', $user->getPreference('enableDebugToolbarForSite')),
                'enableDebugToolbarForCp' => (bool)$this->request->getBodyParam('enableDebugToolbarForCp', $user->getPreference('enableDebugToolbarForCp')),
                'showExceptionView' => (bool)$this->request->getBodyParam('showExceptionView', $user->getPreference('showExceptionView')),
                'profileTemplates' => (bool)$this->request->getBodyParam('profileTemplates', $user->getPreference('profileTemplates')),
            ]);
        }

        Craft::$app->getUsers()->saveUserPreferences($user, $preferences);
        Craft::$app->updateTargetLanguage();

        return $this->asSuccess(Craft::t('app', 'Preferences saved.'));
    }

    /**
     * User password screen.
     *
     * @param User|null $user The user being edited, if there were any validation errors.
     * @return Response
     * @since 5.0.0
     */
    public function actionPassword(?User $user = null): Response
    {
        $this->requireCpRequest();
        $user ??= static::currentUser();
        /**
         * @var Response|CpScreenResponseBehavior $response
         * @phpstan-ignore varTag.nativeType
         */
        $response = $this->asEditUserScreen($user, self::SCREEN_PASSWORD);

        $this->getView()->registerAssetBundle(AuthMethodSetupAsset::class);

        $response->action('users/save-password');
        $response->contentTemplate('users/_password', compact('user'));

        return $response;
    }

    /**
     * Saves a user’s new password.
     *
     * @return Response|null
     * @since 5.0.0
     */
    public function actionSavePassword(): ?Response
    {
        $this->requireCpRequest();
        $this->requireElevatedSession();

        $user = static::currentUser();

        if (!$user->getHasPassword()) {
            throw new BadRequestHttpException('Only users with current passwords can set new ones.');
        }

        $newPassword = $this->request->getRequiredBodyParam('newPassword');

        if ($newPassword === '') {
            return null;
        }

        $user->newPassword = $newPassword;
        $user->setScenario(User::SCENARIO_PASSWORD);

        if (!Craft::$app->getElements()->saveElement($user)) {
            return $this->asFailure(
                Craft::t('app', 'Couldn’t save password.'),
                $user->getErrors('newPassword'),
                ['user' => $user],
            );
        }

        return $this->asSuccess(Craft::t('app', 'Password saved.'));
    }

    /**
     * User passkey screen
     *
     * @return Response
     * @since 5.0.0
     */
    public function actionPasskeys(): Response
    {
        $this->requireCpRequest();
        $user = static::currentUser();
        /**
         * @var Response|CpScreenResponseBehavior $response
         * @phpstan-ignore varTag.nativeType
         */
        $response = $this->asEditUserScreen($user, self::SCREEN_PASSKEYS);

        $view = $this->getView();
        $view->registerAssetBundle(PasskeySetupAsset::class);
        $view->registerJs(<<<JS
new Craft.PasskeySetup();
JS);

        $passkeys = Craft::$app->getAuth()->getPasskeys($user);
        $response->contentTemplate('users/_passkeys', [
            'user' => $user,
            'passkeys' => $passkeys,
        ]);

        return $response;
    }

    /**
     * Returns a 2FA setup screen, for users who require a 2FA method.
     *
     * @return Response
     * @since 5.0.0
     */
    public function actionSetup2fa(): Response
    {
        $this->getView()->registerAssetBundle(AuthMethodSetupAsset::class);

        $this->response->setNoCacheHeaders();
        return $this->renderTemplate('_special/setup-2fa.twig', templateMode: View::TEMPLATE_MODE_CP);
    }

    /**
     * Provides an endpoint for saving a user account.
     *
     * This action accounts for the following scenarios:
     * - An admin registering a new user account.
     * - An admin editing an existing user account.
     * - A normal user with user-administration permissions registering a new user account.
     * - A normal user with user-administration permissions editing an existing user account.
     * - A guest registering a new user account ("public registration").
     * This action behaves the same regardless of whether it was requested from the control panel or the front-end site.
     *
     * @return Response|null
     * @throws NotFoundHttpException if the requested user cannot be found
     * @throws BadRequestHttpException if attempting to create a client account, and one already exists
     * @throws ForbiddenHttpException if attempting public registration but public registration is not allowed
     */
    public function actionSaveUser(): ?Response
    {
        $this->requirePostRequest();

        $userSession = Craft::$app->getUser();
        $currentUser = $userSession->getIdentity();
        $canAdministrateUsers = $currentUser && $currentUser->can('administrateUsers');
        $generalConfig = Craft::$app->getConfig()->getGeneral();
        $userSettings = Craft::$app->getProjectConfig()->get('users') ?? [];
        $requireEmailVerification = (
            Craft::$app->edition->value >= CmsEdition::Pro->value &&
            ($userSettings['requireEmailVerification'] ?? true)
        );
        $deactivateByDefault = $userSettings['deactivateByDefault'] ?? false;
        $userVariable = $this->request->getValidatedBodyParam('userVariable') ?? 'user';
        $returnCsrfToken = false;

        // Get the user being edited
        // ---------------------------------------------------------------------

        $userId = $this->request->getBodyParam('userId');
        $isNewUser = !$userId;
        $newEmail = trim($this->request->getBodyParam('email') ?? '') ?: null;

        $isPublicRegistration = false;

        // Are we editing an existing user?
        if ($userId) {
            /** @var User|null $user */
            $user = User::find()
                ->id($userId)
                ->status(null)
                ->addSelect(['users.password', 'users.passwordResetRequired'])
                ->one();

            if (!$user) {
                throw new NotFoundHttpException('User not found');
            }

            /** @var User $user */
            if (!$user->getIsCurrent()) {
                // Make sure they have permission to edit other users
                $this->requirePermission('editUsers');
            }
        } else {
            // Make sure this is Craft Pro, since that's required for having multiple user accounts
            Craft::$app->requireEdition(CmsEdition::Team);

            // Is someone logged in?
            if ($currentUser) {
                // Make sure they have permission to register users
                $this->requirePermission('registerUsers');
            } else {
                // Make sure public registration is allowed
                $allowPublicRegistration = $userSettings['allowPublicRegistration'] ?? false;
                if (!$allowPublicRegistration) {
                    throw new ForbiddenHttpException('Public registration is not allowed');
                }

                $isPublicRegistration = true;

                // See if there's an inactive user with the same email
                if ($newEmail) {
                    $user = User::find()
                        ->email(Db::escapeParam($newEmail))
                        ->status(User::STATUS_INACTIVE)
                        ->one();
                }
            }

            $user ??= new User();
        }

        $isCurrentUser = $user->getIsCurrent();

        if ($isCurrentUser) {
            // Remember the old username in case it changes
            $oldUsername = $user->username;
        }

        // Handle secure properties (email and password)
        // ---------------------------------------------------------------------

        $sendActivationEmail = false;

        // Are they allowed to set the email address?
        if ($isNewUser || $isCurrentUser || $canAdministrateUsers) {
            // Make sure it actually changed
            if (!$isNewUser && $newEmail && $newEmail === $user->email) {
                $newEmail = null;
            }

            if ($newEmail) {
                // Should we be sending a verification email now?
                // Even if verification isn't required, send one out on account creation if we don't have a password yet
                $sendActivationEmail = (!$isPublicRegistration || !$deactivateByDefault) && (
                        (
                            $requireEmailVerification && (
                                $isPublicRegistration ||
                                ($isCurrentUser && !$canAdministrateUsers) ||
                                ($this->request->getBodyParam('sendActivationEmail') ?? $this->request->getBodyParam('sendVerificationEmail'))
                            )
                        ) ||
                        (
                            !$requireEmailVerification && $isNewUser && (
                                ($isPublicRegistration && $generalConfig->deferPublicRegistrationPassword) ||
                                ($this->request->getBodyParam('sendActivationEmail') ?? $this->request->getBodyParam('sendVerificationEmail'))
                            )
                        )
                    );

                if ($sendActivationEmail) {
                    $user->unverifiedEmail = $newEmail;

                    // Mark them as pending
                    if (!$user->active) {
                        $user->pending = true;
                    }
                } else {
                    // Clear out the unverified email if there is one,
                    // so it doesn't overwrite the new email later on
                    $user->unverifiedEmail = null;
                }

                if (!$sendActivationEmail || $isNewUser) {
                    $user->email = $newEmail;
                }
            }
        } else {
            // Discard the new email if it was posted
            $newEmail = null;
        }

        // Are they allowed to set a new password?
        if ($isPublicRegistration) {
            if (!$generalConfig->deferPublicRegistrationPassword) {
                $user->newPassword = $this->request->getBodyParam('password', '');
            }
        } else {
            if ($isCurrentUser) {
                // If there was a newPassword input but it was empty, pretend it didn't exist
                $user->newPassword = $this->request->getBodyParam('newPassword') ?: null;
                $returnCsrfToken = $user->newPassword !== null;
            }
        }

        // If editing an existing user and either of these properties are being changed,
        // require the user’s current password for additional security
        if (
            !$isNewUser &&
            (!empty($newEmail) || $user->newPassword !== null) &&
            !$this->_verifyElevatedSession()
        ) {
            Craft::warning('Tried to change the email or password for userId: ' . $user->id . ', but the current password does not match what the user supplied.', __METHOD__);
            $user->addError('currentPassword', Craft::t('app', 'Incorrect current password.'));
        }

        // Handle the rest of the user properties
        // ---------------------------------------------------------------------

        // Is the site set to use email addresses as usernames?
        if ($generalConfig->useEmailAsUsername) {
            $user->username = $user->email;
        } elseif ($isNewUser || $currentUser->admin || $isCurrentUser) {
            $user->username = $this->request->getBodyParam('username', ($user->username ?: $user->email));
        }

        $this->populateNameAttributes($user);

        // New users should always be initially saved in a pending state,
        // even if an admin is doing this and opted to not send the verification email
        if ($isNewUser && !$deactivateByDefault) {
            $user->pending = true;
        }

        if ($canAdministrateUsers) {
            $user->passwordResetRequired = (bool)$this->request->getBodyParam('passwordResetRequired', $user->passwordResetRequired);
        }

        if ($isPublicRegistration) {
            // set the default group on the user, so that any content
            // based on user group condition can be validated and saved against them
            $groups = Craft::$app->getUsers()->getDefaultUserGroups($user);
            if (!empty($groups)) {
                $user->setGroups($groups);
            }

            // keep track of which site they registered from
            // (do this even if it's not a multi-site install, in case it becomes one later.)
            $user->affiliatedSiteId = Craft::$app->getSites()->getCurrentSite()->id;
        }

        // If this is Craft Pro, grab any profile content from post
        $fieldsLocation = $this->request->getParam('fieldsLocation', 'fields');
        $user->setFieldValuesFromRequest($fieldsLocation);

        // Validate and save!
        // ---------------------------------------------------------------------

        $photo = UploadedFile::getInstanceByName('photo');

        if ($photo && !Image::canManipulateAsImage($photo->getExtension())) {
            $user->addError('photo', Craft::t('app', 'The user photo provided is not an image.'));
        }

        // Don't validate required custom fields if it's public registration
        if (!$isPublicRegistration || ($userSettings['validateOnPublicRegistration'] ?? false)) {
            $user->setScenario(Element::SCENARIO_LIVE);
        } elseif ($isPublicRegistration) {
            $user->setScenario(User::SCENARIO_REGISTRATION);
        }

        // Manually validate the user so we can pass $clearErrors=false
        $success = $user->validate(null, false) && Craft::$app->getElements()->saveElement($user, false);

        if (!$success) {
            Craft::info('User not saved due to validation error.', __METHOD__);

            if ($isPublicRegistration) {
                // Move any 'newPassword' errors over to 'password'
                $user->addErrors(['password' => $user->getErrors('newPassword')]);
                $user->clearErrors('newPassword');
            }

            // Copy any 'unverifiedEmail' errors to 'email'
            if (!$user->hasErrors('email')) {
                $user->addErrors(['email' => $user->getErrors('unverifiedEmail')]);
                $user->clearErrors('unverifiedEmail');
            }

            return $this->asModelFailure(
                $user,
                StringHelper::upperCaseFirst(Craft::t('app', 'Couldn’t save {type}.', [
                    'type' => User::lowerDisplayName(),
                ])),
                $userVariable
            );
        }

        // If this is a new user and email verification isn't required,
        // go ahead and activate them now.
        if ($isNewUser && !$requireEmailVerification && !$deactivateByDefault) {
            Craft::$app->getUsers()->activateUser($user);
        }

        // Is this the current user, and did their username just change?
        // todo: remove comment when WI-51866 is fixed
        /** @noinspection PhpUndefinedVariableInspection */
        if ($isCurrentUser && $user->username !== $oldUsername) {
            // Update the username cookie
            $userSession->sendUsernameCookie($user);
        }

        // Save the user’s photo, if it was submitted
        $this->_processUserPhoto($user);

        if (Craft::$app->edition->value >= CmsEdition::Pro->value) {
            // If this is public registration, assign the user to the default user group
            if ($isPublicRegistration) {
                // Assign them to the default user group
                Craft::$app->getUsers()->assignUserToDefaultGroup($user);
            }
        }

        // Do we need to send a verification email out?
        if ($sendActivationEmail) {
            // Temporarily set the unverified email on the User so the verification email goes to the
            // right place
            $originalEmail = $user->email;
            $user->email = $user->unverifiedEmail;

            try {
                if ($isNewUser) {
                    // Send the activation email
                    Craft::$app->getUsers()->sendActivationEmail($user);
                } else {
                    // Send the standard verification email
                    Craft::$app->getUsers()->sendNewEmailVerifyEmail($user);
                }
            } finally {
                // Put the original email back into place
                $user->email = $originalEmail;
            }
        }

        // Is this public registration, and was the user going to be activated automatically?
        $publicActivation = $isPublicRegistration && $user->getStatus() === User::STATUS_ACTIVE;
        $loggedIn = $publicActivation && $this->_maybeLoginUserAfterAccountActivation($user);
        $returnCsrfToken = $returnCsrfToken || $loggedIn;

        if ($this->request->getAcceptsJson()) {
            return $this->asModelSuccess(
                $user,
                Craft::t('app', '{type} saved.', ['type' => User::displayName()]),
                $userVariable,
                array_filter([
                    'id' => $user->id, // todo: remove
                    'csrfTokenValue' => $returnCsrfToken && $generalConfig->enableCsrfProtection
                        ? $this->request->getCsrfToken()
                        : null,
                ]),
            );
        }

        if ($isPublicRegistration) {
            if (($message = $this->request->getParam('userRegisteredNotice')) !== null) {
                $default = Html::encode($message);
                Craft::$app->getDeprecator()->log('userRegisteredNotice', 'The `userRegisteredNotice` param has been deprecated for `users/save-user` requests. Use a hashed `successMessage` param instead.');
            } else {
                $default = Craft::t('app', 'User registered.');
            }
            $this->setSuccessFlash($default);
        } else {
            $this->setSuccessFlash(Craft::t('app', '{type} saved.', [
                'type' => User::displayName(),
            ]));
        }

        // Is this public registration, and is the user going to be activated automatically?
        if ($publicActivation) {
            return $this->_redirectUserToCp($user) ?? $this->_redirectUserAfterAccountActivation($user);
        }

        if (!$this->request->getAcceptsJson()) {
            // Tell all browser windows about the draft deletion
            Craft::$app->getSession()->broadcastToJs([
                'event' => 'saveElement',
                'id' => $user->id,
            ]);
        }

        return $this->redirectToPostedUrl($user);
    }

    /**
     * Renders a user photo input.
     *
     * @return Response
     * @since 5.0.0
     */
    public function actionRenderPhotoInput(): Response
    {
        $this->requireAcceptsJson();
        $this->requireCpRequest();
        $userId = $this->request->getRequiredBodyParam('userId');
        $user = Craft::$app->getUsers()->getUserById($userId);

        if (!$user) {
            throw new BadRequestHttpException("Invalid user ID: $userId");
        }

        return $this->_renderPhotoTemplate($user);
    }

    /**
     * Upload a user photo.
     *
     * @return Response|null
     * @throws BadRequestHttpException if the uploaded file is not an image
     */
    public function actionUploadUserPhoto(): ?Response
    {
        $this->requireAcceptsJson();
        $userId = $this->request->getRequiredBodyParam('userId');

        if ($userId != static::currentUser()->id) {
            $this->requirePermission('editUsers');
        }

        if (($file = UploadedFile::getInstanceByName('photo')) === null) {
            return null;
        }

        try {
            if ($file->getHasError()) {
                throw new UploadFailedException($file->error);
            }

            $users = Craft::$app->getUsers();
            $user = $users->getUserById($userId);

            // Move to our own temp location
            $fileLocation = Assets::tempFilePath($file->getExtension());
            move_uploaded_file($file->tempName, $fileLocation);
            $users->saveUserPhoto($fileLocation, $user, $file->name, $file->type);

            return $this->_renderPhotoTemplate($user);
        } catch (Throwable $exception) {
            if (isset($fileLocation) && file_exists($fileLocation)) {
                FileHelper::unlink($fileLocation);
            }

            Craft::error('There was an error uploading the photo: ' . $exception->getMessage(), __METHOD__);

            return $this->asFailure(Craft::t('app', 'There was an error uploading your photo: {error}', [
                'error' => $exception->getMessage(),
            ]));
        }
    }

    /**
     * Delete all the photos for current user.
     *
     * @return Response
     * @throws BadRequestHttpException
     */
    public function actionDeleteUserPhoto(): Response
    {
        $this->requireAcceptsJson();

        $userId = $this->request->getRequiredBodyParam('userId');

        if ($userId != static::currentUser()->id) {
            $this->requirePermission('editUsers');
        }

        $user = Craft::$app->getUsers()->getUserById($userId);

        if ($user->photoId) {
            Craft::$app->getElements()->deleteElementById($user->photoId, Asset::class);
        }

        $user->photoId = null;
        Craft::$app->getElements()->saveElement($user, false);

        return $this->_renderPhotoTemplate($user);
    }

    /**
     * Sends a new activation email to a user.
     *
     * @return Response|null
     * @throws BadRequestHttpException if the user is not pending
     */
    public function actionSendActivationEmail(): ?Response
    {
        $this->userActionChecks();

        $userId = $this->request->getRequiredBodyParam('userId');

        /** @var User|null $user */
        $user = User::find()
            ->id($userId)
            ->status(null)
            ->addSelect(['users.password'])
            ->one();

        if (!$user) {
            $this->_noUserExists();
        }

        // Only allow activation emails to be sent to inactive/pending users.
        /** @var User $user */
        $status = $user->getStatus();
        if (!in_array($status, [User::STATUS_INACTIVE, User::STATUS_PENDING])) {
            throw new BadRequestHttpException('Activation emails can only be sent to inactive or pending users');
        }

        if (!$user->pending) {
            $this->requirePermission('moderateUsers');
        }

        $userVariable = $this->request->getValidatedBodyParam('userVariable') ?? 'user';

        try {
            $emailSent = Craft::$app->getUsers()->sendActivationEmail($user);
        } catch (InvalidElementException $e) {
            return $this->asModelFailure(
                $user,
                Craft::t('app', 'Couldn’t send the activation email: {error}', [
                    'error' => $e->getMessage(),
                ]),
                $userVariable,
            );
        }

        return $emailSent ?
            $this->asSuccess(Craft::t('app', 'Activation email sent.')) :
            $this->asFailure(Craft::t('app', 'Couldn’t send activation email. Check your email settings.'));
    }

    /**
     * Unlocks a user, bypassing the cooldown phase.
     *
     * @return Response
     * @throws ForbiddenHttpException if a non-admin is attempting to unlock an admin
     */
    public function actionUnlockUser(): Response
    {
        $this->userActionChecks();
        $this->requirePermission('moderateUsers');

        $userId = $this->request->getRequiredBodyParam('userId');
        $user = Craft::$app->getUsers()->getUserById($userId);

        if (!$user) {
            $this->_noUserExists();
        }

        // Even if you have moderateUsers permissions, only and admin should be able to unlock another admin.
        if ($user->admin) {
            $currentUser = static::currentUser();
            if (!$currentUser->admin) {
                throw new ForbiddenHttpException('Only admins can unlock other admins.');
            }

            // And admins can't unlock themselves by impersonating another admin
            if ($user->id === Craft::$app->getUser()->getImpersonatorId()) {
                throw new ForbiddenHttpException('You can’t unlock yourself via impersonation.');
            }
        }

        Craft::$app->getUsers()->unlockUser($user);

        $this->setSuccessFlash(Craft::t('app', 'User unlocked.'));
        return $this->redirectToPostedUrl();
    }

    /**
     * Suspends a user.
     *
     * @return Response|null
     * @throws ForbiddenHttpException if a non-admin is attempting to suspend an admin
     */
    public function actionSuspendUser(): ?Response
    {
        $this->userActionChecks();
        $this->requirePermission('moderateUsers');

        $userId = $this->request->getRequiredBodyParam('userId');
        $user = Craft::$app->getUsers()->getUserById($userId);

        if (!$user) {
            $this->_noUserExists();
        }

        $usersService = Craft::$app->getUsers();
        $currentUser = static::currentUser();

        if (!$usersService->canSuspend($currentUser, $user)) {
            $this->setFailFlash(Craft::t('app', 'Couldn’t suspend user.'));
            return null;
        }

        try {
            $usersService->suspendUser($user);
        } catch (InvalidElementException) {
            $this->setFailFlash(Craft::t('app', 'Couldn’t suspend user.'));
            return null;
        }

        $this->setSuccessFlash(Craft::t('app', 'User suspended.'));
        return $this->redirectToPostedUrl();
    }

    /**
     * Returns a summary of the content that is owned by a given user ID(s).
     *
     * @return Response
     * @since 3.0.13
     */
    public function actionUserContentSummary(): Response
    {
        $this->requirePostRequest();

        $userId = $this->request->getRequiredBodyParam('userId');

        if (is_array($userId)) {
            $userId = array_map(fn($id) => (int)$id, $userId);
        } else {
            $userId = (int)$userId;
        }

        if ($userId !== static::currentUser()?->id) {
            $this->requirePermission('deleteUsers');
        }

        $summary = [];

        foreach (Craft::$app->getEntries()->getAllSections() as $section) {
            $entryCount = Entry::find()
                ->sectionId($section->id)
                ->authorId($userId)
                ->site('*')
                ->unique()
                ->status(null)
                ->count();

            if ($entryCount) {
                $summary[] = Craft::t('app', '{num, number} {section} {num, plural, =1{entry} other{entries}}', [
                    'num' => $entryCount,
                    'section' => Craft::t('site', $section->name),
                ]);
            }
        }

        // Fire a 'defineContentSummary' event
        if ($this->hasEventHandlers(self::EVENT_DEFINE_CONTENT_SUMMARY)) {
            $event = new DefineUserContentSummaryEvent([
                'userId' => $userId,
                'contentSummary' => $summary,
            ]);
            $this->trigger(self::EVENT_DEFINE_CONTENT_SUMMARY, $event);
            $summary = $event->contentSummary;
        }

        return $this->asJson($summary);
    }

    /**
     * Deactivates a user.
     *
     * @return Response|null
     * @since 4.0.0
     */
    public function actionDeactivateUser(): ?Response
    {
        $this->userActionChecks();

        $userId = $this->request->getRequiredBodyParam('userId');
        $user = Craft::$app->getUsers()->getUserById($userId);

        if (!$user) {
            $this->_noUserExists();
        }

        if (!$user->getIsCurrent()) {
            $this->requirePermission('administrateUsers');

            // Even if you have administrateUsers permissions, only and admin should be able to deactivate another admin.
            if ($user->admin) {
                $this->requireAdmin(false);
            }
        }

        // Deactivate the user
        try {
            Craft::$app->getUsers()->deactivateUser($user);
            $this->setSuccessFlash(Craft::t('app', 'Successfully deactivated the user.'));
        } catch (InvalidElementException) {
            $this->setFailFlash(Craft::t('app', 'There was a problem deactivating the user.'));
        }

        return $this->redirectToPostedUrl();
    }

    /**
     * Deletes a user.
     *
     * @return Response|null
     */
    public function actionDeleteUser(): ?Response
    {
        $this->requirePostRequest();

        $userId = $this->request->getRequiredBodyParam('userId');
        $user = Craft::$app->getUsers()->getUserById($userId);

        if (!$user) {
            $this->_noUserExists();
        }

        if (!$user->getIsCurrent()) {
            $this->requirePermission('deleteUsers');

            // Even if you have deleteUser permissions, only and admin should be able to delete another admin.
            if ($user->admin) {
                $this->requireAdmin(false);
            }
        }

        // Are we transferring the user’s content to a different user?
        $transferContentToId = $this->request->getBodyParam('transferContentTo');

        if (is_array($transferContentToId) && isset($transferContentToId[0])) {
            $transferContentToId = $transferContentToId[0];
        }

        if ($transferContentToId) {
            $transferContentTo = Craft::$app->getUsers()->getUserById($transferContentToId);

            if (!$transferContentTo) {
                $this->_noUserExists();
            }
        } else {
            $transferContentTo = null;
        }

        // Delete the user
        $user->inheritorOnDelete = $transferContentTo;

        if (!Craft::$app->getElements()->deleteElement($user)) {
            $this->setFailFlash(Craft::t('app', 'Couldn’t delete {type}.', [
                'type' => User::lowerDisplayName(),
            ]));
            return null;
        }

        $this->setSuccessFlash(Craft::t('app', '{type} deleted.', [
            'type' => User::displayName(),
        ]));
        return $this->redirectToPostedUrl();
    }

    /**
     * Unsuspends a user.
     *
     * @return Response|null
     * @throws ForbiddenHttpException if a non-admin is attempting to unsuspend an admin
     */
    public function actionUnsuspendUser(): ?Response
    {
        $this->userActionChecks();
        $this->requirePermission('moderateUsers');

        $userId = $this->request->getRequiredBodyParam('userId');
        $user = Craft::$app->getUsers()->getUserById($userId);

        if (!$user) {
            $this->_noUserExists();
        }

        // Even if you have moderateUsers permissions, only and admin should be able to unsuspend another admin.
        $usersService = Craft::$app->getUsers();
        $currentUser = static::currentUser();

        if (!$usersService->canSuspend($currentUser, $user)) {
            $this->setFailFlash(Craft::t('app', 'Couldn’t unsuspend user.'));
            return null;
        }

        try {
            $usersService->unsuspendUser($user);
        } catch (InvalidElementException) {
            $this->setFailFlash(Craft::t('app', 'Couldn’t unsuspend user.'));
            return null;
        }

        $this->setSuccessFlash(Craft::t('app', 'User unsuspended.'));
        return $this->redirectToPostedUrl();
    }

    /**
     * Saves a user’s address.
     *
     * @return Response|null
     * @throws BadRequestHttpException
     * @throws ForbiddenHttpException
     * @since 4.0.0
     */
    public function actionSaveAddress(): ?Response
    {
        $elementsService = Craft::$app->getElements();
        $user = static::currentUser();
        $userId = (int)($this->request->getBodyParam('userId') ?? $user->id);
        $addressId = $this->request->getBodyParam('addressId');

        if ($addressId) {
            $address = Address::findOne($addressId);

            if (!$address) {
                throw new BadRequestHttpException("Invalid address ID: $addressId");
            }

            if ($address->getOwnerId() !== $userId) {
                throw new BadRequestHttpException("Address $addressId is not owned by user $userId");
            }
        } else {
            $address = new Address([
                'ownerId' => $userId,
            ]);
        }

        if (!$elementsService->canSave($address, $user)) {
            throw new ForbiddenHttpException('User is not permitted to edit this address.');
        }

        // Addresses have no status, and the default element save controller also sets the address scenario to live
        $address->setScenario(Element::SCENARIO_LIVE);

        // Name attributes
        $this->populateNameAttributes($address);

        // All safe attributes
        $safeAttributes = [];
        foreach ($address->safeAttributes() as $name) {
            if (!in_array($name, ['id', 'uid', 'ownerId'])) {
                $value = $this->request->getBodyParam($name);
                if ($value !== null) {
                    $safeAttributes[$name] = $value;
                }
            }
        }
        $address->setAttributes($safeAttributes);

        // Custom fields
        $fieldsLocation = $this->request->getParam('fieldsLocation') ?? 'fields';
        $address->setFieldValuesFromRequest($fieldsLocation);

        if (!$elementsService->saveElement($address)) {
            return $this->asModelFailure($address, StringHelper::upperCaseFirst(Craft::t('app', 'Couldn’t save {type}.', [
                'type' => Address::lowerDisplayName(),
            ])), 'address');
        }

        return $this->asModelSuccess($address, Craft::t('app', '{type} saved.', [
            'type' => Address::displayName(),
        ]));
    }

    /**
     * Deletes a user’s address.
     *
     * @return Response|null
     * @since 4.0.0
     */
    public function actionDeleteAddress(): ?Response
    {
        $addressId = $this->request->getRequiredBodyParam('addressId');
        $address = Address::findOne($addressId);

        if (!$address) {
            throw new BadRequestHttpException("Invalid address ID: $addressId");
        }

        $elementsService = Craft::$app->getElements();

        if (!$elementsService->canDelete($address)) {
            throw new ForbiddenHttpException('User is not permitted to delete this address.');
        }

        if (!$elementsService->deleteElement($address)) {
            return $this->asModelFailure($address, Craft::t('app', 'Couldn’t delete {type}.', [
                'type' => Address::lowerDisplayName(),
            ]), 'address');
        }

        return $this->asModelSuccess($address, Craft::t('app', '{type} deleted.', [
            'type' => Address::displayName(),
        ]));
    }

    /**
     * Saves the user field layout.
     *
     * @return Response|null
     */
    public function actionSaveFieldLayout(): ?Response
    {
        $this->requirePostRequest();
        $this->requireAdmin();

        // Set the field layout
        $fieldLayout = Craft::$app->getFields()->assembleLayoutFromPost();
        $fieldLayout->type = User::class;
        $fieldLayout->reservedFieldHandles = [
            'active',
            'addresses',
            'admin',
            'affiliatedSiteId',
            'email',
            'firstName',
            'friendlyName',
            'fullName',
            'groups',
            'lastName',
            'locked',
            'name',
            'password',
            'pending',
            'photo',
            'suspended',
            'username',
        ];

        if (!Craft::$app->getUsers()->saveLayout($fieldLayout)) {
            Craft::$app->getUrlManager()->setRouteParams([
                'variables' => [
                    'fieldLayout' => $fieldLayout,
                ],
            ]);
            $this->setFailFlash(Craft::t('app', 'Couldn’t save user fields.'));
            return null;
        }

        $this->setSuccessFlash(Craft::t('app', 'User fields saved.'));
        return $this->redirectToPostedUrl();
    }

    /**
     * Handles a failed login attempt.
     *
     * @param string|null $authError
     * @param User|null $user
     * @return Response|null
     * @throws ServiceUnavailableHttpException
     */
    private function _handleLoginFailure(?string $authError = null, ?User $user = null): ?Response
    {
        [$authError, $message] = UserHelper::getLoginFailureInfo($authError, $user);

        // Fire a 'loginFailure' event
        if ($this->hasEventHandlers(self::EVENT_LOGIN_FAILURE)) {
            $event = new LoginFailureEvent([
                'authError' => $authError,
                'message' => $message,
                'user' => $user,
            ]);
            $this->trigger(self::EVENT_LOGIN_FAILURE, $event);
            $message = $event->message;
        }

        return $this->asFailure(
            $message,
            data: [
                'errorCode' => $authError,
            ],
            routeParams: [
                'loginName' => $this->request->getBodyParam('loginName'),
                'rememberMe' => (bool)$this->request->getBodyParam('rememberMe'),
                'errorCode' => $authError,
                'errorMessage' => $message,
            ]
        );
    }

    public function actionAuthForm(): Response
    {
        // If the current user is being impersonated, use the impersonator
        $userSession = Craft::$app->getUser();
        $authService = Craft::$app->getAuth();
        $user = $userSession->getImpersonator() ?? $authService->getUser();

        if (!$user) {
            if ($this->request->getIsSiteRequest()) {
                $loginPath = Craft::$app->getConfig()->getGeneral()->getLoginPath();
                if (!$loginPath) {
                    throw new InvalidConfigException('The loginPath config setting is disabled.');
                }
                return $this->redirect($loginPath);
            }

            return $this->redirect(Request::CP_PATH_LOGIN);
        }

        $activeMethods = $authService->getActiveMethods($user);
        $methodClass = $this->request->getParam('method');

        if ($methodClass) {
            /** @var AuthMethodInterface|null $method */
            $method = ArrayHelper::firstWhere(
                $activeMethods,
                fn(AuthMethodInterface $method) => $method::class === $methodClass,
            );
            if (!$method) {
                throw new BadRequestHttpException("Invalid method class: $methodClass");
            }
            $activeMethods = array_values(array_filter($activeMethods, fn($m) => $m !== $method));
        } else {
            if (empty($activeMethods)) {
                throw new BadRequestHttpException('User has no active two-step verification methods.');
            }
            $method = array_shift($activeMethods);
        }

        $view = $this->getView();
        $templateMode = $view->getTemplateMode();
        $view->setTemplateMode(View::TEMPLATE_MODE_CP);
        try {
            $html = $method->getAuthFormHtml();
        } finally {
            $view->setTemplateMode($templateMode);
        }

        $returnUrl = $this->request->getQueryParam('returnUrl');
        if (!$returnUrl) {
            if ($this->request->getIsCpRequest()) {
                // explicitly set the default return URL here, since checkPermission('accessCp') will be false
                $defaultReturnUrl = UrlHelper::cpUrl(Craft::$app->getConfig()->getGeneral()->getPostCpLoginRedirect());
            } else {
                $defaultReturnUrl = UrlHelper::siteUrl(Craft::$app->getConfig()->getGeneral()->getPostLoginRedirect());
            }
            $returnUrl = $userSession->getReturnUrl($defaultReturnUrl);
        }

        $authFormData = [
            'authMethod' => $method::class,
            'otherMethods' => array_map(fn(AuthMethodInterface $method) => [
                'name' => $method::displayName(),
                'class' => $method::class,
            ], $activeMethods),
            'authForm' => $html,
            'returnUrl' => $returnUrl,
        ];

        if ($this->request->getAcceptsJson()) {
            return $this->asJson([
                ...$authFormData,
                'headHtml' => $view->getHeadHtml(),
                'bodyHtml' => $view->getBodyHtml(),
            ]);
        }

        return $this->renderTemplate('login.twig', compact('authFormData'), View::TEMPLATE_MODE_CP);
    }

    /**
     * Redirects the user after a successful login attempt, or if they visited the Login page while they were already
     * logged in.
     *
     * @param User $user
     * @return Response
     */
    private function _handleSuccessfulLogin(User $user): Response
    {
        // Get the return URL
        $userSession = Craft::$app->getUser();
        $returnUrl = $userSession->getReturnUrl();

        // Clear it out
        $userSession->removeReturnUrl();

        // If this was an Ajax request, just return success:true
        if ($this->request->getAcceptsJson()) {
            $return = [
                'returnUrl' => $returnUrl,
            ];

            if (Craft::$app->getConfig()->getGeneral()->enableCsrfProtection) {
                $return['csrfTokenValue'] = $this->request->getCsrfToken();
            }

            return $this->asModelSuccess($user, modelName: 'user', data: $return);
        }

        return $this->redirectToPostedUrl($userSession->getIdentity(), $returnUrl);
    }

    /**
     * Renders the Set Password template for a given user.
     *
     * @param array $variables
     * @return Response
     */
    private function _renderSetPasswordTemplate(array $variables): Response
    {
        return $this->_rerouteWithFallbackTemplate('set-password.twig', $variables);
    }

    private function _rerouteWithFallbackTemplate(string $cpTemplate, array $variables = []): ?Response
    {
        // If this is a site request, try handling the request like normal
        if ($this->request->getIsSiteRequest()) {
            // No special handling for Craft < Pro
            if (Craft::$app->edition->value < CmsEdition::Pro->value) {
                return null;
            }

            try {
                Craft::$app->getUrlManager()->setRouteParams([
                    'variables' => $variables,
                ]);

                // Avoid re-routing to the same action again
                $this->request->checkIfActionRequest(true, true, false);
                if (($this->request->getActionSegments()[0] ?? null) === $this->id) {
                    $this->request->setIsActionRequest(false);
                }

                /** @var Application $app */
                $app = Craft::$app;
                return $app->handleRequest($this->request, true);
            } catch (NotFoundHttpException) {
                // Just go with the control panel template
            }
        }

        // Otherwise go with the control panel’s template
        return $this->renderTemplate($cpTemplate, $variables, View::TEMPLATE_MODE_CP);
    }

    /**
     * Throws a "no user exists" exception
     *
     * @throws BadRequestHttpException
     */
    private function _noUserExists(): void
    {
        throw new BadRequestHttpException('User not found');
    }

    /**
     * Verifies that the user has an elevated session, or that their current password was submitted with the request.
     *
     * @return bool
     */
    private function _verifyElevatedSession(): bool
    {
        return (Craft::$app->getUser()->getHasElevatedSession() || $this->_verifyExistingPassword());
    }

    /**
     * Verifies that the current user’s password was submitted with the request.
     *
     * @return bool
     */
    private function _verifyExistingPassword(): bool
    {
        $currentUser = static::currentUser();

        if (!$currentUser) {
            return false;
        }

        $currentPassword = $this->request->getParam('currentPassword') ?? $this->request->getParam('password');
        if ($currentPassword === null) {
            return false;
        }

        $currentHashedPassword = $currentUser->password;

        try {
            return Craft::$app->getSecurity()->validatePassword($currentPassword, $currentHashedPassword);
        } catch (InvalidArgumentException) {
            return false;
        }
    }

    /**
     * @param User $user
     * @throws Throwable if reasons
     */
    private function _processUserPhoto(User $user): void
    {
        // Delete their photo?
        $users = Craft::$app->getUsers();

        if ($this->request->getBodyParam('deletePhoto')) {
            $users->deleteUserPhoto($user);
            $user->photoId = null;
            Craft::$app->getElements()->saveElement($user);
        }

        $newPhoto = false;
        $fileLocation = null;
        $filename = null;
        $mimeType = null;

        // Did they upload a new one?
        if ($photo = UploadedFile::getInstanceByName('photo')) {
            $fileLocation = Assets::tempFilePath($photo->getExtension());
            move_uploaded_file($photo->tempName, $fileLocation);
            $filename = $photo->name;
            $mimeType = $photo->type;
            $newPhoto = true;
        } elseif (($photo = $this->request->getBodyParam('photo')) && is_array($photo)) {
            // base64-encoded photo
            $matches = [];

            if (preg_match('/^data:((?<type>[a-z0-9]+\/[a-z0-9\+]+);)?base64,(?<data>.+)/i', $photo['data'] ?? '', $matches)) {
                $filename = $photo['filename'] ?? null;
                $extension = $filename ? pathinfo($filename, PATHINFO_EXTENSION) : null;
                $mimeType = $matches['type'] ?: null;

                if (!$extension && $mimeType) {
                    try {
                        $extension = FileHelper::getExtensionByMimeType($mimeType);
                    } catch (InvalidArgumentException) {
                    }
                }

                if (!$extension) {
                    Craft::warning('Could not determine file extension for user photo.', __METHOD__);
                    return;
                }

                $fileLocation = Assets::tempFilePath($extension);
                $data = base64_decode($matches['data']);
                FileHelper::writeToFile($fileLocation, $data);
                $newPhoto = true;
            }
        }

        if ($newPhoto) {
            try {
                $users->saveUserPhoto($fileLocation, $user, $filename, $mimeType);
            } catch (Throwable $e) {
                if (file_exists($fileLocation)) {
                    FileHelper::unlink($fileLocation);
                }

                throw $e;
            }
        }
    }

    /**
     * Saves new permissions on the user
     *
     * @param User $user
     * @param User $currentUser
     * @throws ForbiddenHttpException if the user account doesn't have permission to assign the attempted permissions
     */
    private function _saveUserPermissions(User $user, User $currentUser): void
    {
        if (!$currentUser->can('assignUserPermissions')) {
            return;
        }

        // Save any user permissions
        if ($user->admin) {
            $permissions = [];
        } else {
            $permissions = $this->request->getBodyParam('permissions');

            if ($permissions === null) {
                return;
            }

            // it will be an empty string if no permissions were assigned during user saving.
            if ($permissions === '') {
                $permissions = [];
            }
        }

        // See if there are any new permissions in here
        $hasNewPermissions = false;

        foreach ($permissions as $permission) {
            if (!$user->can($permission)) {
                $hasNewPermissions = true;

                // Make sure the current user even has permission to grant it
                if (!$currentUser->can($permission)) {
                    throw new ForbiddenHttpException("Your account doesn't have permission to assign the $permission permission to a user.");
                }
            }
        }

        if ($hasNewPermissions) {
            $this->requireElevatedSession();
        }

        Craft::$app->getUserPermissions()->saveUserPermissions($user->id, $permissions);
    }

    /**
     * Saves user groups on a user.
     *
     * @param User $user
     * @param User $currentUser
     * @throws ForbiddenHttpException if the user account doesn't have permission to assign the attempted groups
     */
    private function _saveUserGroups(User $user, User $currentUser): void
    {
        $groupIds = $this->request->getBodyParam('groups');

        if ($groupIds === null) {
            return;
        }

        if ($groupIds === '') {
            $groupIds = [];
        }

        /** @var UserGroup[] $allGroups */
        $allGroups = ArrayHelper::index(Craft::$app->getUserGroups()->getAllGroups(), 'id');

        // See if there are any new groups in here
        $oldGroupIds = array_map(fn(UserGroup $group) => $group->id, $user->getGroups());
        $hasNewGroups = false;
        $newGroups = [];

        foreach ($groupIds as $groupId) {
            $group = $newGroups[] = $allGroups[$groupId];

            if (!in_array($groupId, $oldGroupIds, false)) {
                $hasNewGroups = true;

                // Make sure the current user is in the group or has permission to assign it
                if (!$currentUser->can("assignUserGroup:$group->uid")) {
                    throw new ForbiddenHttpException("Your account doesn't have permission to assign user group “{$group->name}” to a user.");
                }
            }
        }

        if ($hasNewGroups) {
            $this->requireElevatedSession();
        }

        Craft::$app->getUsers()->assignUserToGroups($user->id, $groupIds);
        $user->setGroups($newGroups);
    }

    /**
     * @return array|Response
     */
    private function _processTokenRequest(): Response|array
    {
        $uid = $this->request->getRequiredParam('id');
        $code = $this->request->getRequiredParam('code');

        /** @var User|null $user */
        $user = User::find()
            ->uid($uid)
            ->status(null)
            ->addSelect(['users.password'])
            ->one();

        if (!$user) {
            return $this->_processInvalidToken();
        }

        // If someone is logged in and it’s not this person, log them out
        $userSession = Craft::$app->getUser();
        if (!$userSession->getIsGuest() && $userSession->getId() != $user->id) {
            $userSession->logout();
        }

        // Fire a 'beforeVerifyUser' event
        $usersService = Craft::$app->getUsers();
        if ($usersService->hasEventHandlers(Users::EVENT_BEFORE_VERIFY_EMAIL)) {
            $usersService->trigger(Users::EVENT_BEFORE_VERIFY_EMAIL, new UserEvent([
                'user' => $user,
            ]));
        }

        if (!Craft::$app->getUsers()->isVerificationCodeValidForUser($user, $code)) {
            return $this->_processInvalidToken($user);
        }

        // Fire an 'afterVerifyUser' event
        if ($usersService->hasEventHandlers(Users::EVENT_AFTER_VERIFY_EMAIL)) {
            $usersService->trigger(Users::EVENT_AFTER_VERIFY_EMAIL, new UserEvent([
                'user' => $user,
            ]));
        }

        return [$user, $uid, $code];
    }

    /**
     * @param User|null $user
     * @return Response
     * @throws HttpException if the verification code is invalid
     */
    private function _processInvalidToken(?User $user = null): Response
    {
        $this->trigger(self::EVENT_INVALID_USER_TOKEN, new InvalidUserTokenEvent([
            'user' => $user,
        ]));

        if ($this->request->getAcceptsJson()) {
            return $this->asFailure('InvalidVerificationCode');
        }

        // If they don't have a verification code at all, and they're already logged-in, just send them to the post-login URL
        if ($user && !$user->verificationCode) {
            $userSession = Craft::$app->getUser();
            if (!$userSession->getIsGuest()) {
                $returnUrl = $userSession->getReturnUrl();
                $userSession->removeReturnUrl();
                return $this->redirect($returnUrl);
            }
        }

        // If the invalidUserTokenPath config setting is set, send them there
        if ($this->request->getIsSiteRequest()) {
            $generalConfig = Craft::$app->getConfig()->getGeneral();
            $url = $generalConfig->getInvalidUserTokenPath() ?? $generalConfig->getLoginPath();
            return $this->redirect(UrlHelper::siteUrl($url));
        }

        return $this->redirect(Request::CP_PATH_LOGIN);
    }

    /**
     * Takes over after a user has been activated.
     *
     * @param User $user The user that was just activated
     * @return Response|null
     */
    private function _onAfterActivateUser(User $user): ?Response
    {
        $this->_maybeLoginUserAfterAccountActivation($user);

        if (!$this->request->getAcceptsJson()) {
            return $this->_redirectUserToCp($user) ?? $this->_redirectUserAfterAccountActivation($user);
        }

        return null;
    }

    /**
     * Possibly log a user in right after they activated their account (not when they reset their password),
     * if Craft is configured to do so.
     *
     * @param User $user The user that was just activated
     * @return bool Whether the user was logged in
     */
    private function _maybeLoginUserAfterAccountActivation(User $user): bool
    {
        $generalConfig = Craft::$app->getConfig()->getGeneral();
        if (!$generalConfig->autoLoginAfterAccountActivation) {
            return false;
        }
        return Craft::$app->getUser()->login($user, $generalConfig->userSessionDuration);
    }

    /**
     * Redirects a user to the `postCpLoginRedirect` location, if they have access to the control panel.
     *
     * @param User $user The user to redirect
     * @return Response|null
     */
    private function _redirectUserToCp(User $user): ?Response
    {
        // Can they access the control panel?
        if ($user->can('accessCp')) {
            $postCpLoginRedirect = Craft::$app->getConfig()->getGeneral()->getPostCpLoginRedirect();
            $url = UrlHelper::cpUrl($postCpLoginRedirect);
            return $this->redirect($url);
        }

        return null;
    }

    /**
     * Redirect the browser after a user’s account has been activated.
     *
     * @param User $user The user that was just activated
     * @return Response
     */
    private function _redirectUserAfterAccountActivation(User $user): Response
    {
        $activateAccountSuccessPath = Craft::$app->getConfig()->getGeneral()->getActivateAccountSuccessPath();
        $url = UrlHelper::siteUrl($activateAccountSuccessPath);
        return $this->redirectToPostedUrl($user, $url);
    }

    /**
     * Redirect the browser after a user has verified their new email address
     *
     * @param User $user The user that just verified their email
     * @return Response
     */
    private function _redirectUserAfterEmailVerification(User $user): Response
    {
        $verifyEmailSuccessPath = Craft::$app->getConfig()->getGeneral()->getVerifyEmailSuccessPath();
        $url = UrlHelper::siteUrl($verifyEmailSuccessPath);
        return $this->redirectToPostedUrl($user, $url);
    }

    /**
     * @param string[] $errors
     * @param string|null $loginName
     * @return Response|null
     * @throws \Exception
     */
    private function _handleSendPasswordResetError(array $errors, ?string $loginName = null): ?Response
    {
        $errorString = implode(', ', $errors);

        return $this->asFailure(
            $errorString,
            [
                'errors' => $errors,
            ],
            [
                'loginName' => $loginName,
                'errors' => $errors,
            ]
        );
    }

    /**
     * Renders the user photo template.
     *
     * @param User $user
     * @return Response
     */
    private function _renderPhotoTemplate(User $user): Response
    {
        $view = $this->getView();
        $templateMode = $view->getTemplateMode();
        if ($templateMode === View::TEMPLATE_MODE_SITE && !$view->doesTemplateExist('users/_photo.twig')) {
            $templateMode = View::TEMPLATE_MODE_CP;
        }

        $data = [
            'html' => $view->renderTemplate('users/_photo.twig', [
                'user' => $user,
            ], $templateMode),
            'photoId' => $user->photoId,
        ];

        if ($user->getIsCurrent() && $this->request->getIsCpRequest()) {
            $data['headerPhotoHtml'] = $view->renderTemplate('_layouts/components/header-photo.twig');
        }

        return $this->asJson($data);
    }

    private function _hashCheck()
    {
        Craft::$app->getSecurity()->validatePassword('p@ss1w0rd', '$2y$13$nj9aiBeb7RfEfYP3Cum6Revyu14QelGGxwcnFUKXIrQUitSodEPRi');
    }

    private function _randomlyDelayResponse(float $maxOffset = 0)
    {
        // Delay randomly between 0.5 and 1.5 seconds.
        $max = 1500000 - (int)($maxOffset * 1000000);
        if ($max > 500000) {
            usleep(random_int(500000, $max));
        }
    }

    /**
     * Marks the user’s feature announcements as read.
     *
     * @return Response
     */
    public function actionMarkAnnouncementsAsRead(): Response
    {
        $this->requirePostRequest();
        $ids = $this->request->getRequiredBodyParam('ids');
        Craft::$app->getAnnouncements()->markAsRead($ids);
        return $this->asSuccess();
    }

    private function populateNameAttributes(object $model): void
    {
        /** @var object|NameTrait $model */
        $fullName = $this->request->getBodyParam('fullName');

        if ($fullName !== null) {
            $model->fullName = $fullName;

            // Unset firstName and lastName so NameTrait::prepareNamesForSave() can set them
            $model->firstName = $model->lastName = null;
        } else {
            // Still check for firstName/lastName in case a front-end form is still posting them
            $firstName = $this->request->getBodyParam('firstName');
            $lastName = $this->request->getBodyParam('lastName');

            if ($firstName !== null || $lastName !== null) {
                $model->firstName = $firstName ?? $model->firstName;
                $model->lastName = $lastName ?? $model->lastName;

                // Unset fullName so NameTrait::prepareNamesForSave() can set it
                $model->fullName = null;
            }
        }
    }

    public function asModelSuccess(
        ModelInterface|Model $model,
        ?string $message = null,
        ?string $modelName = null,
        array $data = [],
        ?string $redirect = null,
    ): Response {
        $this->clearPassword($model);
        return parent::asModelSuccess($model, $message, $modelName, $data, $redirect);
    }

    public function asModelFailure(
        ModelInterface|Model $model,
        ?string $message = null,
        ?string $modelName = null,
        array $data = [],
        array $routeParams = [],
    ): ?Response {
        $this->clearPassword($model);
        return parent::asModelFailure($model, $message, $modelName, $data, $routeParams);
    }

    private function clearPassword(ModelInterface|Model $model): void
    {
        if ($model instanceof User) {
            $model->password = null;
            $model->newPassword = null;
            $model->currentPassword = null;
        }
    }

    /**
     * @throws BadRequestHttpException
     * @throws ForbiddenHttpException
     * @throws WrongEditionException
     */
    private function userActionChecks(): void
    {
        Craft::$app->requireEdition(CmsEdition::Team);
        $this->requirePostRequest();
        $this->requireCpRequest();
        $this->requirePermission('editUsers');
    }
}
