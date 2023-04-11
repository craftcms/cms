<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\controllers;

use Craft;
use craft\base\Element;
use craft\base\NameTrait;
use craft\elements\Address;
use craft\elements\Asset;
use craft\elements\Entry;
use craft\elements\User;
use craft\errors\InvalidElementException;
use craft\errors\UploadFailedException;
use craft\errors\UserLockedException;
use craft\events\DefineUserContentSummaryEvent;
use craft\events\FindLoginUserEvent;
use craft\events\InvalidUserTokenEvent;
use craft\events\LoginFailureEvent;
use craft\events\RegisterUserActionsEvent;
use craft\events\UserEvent;
use craft\helpers\ArrayHelper;
use craft\helpers\Assets;
use craft\helpers\Db;
use craft\helpers\FileHelper;
use craft\helpers\Html;
use craft\helpers\Image;
use craft\helpers\Session;
use craft\helpers\UrlHelper;
use craft\helpers\User as UserHelper;
use craft\i18n\Locale;
use craft\models\UserGroup;
use craft\services\Users;
use craft\web\Application;
use craft\web\assets\edituser\EditUserAsset;
use craft\web\Controller;
use craft\web\Request;
use craft\web\ServiceUnavailableHttpException;
use craft\web\UploadedFile;
use craft\web\View;
use DateTime;
use Throwable;
use yii\base\Exception;
use yii\base\InvalidArgumentException;
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
     * @event RegisterUserActionsEvent The event that is triggered when a user’s available actions are being registered
     */
    public const EVENT_REGISTER_USER_ACTIONS = 'registerUserActions';

    /**
     * @event UserEvent The event that is triggered BEFORE user groups and permissions ARE assigned to the user getting saved
     * @since 3.5.13
     */
    public const EVENT_BEFORE_ASSIGN_GROUPS_AND_PERMISSIONS = 'afterBeforeGroupsAndPermissions';

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
        'login' => self::ALLOW_ANONYMOUS_LIVE | self::ALLOW_ANONYMOUS_OFFLINE,
        'logout' => self::ALLOW_ANONYMOUS_LIVE | self::ALLOW_ANONYMOUS_OFFLINE,
        'impersonate-with-token' => self::ALLOW_ANONYMOUS_LIVE | self::ALLOW_ANONYMOUS_OFFLINE,
        'save-user' => self::ALLOW_ANONYMOUS_LIVE,
        'send-activation-email' => self::ALLOW_ANONYMOUS_LIVE | self::ALLOW_ANONYMOUS_OFFLINE,
        'send-password-reset-email' => self::ALLOW_ANONYMOUS_LIVE | self::ALLOW_ANONYMOUS_OFFLINE,
        'set-password' => self::ALLOW_ANONYMOUS_LIVE | self::ALLOW_ANONYMOUS_OFFLINE,
        'verify-email' => self::ALLOW_ANONYMOUS_LIVE | self::ALLOW_ANONYMOUS_OFFLINE,
    ];

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
     * Displays the login template, and handles login post requests.
     *
     * @return Response|null
     * @throws BadRequestHttpException
     */
    public function actionLogin(): ?Response
    {
        $userSession = Craft::$app->getUser();
        if (!$userSession->getIsGuest()) {
            // Too easy.
            return $this->_handleSuccessfulLogin();
        }

        if (!$this->request->getIsPost()) {
            return null;
        }

        $loginName = $this->request->getRequiredBodyParam('loginName');
        $password = $this->request->getRequiredBodyParam('password');
        $rememberMe = (bool)$this->request->getBodyParam('rememberMe');

        $user = $this->_findLoginUser($loginName);

        if (!$user || $user->password === null) {
            // Delay again to match $user->authenticate()'s delay
            Craft::$app->getSecurity()->validatePassword('p@ss1w0rd', '$2y$13$nj9aiBeb7RfEfYP3Cum6Revyu14QelGGxwcnFUKXIrQUitSodEPRi');
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

        // Try logging them in
        if (!$userSession->login($user, $duration)) {
            // Unknown error
            return $this->_handleLoginFailure(null, $user);
        }

        return $this->_handleSuccessfulLogin();
    }

    private function _findLoginUser(string $loginName): ?User
    {
        $event = new FindLoginUserEvent([
            'loginName' => $loginName,
        ]);
        $this->trigger(self::EVENT_BEFORE_FIND_LOGIN_USER, $event);

        $user = $event->user ?? Craft::$app->getUsers()->getUserByUsernameOrEmail($loginName);

        $event = new FindLoginUserEvent([
            'loginName' => $loginName,
            'user' => $user,
        ]);
        $this->trigger(self::EVENT_AFTER_FIND_LOGIN_USER, $event);
        return $event->user;
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
        $this->requirePostRequest();

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
        Session::set(User::IMPERSONATE_KEY, $userSession->getId());

        if (!$userSession->loginByUserId($userId)) {
            Session::remove(User::IMPERSONATE_KEY);
            $this->setFailFlash(Craft::t('app', 'There was a problem impersonating this user.'));
            Craft::error($userSession->getIdentity()->username . ' tried to impersonate userId: ' . $userId . ' but something went wrong.', __METHOD__);
            return null;
        }

        return $this->_handleSuccessfulLogin();
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
        $this->requirePostRequest();

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

        // Save the original user ID to the session now so User::findIdentity()
        // knows not to worry if the user isn't active yet
        Session::set(User::IMPERSONATE_KEY, $prevUserId);

        if (!$userSession->loginByUserId($userId)) {
            Session::remove(User::IMPERSONATE_KEY);
            $this->setFailFlash(Craft::t('app', 'There was a problem impersonating this user.'));
            Craft::error($userSession->getIdentity()->username . ' tried to impersonate userId: ' . $userId . ' but something went wrong.', __METHOD__);
            return null;
        }

        return $this->_handleSuccessfulLogin();
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
     * Starts an elevated user session.
     *
     * @return Response|null
     */
    public function actionStartElevatedSession(): ?Response
    {
        $password = $this->request->getBodyParam('currentPassword') ?? $this->request->getBodyParam('password');

        try {
            $success = Craft::$app->getUser()->startElevatedSession($password);
        } catch (UserLockedException $e) {
            $authError = Craft::$app->getConfig()->getGeneral()->cooldownDuration
                ? User::AUTH_ACCOUNT_COOLDOWN
                : User::AUTH_ACCOUNT_LOCKED;

            $message = UserHelper::getLoginFailureMessage($authError, $e->user);

            return $this->asFailure($message);
        }

        if (!$success) {
            return $this->asFailure();
        }

        return $this->asSuccess();
    }

    /**
     * @return Response
     */
    public function actionLogout(): Response
    {
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
                $errors[] = Craft::t('app', 'Username or email is required.');

                return $this->_handleSendPasswordResetError($errors);
            }

            $user = Craft::$app->getUsers()->getUserByUsernameOrEmail($loginName);

            if (!$user || !$user->getIsCredentialed()) {
                $errors[] = Craft::t('app', 'Invalid username or email.');
            }
        }

        // Don't try to send the email if there are already error or there is no user
        try {
            if (empty($errors) && !empty($user) && !Craft::$app->getUsers()->sendPasswordResetEmail($user)) {
                throw new Exception();
            }
        } catch (Exception) {
            $errors[] = Craft::t('app', 'There was a problem sending the password reset email.');
        }

        if (!empty($errors) && Craft::$app->getConfig()->getGeneral()->preventUserEnumeration) {
            $list = implode("\n", array_map(function(string $error) {
                return sprintf('- %s', $error);
            }, $errors));
            Craft::warning(sprintf("Password reset email not sent:\n%s", $list), __METHOD__);
            $errors = [];
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
        } catch (InvalidElementException) {
            $errors = $user->getFirstErrors();
            throw new BadRequestHttpException(reset($errors));
        }

        return $this->asJson([
            'url' => $url,
        ]);
    }

    /**
     * Sets a user’s password once they’ve verified they have access to their email.
     *
     * @return Response
     */
    public function actionSetPassword(): Response
    {
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
        $user = Craft::$app->getUsers()->getUserByUid($uid);

        if (!$user) {
            throw new BadRequestHttpException("Invalid user UID: $uid");
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
        if (
            $user->getStatus() == User::STATUS_PENDING &&
            Craft::$app->getUsers()->activateUser($user) &&
            ($response = $this->_onAfterActivateUser($user)) !== null
        ) {
            return $response;
        }

        // Maybe automatically log them in
        $loggedIn = $this->_maybeLoginUserAfterAccountActivation($user);

        if ($this->request->getAcceptsJson()) {
            $return = [
                'status' => $user->getStatus(),
            ];
            if ($loggedIn && Craft::$app->getConfig()->getGeneral()->enableCsrfProtection) {
                $return['csrfTokenValue'] = $this->request->getCsrfToken();
            }
            return $this->asSuccess(data: $return);
        }

        // Can they access the control panel?
        if ($user->can('accessCp')) {
            // Send them to the control panel login page
            $url = UrlHelper::cpUrl(Request::CP_PATH_LOGIN);
        } else {
            // Send them to the 'setPasswordSuccessPath'.
            $setPasswordSuccessPath = Craft::$app->getConfig()->getGeneral()->getSetPasswordSuccessPath();
            $url = UrlHelper::siteUrl($setPasswordSuccessPath);
        }

        return $this->redirect($url);
    }

    /**
     * Verifies that a user has access to an email address.
     *
     * @return Response
     */
    public function actionVerifyEmail(): Response
    {
        if (!is_array($info = $this->_processTokenRequest())) {
            return $info;
        }

        /** @var User $user */
        [$user] = $info;
        $pending = $user->pending;
        $usersService = Craft::$app->getUsers();

        // Do they have an unverified email?
        if ($user->unverifiedEmail) {
            if (!$usersService->verifyEmailForUser($user)) {
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
        $this->requirePostRequest();

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
            return $this->asFailure(Craft::t('app', 'Couldn’t save {type}.', [
                'type' => User::lowerDisplayName(),
            ]));
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
        $this->requirePermission('administrateUsers');
        $this->requirePostRequest();
        $userVariable = $this->request->getValidatedBodyParam('userVariable') ?? 'user';

        $userId = $this->request->getRequiredBodyParam('userId');
        $user = Craft::$app->getUsers()->getUserById($userId);

        if (!$user) {
            $this->_noUserExists();
        }

        try {
            if (!Craft::$app->getUsers()->activateUser($user)) {
                throw new InvalidElementException($user);
            }
        } catch (InvalidElementException) {
            return $this->asModelFailure(
                $user,
                Craft::t('app', 'There was a problem activating the user.'),
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
     * Edit a user account.
     *
     * @param int|string|null $userId The user’s ID, if any, or a string that indicates the user to be edited ('current' or 'client').
     * @param User|null $user The user being edited, if there were any validation errors.
     * @param array|null $errors Any errors that occurred as a result of the previous action.
     * @return Response
     * @throws NotFoundHttpException if the requested user cannot be found
     * @throws BadRequestHttpException if there’s a mismatch between|null $userId and|null $user
     */
    public function actionEditUser(mixed $userId = null, ?User $user = null, ?array $errors = null): Response
    {
        if (!empty($errors)) {
            $firstError = reset($errors);
            if (is_array($firstError)) {
                $this->setFailFlash(implode(', ', $firstError));
            } else {
                $this->setFailFlash($firstError);
            }
        }

        // Determine which user account we're editing
        // ---------------------------------------------------------------------

        $edition = Craft::$app->getEdition();
        $currentUser = static::currentUser();

        if ($user === null) {
            // Are we editing a specific user account?
            if ($userId !== null) {
                /** @var User|null $user */
                $user = User::find()
                    ->addSelect(['users.password', 'users.passwordResetRequired'])
                    ->id($userId === 'current' ? $currentUser->id : $userId)
                    ->status(null)
                    ->one();
            } elseif ($edition === Craft::Pro) {
                // Registering a new user
                $user = new User();
            }

            if (!$user) {
                throw new NotFoundHttpException('User not found');
            }
        }

        /** @var User $user */
        $isNewUser = !$user->id;

        // Make sure they have permission to edit this user
        // ---------------------------------------------------------------------

        $isCurrentUser = $user->getIsCurrent();
        if (!$isCurrentUser) {
            if ($isNewUser) {
                $this->requirePermission('registerUsers');
            } else {
                $this->requirePermission('editUsers');
            }
        }

        $canAdministrateUsers = $currentUser->can('administrateUsers');
        $canModerateUsers = $currentUser->can('moderateUsers');

        $name = trim($user->getName());

        // Determine which actions should be available
        // ---------------------------------------------------------------------

        $statusLabel = null;
        $statusActions = [];
        $sessionActions = [];
        $destructiveActions = [];
        $miscActions = [];

        if ($edition === Craft::Pro && !$isNewUser) {
            switch ($user->getStatus()) {
                case Element::STATUS_ARCHIVED:
                case Element::STATUS_DISABLED:
                    $statusLabel = $user->archived ? Craft::t('app', 'Archived') : Craft::t('app', 'Disabled');
                    if (Craft::$app->getElements()->canSave($user)) {
                        $statusActions[] = [
                            'action' => 'users/enable-user',
                            'label' => Craft::t('app', 'Enable'),
                        ];
                    }
                    break;
                case User::STATUS_INACTIVE:
                case User::STATUS_PENDING:
                    $statusLabel = $user->pending ? Craft::t('app', 'Pending') : Craft::t('app', 'Inactive');
                    // Only provide activation actions if they have an email address
                    if ($user->email) {
                        if ($user->pending || $canAdministrateUsers) {
                            $statusActions[] = [
                                'action' => 'users/send-activation-email',
                                'label' => Craft::t('app', 'Send activation email'),
                            ];
                        }
                        if ($canAdministrateUsers) {
                            // Only need to show the "Copy activation URL" option if they don't have a password
                            if (!$user->password) {
                                $statusActions[] = [
                                    'id' => 'copy-passwordreset-url',
                                    'label' => Craft::t('app', 'Copy activation URL…'),
                                ];
                            }
                            $statusActions[] = [
                                'action' => 'users/activate-user',
                                'label' => Craft::t('app', 'Activate account'),
                            ];
                        }
                    }
                    break;
                case User::STATUS_SUSPENDED:
                    $statusLabel = Craft::t('app', 'Suspended');
                    if (Craft::$app->getUsers()->canSuspend($currentUser, $user)) {
                        $statusActions[] = [
                            'action' => 'users/unsuspend-user',
                            'label' => Craft::t('app', 'Unsuspend'),
                        ];
                    }
                    break;
                case User::STATUS_ACTIVE:
                    if ($user->locked) {
                        $statusLabel = Craft::t('app', 'Locked');
                        if (
                            !$isCurrentUser &&
                            ($currentUser->admin || !$user->admin) &&
                            $canModerateUsers &&
                            (
                                ($previousUserId = Session::get(User::IMPERSONATE_KEY)) === null ||
                                $user->id != $previousUserId
                            )
                        ) {
                            $statusActions[] = [
                                'action' => 'users/unlock-user',
                                'label' => Craft::t('app', 'Unlock'),
                            ];
                        }
                    } else {
                        $statusLabel = Craft::t('app', 'Active');
                    }

                    if (!$isCurrentUser) {
                        $statusActions[] = [
                            'action' => 'users/send-password-reset-email',
                            'label' => Craft::t('app', 'Send password reset email'),
                        ];
                        if ($canAdministrateUsers) {
                            $statusActions[] = [
                                'id' => 'copy-passwordreset-url',
                                'label' => Craft::t('app', 'Copy password reset URL…'),
                            ];
                        }
                    }
                    break;
            }

            if (!$isCurrentUser) {
                if (Craft::$app->getUsers()->canImpersonate($currentUser, $user)) {
                    $sessionActions[] = [
                        'action' => 'users/impersonate',
                        'label' => $name
                            ? Craft::t('app', 'Sign in as {user}', ['user' => $user->getName()])
                            : Craft::t('app', 'Sign in as user'),
                    ];
                    $sessionActions[] = [
                        'id' => 'copy-impersonation-url',
                        'label' => Craft::t('app', 'Copy impersonation URL…'),
                    ];
                }

                if (Craft::$app->getUsers()->canSuspend($currentUser, $user) && $user->active && !$user->suspended) {
                    $destructiveActions[] = [
                        'action' => 'users/suspend-user',
                        'label' => Craft::t('app', 'Suspend'),
                    ];
                }
            }

            // Destructive actions that should only be performed on non-admins, unless the current user is also an admin
            if (!$user->admin || $currentUser->admin) {
                if (($isCurrentUser || $canAdministrateUsers) && ($user->active || $user->pending)) {
                    $destructiveActions[] = [
                        'action' => 'users/deactivate-user',
                        'label' => Craft::t('app', 'Deactivate…'),
                        'confirm' => Craft::t('app', 'Deactivating a user revokes their ability to sign in. Are you sure you want to continue?'),
                    ];
                }

                if ($isCurrentUser || $currentUser->can('deleteUsers')) {
                    $destructiveActions[] = [
                        'id' => 'delete-btn',
                        'label' => Craft::t('app', 'Delete…'),
                    ];
                }
            }
        }

        // Give plugins a chance to modify these, or add new ones
        $event = new RegisterUserActionsEvent([
            'user' => $user,
            'statusActions' => $statusActions,
            'sessionActions' => $sessionActions,
            'destructiveActions' => $destructiveActions,
            'miscActions' => $miscActions,
        ]);
        $this->trigger(self::EVENT_REGISTER_USER_ACTIONS, $event);

        $actions = array_filter([
            $event->statusActions,
            $event->miscActions,
            $event->sessionActions,
            array_map(function(array $action): array {
                $action['destructive'] = true;
                return $action;
            }, $event->destructiveActions),
        ]);

        // Set the appropriate page title
        // ---------------------------------------------------------------------

        if (!$isNewUser) {
            if ($isCurrentUser) {
                $title = Craft::t('app', 'My Account');
            } elseif ($name) {
                $title = Craft::t('app', '{user}’s Account', ['user' => $name]);
            } else {
                $title = Craft::t('app', 'Edit User');
            }
        } else {
            $title = Craft::t('app', 'Create a new user');
        }

        // Prep the form tabs & content
        // ---------------------------------------------------------------------

        $form = $user->getFieldLayout()->createForm($user, false, [
            'tabIdPrefix' => 'profile',
            'registerDeltas' => true,
        ]);
        $selectedTab = 'account';

        $tabs = [
            'account' => [
                'label' => Craft::t('app', 'Account'),
                'url' => '#account',
            ],
        ];

        $tabs += $form->getTabMenu();

        // Show the permission tab for the users that can change them on Craft Pro editions
        $canAssignUserGroups = $currentUser->canAssignUserGroups();
        $showPermissionsTab = (
            $edition === Craft::Pro &&
            ($currentUser->can('assignUserPermissions') || $canAssignUserGroups)
        );

        if ($showPermissionsTab) {
            $tabs['perms'] = [
                'label' => Craft::t('app', 'Permissions'),
                'url' => '#perms',
            ];
        }

        // Show the preferences tab if it's the current user
        if ($isCurrentUser) {
            $tabs['prefs'] = [
                'label' => Craft::t('app', 'Preferences'),
                'url' => '#prefs',
            ];
        }

        // Just one tab looks awkward, so just don't show them at all then.
        if (count($tabs) == 1) {
            $tabs = [];
        } else {
            if ($user->hasErrors()) {
                // Add the 'error' class to any tabs that have errors
                $errors = $user->getErrors();
                $accountFields = [
                    'username',
                    'fullName',
                    'email',
                    'password',
                    'newPassword',
                    'currentPassword',
                    'passwordResetRequired',
                    'preferredLanguage',
                ];

                foreach ($errors as $attribute => $error) {
                    if (isset($tabs['account']) && in_array($attribute, $accountFields, true)) {
                        $tabs['account']['class'] = 'error';
                    } else {
                        if (isset($tabs['profile'])) {
                            $tabs['profile']['class'] = 'error';
                        }
                    }
                }
            }
        }

        $fieldsHtml = $form->render(false);

        // Prepare the language/locale options
        // ---------------------------------------------------------------------

        if ($isCurrentUser) {
            $i18n = Craft::$app->getI18n();

            // Language
            $appLocales = $i18n->getAppLocales();
            ArrayHelper::multisort($appLocales, fn(Locale $locale) => $locale->getDisplayName());
            $languageId = Craft::$app->getLocale()->getLanguageID();

            $languageOptions = array_map(fn(Locale $locale) => [
                'label' => $locale->getDisplayName(Craft::$app->language),
                'value' => $locale->id,
                'data' => [
                    'data' => [
                        'hint' => $locale->getLanguageID() !== $languageId ? $locale->getDisplayName() : false,
                        'hintLang' => $locale->id,
                    ],
                ],
            ], $appLocales);

            $userLanguage = $user->getPreferredLanguage();

            if (
                !$userLanguage ||
                !ArrayHelper::contains($appLocales, fn(Locale $locale) => $locale->id === $userLanguage)
            ) {
                $userLanguage = Craft::$app->language;
            }

            // Formatting Locale
            $allLocales = $i18n->getAllLocales();
            ArrayHelper::multisort($allLocales, fn(Locale $locale) => $locale->getDisplayName());

            $localeOptions = [
                ['label' => Craft::t('app', 'Same as language'), 'value' => ''],
            ];
            array_push($localeOptions, ...array_map(fn(Locale $locale) => [
                'label' => $locale->getDisplayName(Craft::$app->language),
                'value' => $locale->id,
                'data' => [
                    'data' => [
                        'hint' => $locale->getLanguageID() !== $languageId ? $locale->getDisplayName() : false,
                        'hintLang' => $locale->id,
                    ],
                ],
            ], $allLocales));

            $userLocale = $user->getPreferredLocale();

            if (
                !$userLocale ||
                !ArrayHelper::contains($allLocales, fn(Locale $locale) => $locale->id === $userLocale)
            ) {
                $userLocale = Craft::$app->getConfig()->getGeneral()->defaultCpLocale;
            }
        } else {
            $languageOptions = $localeOptions = $userLanguage = $userLocale = null;
        }

        // Determine whether user photo uploading should be possible
        $volumeUid = Craft::$app->getProjectConfig()->get('users.photoVolumeUid');
        $showPhotoField = $volumeUid && Craft::$app->getVolumes()->getVolumeByUid($volumeUid);

        // Load the resources and render the page
        // ---------------------------------------------------------------------

        // Body class
        $bodyClass = 'edit-user';

        $this->getView()->registerAssetBundle(EditUserAsset::class);

        $deleteModalRedirect = Craft::$app->getSecurity()->hashData(Craft::$app->getEdition() === Craft::Pro ? 'users' : 'dashboard');

        $this->getView()->registerJsWithVars(
            fn($userId, $isCurrent, $deleteModalRedirect) => <<<JS
new Craft.AccountSettingsForm($userId, $isCurrent, {
    deleteModalRedirect: $deleteModalRedirect,
})
JS,
            [$user->id, $isCurrentUser, $deleteModalRedirect],
            View::POS_END
        );

        return $this->renderTemplate('users/_edit.twig', compact(
            'user',
            'isNewUser',
            'statusLabel',
            'actions',
            'languageOptions',
            'localeOptions',
            'userLanguage',
            'userLocale',
            'bodyClass',
            'title',
            'tabs',
            'selectedTab',
            'showPhotoField',
            'showPermissionsTab',
            'canAssignUserGroups',
            'fieldsHtml'
        ));
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
        $requireEmailVerification = $userSettings['requireEmailVerification'] ?? true;
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
            Craft::$app->requireEdition(Craft::Pro);

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

            $user = $user ?? new User();
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
        } else {
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

        // Is their admin status changing?
        if (
            $currentUser &&
            $currentUser->admin &&
            ($adminParam = $this->request->getBodyParam('admin', $user->admin)) != $user->admin
        ) {
            if ($adminParam) {
                $this->requireElevatedSession();
                $user->admin = true;
            } else {
                $user->admin = false;
            }
        }

        // If this is public registration and it's a Pro version,
        // set the default group on the user, so that any content
        // based on user group condition can be validated and saved against them
        if ($isPublicRegistration) {
            $defaultGroupUid = Craft::$app->getProjectConfig()->get('users.defaultGroup');
            if ($defaultGroupUid) {
                $group = Craft::$app->userGroups->getGroupByUid($defaultGroupUid);
                if ($group) {
                    $user->setGroups([$group]);
                }
            }
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
                Craft::t('app', 'Couldn’t save user.'),
                $userVariable
            );
        }

        // If this is a new user and email verification isn't required,
        // go ahead and activate them now.
        if ($isNewUser && !$requireEmailVerification && !$deactivateByDefault) {
            Craft::$app->getUsers()->activateUser($user);
        }

        // Save their preferences too
        $preferences = [
            'language' => $this->request->getBodyParam('preferredLanguage', $user->getPreference('language')),
            'locale' => $this->request->getBodyParam('preferredLocale', $user->getPreference('locale')) ?: null,
            'weekStartDay' => $this->request->getBodyParam('weekStartDay', $user->getPreference('weekStartDay')),
            'alwaysShowFocusRings' => (bool)$this->request->getBodyParam('alwaysShowFocusRings', $user->getPreference('alwaysShowFocusRings')),
            'useShapes' => (bool)$this->request->getBodyParam('useShapes', $user->getPreference('useShapes')),
            'underlineLinks' => (bool)$this->request->getBodyParam('underlineLinks', $user->getPreference('underlineLinks')),
            'notificationDuration' => $this->request->getBodyParam('notificationDuration', $user->getPreference('notificationDuration')),
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

        if ($isCurrentUser) {
            Craft::$app->updateTargetLanguage();
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

        if (Craft::$app->getEdition() === Craft::Pro) {
            // If this is public registration, assign the user to the default user group
            if ($isPublicRegistration) {
                // Assign them to the default user group
                Craft::$app->getUsers()->assignUserToDefaultGroup($user);
            } elseif ($currentUser) {
                // Fire an 'afterBeforeGroupsAndPermissions' event
                if ($this->hasEventHandlers(self::EVENT_BEFORE_ASSIGN_GROUPS_AND_PERMISSIONS)) {
                    $this->trigger(self::EVENT_BEFORE_ASSIGN_GROUPS_AND_PERMISSIONS, new UserEvent([
                        'user' => $user,
                    ]));
                }

                // Assign user groups and permissions if the current user is allowed to do that
                $this->_saveUserPermissions($user, $currentUser);
                $this->_saveUserGroups($user, $currentUser);

                // Fire an 'afterAssignGroupsAndPermissions' event
                if ($this->hasEventHandlers(self::EVENT_AFTER_ASSIGN_GROUPS_AND_PERMISSIONS)) {
                    $this->trigger(self::EVENT_AFTER_ASSIGN_GROUPS_AND_PERMISSIONS, new UserEvent([
                        'user' => $user,
                    ]));
                }
            }
        }

        // Do we need to send a verification email out?
        if ($sendActivationEmail) {
            // Temporarily set the unverified email on the User so the verification email goes to the
            // right place
            $originalEmail = $user->email;
            $user->email = $user->unverifiedEmail;

            if ($isNewUser) {
                // Send the activation email
                Craft::$app->getUsers()->sendActivationEmail($user);
            } else {
                // Send the standard verification email
                Craft::$app->getUsers()->sendNewEmailVerifyEmail($user);
            }

            // Put the original email back into place
            $user->email = $originalEmail;
        }

        // Is this public registration, and was the user going to be activated automatically?
        $publicActivation = $isPublicRegistration && $user->getStatus() === User::STATUS_ACTIVE;
        $loggedIn = $publicActivation && $this->_maybeLoginUserAfterAccountActivation($user);
        $returnCsrfToken = $returnCsrfToken || $loggedIn;

        if ($this->request->getAcceptsJson()) {
            $return = [
                'id' => $user->id,
            ];
            if ($returnCsrfToken && $generalConfig->enableCsrfProtection) {
                $return['csrfTokenValue'] = $this->request->getCsrfToken();
            }

            return $this->asSuccess(data: $return);
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
            $users->saveUserPhoto($fileLocation, $user, $file->name);

            return $this->asJson([
                'html' => $this->_renderPhotoTemplate($user),
                'photoId' => $user->photoId,
            ]);
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

        return $this->asJson([
            'html' => $this->_renderPhotoTemplate($user),
        ]);
    }

    /**
     * Sends a new activation email to a user.
     *
     * @return Response|null
     * @throws BadRequestHttpException if the user is not pending
     */
    public function actionSendActivationEmail(): ?Response
    {
        $this->requirePostRequest();

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
            $this->requirePermission('administrateUsers');
        }

        try {
            $emailSent = Craft::$app->getUsers()->sendActivationEmail($user);
        } catch (InvalidElementException) {
            $emailSent = false;
        }

        $userVariable = $this->request->getValidatedBodyParam('userVariable') ?? 'user';
        if ($user->hasErrors()) {
            return $this->asModelFailure(
                $user,
                Craft::t('app', 'Couldn’t send activation email.'),
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
        $this->requirePostRequest();
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
            $previousUserId = Session::get(User::IMPERSONATE_KEY);
            if ($previousUserId && $user->id == $previousUserId) {
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
        $this->requirePostRequest();
        $this->requirePermission('moderateUsers');

        $userId = $this->request->getRequiredBodyParam('userId');
        $user = Craft::$app->getUsers()->getUserById($userId);

        if (!$user) {
            $this->_noUserExists();
        }

        $usersService = Craft::$app->getUsers();
        $currentUser = static::currentUser();

        if (!$usersService->canSuspend($currentUser, $user) || !$usersService->suspendUser($user)) {
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

        foreach (Craft::$app->getSections()->getAllSections() as $section) {
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

        // Fire a 'defineUserContentSummary' event
        $event = new DefineUserContentSummaryEvent([
            'userId' => $userId,
            'contentSummary' => $summary,
        ]);
        $this->trigger(self::EVENT_DEFINE_CONTENT_SUMMARY, $event);

        return $this->asJson($event->contentSummary);
    }

    /**
     * Deactivates a user.
     *
     * @return Response|null
     * @since 4.0.0
     */
    public function actionDeactivateUser(): ?Response
    {
        $this->requirePostRequest();

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
        if (Craft::$app->getUsers()->deactivateUser($user)) {
            $this->setSuccessFlash(Craft::t('app', 'Successfully deactivated the user.'));
        } else {
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
            $this->setFailFlash(Craft::t('app', 'Couldn’t delete the user.'));
            return null;
        }

        $this->setSuccessFlash(Craft::t('app', 'User deleted.'));
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
        $this->requirePostRequest();
        $this->requirePermission('moderateUsers');

        $userId = $this->request->getRequiredBodyParam('userId');
        $user = Craft::$app->getUsers()->getUserById($userId);

        if (!$user) {
            $this->_noUserExists();
        }

        // Even if you have moderateUsers permissions, only and admin should be able to unsuspend another admin.
        $usersService = Craft::$app->getUsers();
        $currentUser = static::currentUser();

        if (!$usersService->canSuspend($currentUser, $user) || !$usersService->unsuspendUser($user)) {
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

            if ($address->ownerId !== $userId) {
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
            $value = $this->request->getBodyParam($name);
            if ($value !== null) {
                $safeAttributes[$name] = $value;
            }
        }
        $address->setAttributes($safeAttributes);

        // Custom fields
        $fieldsLocation = $this->request->getParam('fieldsLocation') ?? 'fields';
        $address->setFieldValuesFromRequest($fieldsLocation);

        if (!$elementsService->saveElement($address)) {
            return $this->asModelFailure($address, Craft::t('app', 'Couldn’t save {type}.', [
                'type' => Address::lowerDisplayName(),
            ]), 'address');
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
            'groups',
            'photo',
            'firstName',
            'lastName',
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
     * Verifies a password for a user.
     *
     * @return Response|null
     */
    public function actionVerifyPassword(): ?Response
    {
        $this->requireAcceptsJson();

        if ($this->_verifyExistingPassword()) {
            return $this->asSuccess();
        }

        return $this->asFailure(Craft::t('app', 'Invalid password.'));
    }

    /**
     * Handles a failed login attempt.
     *
     * @param string|null $authError
     * @param User|null $user
     * @return Response|null
     * @throws ServiceUnavailableHttpException
     */
    private function _handleLoginFailure(?string $authError, ?User $user = null): ?Response
    {
        // Delay randomly between 0 and 1.5 seconds.
        usleep(random_int(0, 1500000));

        $message = UserHelper::getLoginFailureMessage($authError, $user);

        // Fire a 'loginFailure' event
        $event = new LoginFailureEvent([
            'authError' => $authError,
            'message' => $message,
            'user' => $user,
        ]);
        $this->trigger(self::EVENT_LOGIN_FAILURE, $event);

        return $this->asFailure(
            $event->message,
            data: [
                'errorCode' => $authError,
            ],
            routeParams: [
                'loginName' => $this->request->getBodyParam('loginName'),
                'rememberMe' => (bool)$this->request->getBodyParam('rememberMe'),
                'errorCode' => $authError,
                'errorMessage' => $event->message,
            ]
        );
    }

    /**
     * Redirects the user after a successful login attempt, or if they visited the Login page while they were already
     * logged in.
     *
     * @return Response
     */
    private function _handleSuccessfulLogin(): Response
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

            return $this->asSuccess(data: $return);
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
        // If this is a site request, try handling the request like normal
        if ($this->request->getIsSiteRequest()) {
            try {
                Craft::$app->getUrlManager()->setRouteParams([
                    'variables' => $variables,
                ]);

                // Avoid re-routing to the same action again
                $this->request->checkIfActionRequest(true, true, false);
                if ($this->request->getActionSegments() === ['users', 'set-password']) {
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
        return $this->renderTemplate('setpassword.twig', $variables, View::TEMPLATE_MODE_CP);
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

        // Did they upload a new one?
        if ($photo = UploadedFile::getInstanceByName('photo')) {
            $fileLocation = Assets::tempFilePath($photo->getExtension());
            move_uploaded_file($photo->tempName, $fileLocation);
            $filename = $photo->name;
            $newPhoto = true;
        } elseif (($photo = $this->request->getBodyParam('photo')) && is_array($photo)) {
            // base64-encoded photo
            $matches = [];

            if (preg_match('/^data:((?<type>[a-z0-9]+\/[a-z0-9\+]+);)?base64,(?<data>.+)/i', $photo['data'] ?? '', $matches)) {
                $filename = $photo['filename'] ?? null;
                $extension = $filename ? pathinfo($filename, PATHINFO_EXTENSION) : null;

                if (!$extension && !empty($matches['type'])) {
                    try {
                        $extension = FileHelper::getExtensionByMimeType($matches['type']);
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
                $users->saveUserPhoto($fileLocation, $user, $filename);
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
        $oldGroupIds = ArrayHelper::getColumn($user->getGroups(), 'id');
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
            $url = Craft::$app->getConfig()->getGeneral()->getInvalidUserTokenPath();
            return $this->redirect(UrlHelper::siteUrl($url));
        }

        throw new BadRequestHttpException(Craft::t('app', 'Invalid verification code. Please sign in or reset your password.'));
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
     * Possibly log a user in right after they were activated or reset their password, if Craft is configured to do so.
     *
     * @param User $user The user that was just activated or reset their password
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
     * @return string The rendered HTML
     */
    private function _renderPhotoTemplate(User $user): string
    {
        $view = $this->getView();
        $templateMode = $view->getTemplateMode();
        if ($templateMode === View::TEMPLATE_MODE_SITE && !$view->doesTemplateExist('users/_photo')) {
            $templateMode = View::TEMPLATE_MODE_CP;
        }

        return $view->renderTemplate('users/_photo.twig', [
            'user' => $user,
        ], $templateMode);
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
        } else {
            // Still check for firstName/lastName in case a front-end form is still posting them
            $firstName = $this->request->getBodyParam('firstName');
            $lastName = $this->request->getBodyParam('lastName');

            if ($firstName !== null || $lastName !== null) {
                $model->fullName = null;
                $model->firstName = $firstName ?? $model->firstName;
                $model->lastName = $lastName ?? $model->lastName;
            }
        }
    }
}
