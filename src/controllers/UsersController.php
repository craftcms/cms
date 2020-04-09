<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\controllers;

use Craft;
use craft\base\Element;
use craft\base\Field;
use craft\elements\Asset;
use craft\elements\Entry;
use craft\elements\User;
use craft\errors\UploadFailedException;
use craft\errors\UserLockedException;
use craft\events\DefineUserContentSummaryEvent;
use craft\events\LoginFailureEvent;
use craft\events\RegisterUserActionsEvent;
use craft\events\UserEvent;
use craft\helpers\Assets;
use craft\helpers\FileHelper;
use craft\helpers\Image;
use craft\helpers\Json;
use craft\helpers\UrlHelper;
use craft\helpers\User as UserHelper;
use craft\services\Users;
use craft\web\assets\edituser\EditUserAsset;
use craft\web\Controller;
use craft\web\Request;
use craft\web\ServiceUnavailableHttpException;
use craft\web\UploadedFile;
use craft\web\View;
use yii\base\InvalidArgumentException;
use yii\web\BadRequestHttpException;
use yii\web\ForbiddenHttpException;
use yii\web\HttpException;
use yii\web\NotFoundHttpException;
use yii\web\Response;

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
     * @event LoginFailureEvent The event that is triggered when a failed login attempt was made
     */
    const EVENT_LOGIN_FAILURE = 'loginFailure';

    /**
     * @event RegisterUserActionsEvent The event that is triggered when a user’s available actions are being registered
     */
    const EVENT_REGISTER_USER_ACTIONS = 'registerUserActions';

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
    const EVENT_DEFINE_CONTENT_SUMMARY = 'defineContentSummary';

    /**
     * @inheritdoc
     */
    protected $allowAnonymous = [
        'get-remaining-session-time' => self::ALLOW_ANONYMOUS_LIVE | self::ALLOW_ANONYMOUS_OFFLINE,
        'session-info' => self::ALLOW_ANONYMOUS_LIVE | self::ALLOW_ANONYMOUS_OFFLINE,
        'login' => self::ALLOW_ANONYMOUS_LIVE | self::ALLOW_ANONYMOUS_OFFLINE,
        'logout' => self::ALLOW_ANONYMOUS_LIVE | self::ALLOW_ANONYMOUS_OFFLINE,
        'save-user' => self::ALLOW_ANONYMOUS_LIVE,
        'send-activation-email' => self::ALLOW_ANONYMOUS_LIVE | self::ALLOW_ANONYMOUS_OFFLINE,
        'send-password-reset-email' => self::ALLOW_ANONYMOUS_LIVE | self::ALLOW_ANONYMOUS_OFFLINE,
        'set-password' => self::ALLOW_ANONYMOUS_LIVE | self::ALLOW_ANONYMOUS_OFFLINE,
        'verify-email' => self::ALLOW_ANONYMOUS_LIVE | self::ALLOW_ANONYMOUS_OFFLINE,
    ];

    /**
     * @inheritdoc
     */
    public function beforeAction($action)
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
    public function actionLogin()
    {
        $userSession = Craft::$app->getUser();
        if (!$userSession->getIsGuest()) {
            // Too easy.
            return $this->_handleSuccessfulLogin();
        }

        $request = Craft::$app->getRequest();
        if (!$request->getIsPost()) {
            return null;
        }

        $loginName = $request->getRequiredBodyParam('loginName');
        $password = $request->getRequiredBodyParam('password');
        $rememberMe = (bool)$request->getBodyParam('rememberMe');

        // Does a user exist with that username/email?
        $user = Craft::$app->getUsers()->getUserByUsernameOrEmail($loginName);

        // Delay randomly between 0 and 1.5 seconds.
        usleep(random_int(0, 1500000));

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

    /**
     * Logs a user in for impersonation. Requires you to be an administrator.
     *
     * @return Response|null
     * @throws ForbiddenHttpException
     */
    public function actionImpersonate()
    {
        $this->requireLogin();
        $this->requirePostRequest();

        $userSession = Craft::$app->getUser();
        $session = Craft::$app->getSession();
        $request = Craft::$app->getRequest();

        $userId = $request->getBodyParam('userId');

        // Make sure they're allowed to impersonate this user
        $usersService = Craft::$app->getUsers();
        $impersonatee = $usersService->getUserById($userId);
        if (!$usersService->canImpersonate($userSession->getIdentity(), $impersonatee)) {
            throw new ForbiddenHttpException('You do not have sufficient permissions to impersonate this user');
        }

        // Save the original user ID to the session now so User::findIdentity()
        // knows not to worry if the user isn't active yet
        $session->set(User::IMPERSONATE_KEY, $userSession->getId());

        if (!$userSession->loginByUserId($userId)) {
            $session->remove(User::IMPERSONATE_KEY);
            $session->setError(Craft::t('app', 'There was a problem impersonating this user.'));
            Craft::error($userSession->getIdentity()->username . ' tried to impersonate userId: ' . $userId . ' but something went wrong.', __METHOD__);

            return null;
        }

        return $this->_handleSuccessfulLogin();
    }

    /**
     * Returns how many seconds are left in the current user session.
     *
     * @return Response
     * @deprecated in 3.4.0. Use [[actionSessionInfo()]] instead.
     */
    public function actionGetRemainingSessionTime(): Response
    {
        Craft::$app->getDeprecator()->log(__METHOD__, 'The users/get-remaining-session-time action is deprecated. Use users/session-info instead.');
        return $this->runAction('session-info');
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
            $return['csrfTokenValue'] = Craft::$app->getRequest()->getCsrfToken();
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
     * return Response
     */
    public function actionStartElevatedSession()
    {
        $request = Craft::$app->getRequest();
        $password = $request->getBodyParam('currentPassword') ?? $request->getBodyParam('password');

        try {
            $success = Craft::$app->getUser()->startElevatedSession($password);
        } catch (UserLockedException $e) {
            $authError = Craft::$app->getConfig()->getGeneral()->cooldownDuration
                ? User::AUTH_ACCOUNT_COOLDOWN
                : User::AUTH_ACCOUNT_LOCKED;

            $message = UserHelper::getLoginFailureMessage($authError, $e->user);

            return $this->asJson([
                'success' => false,
                'message' => $message,
            ]);
        }

        return $this->asJson([
            'success' => $success,
        ]);
    }

    /**
     * @return Response
     */
    public function actionLogout(): Response
    {
        // Passing false here for reasons.
        Craft::$app->getUser()->logout(false);

        $request = Craft::$app->getRequest();
        if ($request->getAcceptsJson()) {
            $return = [
                'success' => true
            ];

            if (Craft::$app->getConfig()->getGeneral()->enableCsrfProtection) {
                $return['csrfTokenValue'] = $request->getCsrfToken();
            }

            return $this->asJson($return);
        }

        // Redirect to the login page if this is a CP request
        if ($request->getIsCpRequest()) {
            return $this->redirect(Request::CP_PATH_LOGIN);
        }

        return $this->redirect(Craft::$app->getConfig()->getGeneral()->getPostLogoutRedirect());
    }

    /**
     * Sends a password reset email.
     *
     * @return Response|null
     * @throws NotFoundHttpException if the requested user cannot be found
     */
    public function actionSendPasswordResetEmail()
    {
        $this->requirePostRequest();
        $request = Craft::$app->getRequest();

        $errors = [];
        $existingUser = false;
        $loginName = null;

        // If someone's logged in and they're allowed to edit other users, then see if a userId was submitted
        if (Craft::$app->getUser()->checkPermission('editUsers')) {
            $userId = $request->getBodyParam('userId');

            if ($userId) {
                $user = Craft::$app->getUsers()->getUserById($userId);

                if (!$user) {
                    throw new NotFoundHttpException('User not found');
                }

                $existingUser = true;
            }
        }

        /** @noinspection UnSafeIsSetOverArrayInspection - FP */
        if (!isset($user)) {
            $loginName = $request->getBodyParam('loginName');

            if (!$loginName) {
                // If they didn't even enter a username/email, just bail now.
                $errors[] = Craft::t('app', 'Username or email is required.');

                return $this->_handleSendPasswordResetError($errors);
            }

            $user = Craft::$app->getUsers()->getUserByUsernameOrEmail($loginName);

            if (!$user) {
                $errors[] = Craft::t('app', 'Invalid username or email.');
            }
        }

        // If no one is logged in and preventUserEnumeration is enabled, clear out the login errors
        if (!$existingUser && Craft::$app->getConfig()->getGeneral()->preventUserEnumeration) {
            $errors = [];
        }

        if (!empty($user) && !Craft::$app->getUsers()->sendPasswordResetEmail($user)) {
            $errors[] = Craft::t('app', 'There was a problem sending the password reset email.');
        }

        if (empty($errors)) {
            if ($request->getAcceptsJson()) {
                return $this->asJson(['success' => true]);
            }

            Craft::$app->getSession()->setNotice(Craft::t('app', 'Password reset email sent.'));

            return $this->redirectToPostedUrl();
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
    public function actionGetPasswordResetUrl()
    {
        $this->requirePermission('administrateUsers');

        if (!$this->_verifyElevatedSession()) {
            throw new BadRequestHttpException('Existing password verification failed');
        }

        $userId = Craft::$app->getRequest()->getRequiredParam('userId');
        $user = Craft::$app->getUsers()->getUserById($userId);

        if (!$user) {
            $this->_noUserExists();
        }

        return $this->asJson([
            'url' => Craft::$app->getUsers()->getPasswordResetUrl($user)
        ]);
    }

    /**
     * Sets a user's password once they've verified they have access to their email.
     *
     * @return Response
     */
    public function actionSetPassword(): Response
    {
        // Have they just submitted a password, or are we just displaying the page?
        $request = Craft::$app->getRequest();
        if (!$request->getIsPost()) {
            if (!is_array($info = $this->_processTokenRequest())) {
                return $info;
            }

            /** @var User $user */
            /** @var string $uid */
            /** @var string $code */
            list($user, $uid, $code) = $info;

            Craft::$app->getUser()->sendUsernameCookie($user);

            // Send them to the set password template.
            return $this->_renderSetPasswordTemplate([
                'code' => $code,
                'id' => $uid,
                'newUser' => $user->password ? false : true,
            ]);
        }

        // POST request. They've just set the password.
        $code = $request->getRequiredBodyParam('code');
        $uid = $request->getRequiredParam('id');
        $user = Craft::$app->getUsers()->getUserByUid($uid);

        // See if we still have a valid token.
        $isCodeValid = Craft::$app->getUsers()->isVerificationCodeValidForUser($user, $code);

        if (!$user || !$isCodeValid) {
            return $this->_processInvalidToken();
        }

        $user->newPassword = $request->getRequiredBodyParam('newPassword');
        $user->setScenario(User::SCENARIO_PASSWORD);

        if (!Craft::$app->getElements()->saveElement($user)) {
            $errors = $user->getErrors('newPassword');

            if ($request->getAcceptsJson()) {
                return $this->asErrorJson(implode(', ', $errors));
            }

            Craft::$app->getSession()->setError(Craft::t('app', 'Couldn’t update password.'));

            return $this->_renderSetPasswordTemplate([
                'errors' => $errors,
                'code' => $code,
                'id' => $uid,
                'newUser' => $user->password ? false : true,
            ]);
        }

        if ($user->getStatus() == User::STATUS_PENDING) {
            // Activate them
            Craft::$app->getUsers()->activateUser($user);

            // Treat this as an activation request
            if (($response = $this->_onAfterActivateUser($user)) !== null) {
                return $response;
            }
        }

        // Maybe automatically log them in
        $this->_maybeLoginUserAfterAccountActivation($user);

        if ($request->getAcceptsJson()) {
            return $this->asJson(['success' => true]);
        }

        // Can they access the CP?
        if ($user->can('accessCp')) {
            // Send them to the CP login page
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
        list($user) = $info;
        $userIsPending = $user->getStatus() === User::STATUS_PENDING;

        if (!Craft::$app->getUsers()->verifyEmailForUser($user)) {
            return $this->renderTemplate('_special/emailtaken', [
                'email' => $user->unverifiedEmail
            ]);
        }

        // If they're logged in, give them a success notice
        if (!Craft::$app->getUser()->getIsGuest()) {
            Craft::$app->getSession()->setNotice(Craft::t('app', 'Email verified'));
        }

        // They were just activated, so treat this as an activation request
        if ($userIsPending && ($response = $this->_onAfterActivateUser($user)) !== null) {
            return $response;
        }

        return $this->_redirectUserToCp($user) ?? $this->_redirectUserAfterEmailVerification($user);
    }

    /**
     * Manually activates a user account. Only admins have access.
     *
     * @return Response
     */
    public function actionActivateUser(): Response
    {
        $this->requirePermission('administrateUsers');
        $this->requirePostRequest();

        $userId = Craft::$app->getRequest()->getRequiredBodyParam('userId');
        $user = Craft::$app->getUsers()->getUserById($userId);

        if (!$user) {
            $this->_noUserExists();
        }

        if (Craft::$app->getUsers()->activateUser($user)) {
            Craft::$app->getSession()->setNotice(Craft::t('app', 'Successfully activated the user.'));
        } else {
            Craft::$app->getSession()->setError(Craft::t('app', 'There was a problem activating the user.'));
        }

        return $this->redirectToPostedUrl();
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
    public function actionEditUser($userId = null, User $user = null, array $errors = null): Response
    {
        if (!empty($errors)) {
            Craft::$app->getSession()->setError(reset($errors));
        }

        // Determine which user account we're editing
        // ---------------------------------------------------------------------

        $edition = Craft::$app->getEdition();
        $userSession = Craft::$app->getUser();

        if ($user === null) {
            // Are we editing a specific user account?
            if ($userId !== null) {
                $user = User::find()
                    ->addSelect(['users.password', 'users.passwordResetRequired'])
                    ->id($userId === 'current' ? $userSession->getId() : $userId)
                    ->anyStatus()
                    ->one();
            } else if ($edition === Craft::Pro) {
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

        $currentUser = $userSession->getIdentity();

        // Determine which actions should be available
        // ---------------------------------------------------------------------

        $statusLabel = null;
        $statusActions = [];
        $sessionActions = [];
        $destructiveActions = [];
        $miscActions = [];

        if ($edition === Craft::Pro && !$isNewUser) {
            switch ($user->getStatus()) {
                case User::STATUS_PENDING:
                    $statusLabel = Craft::t('app', 'Pending');
                    $statusActions[] = [
                        'action' => 'users/send-activation-email',
                        'label' => Craft::t('app', 'Send activation email')
                    ];
                    if ($userSession->checkPermission('administrateUsers')) {
                        // Only need to show the "Copy activation URL" option if they don't have a password
                        if (!$user->password) {
                            $statusActions[] = [
                                'id' => 'copy-passwordreset-url',
                                'label' => Craft::t('app', 'Copy activation URL')
                            ];
                        }
                        $statusActions[] = [
                            'action' => 'users/activate-user',
                            'label' => Craft::t('app', 'Activate account')
                        ];
                    }
                    break;
                case User::STATUS_SUSPENDED:
                    $statusLabel = Craft::t('app', 'Suspended');
                    if ($userSession->checkPermission('moderateUsers')) {
                        $statusActions[] = [
                            'action' => 'users/unsuspend-user',
                            'label' => Craft::t('app', 'Unsuspend')
                        ];
                    }
                    break;
                case User::STATUS_ACTIVE:
                    if ($user->locked) {
                        $statusLabel = Craft::t('app', 'Locked');
                        if (
                            !$isCurrentUser &&
                            ($currentUser->admin || !$user->admin) &&
                            $userSession->checkPermission('moderateUsers') &&
                            (
                                ($previousUserId = Craft::$app->getSession()->get(User::IMPERSONATE_KEY)) === null ||
                                $user->id != $previousUserId
                            )
                        ) {
                            $statusActions[] = [
                                'action' => 'users/unlock-user',
                                'label' => Craft::t('app', 'Unlock')
                            ];
                        }
                    } else {
                        $statusLabel = Craft::t('app', 'Active');
                    }

                    if (!$isCurrentUser) {
                        $statusActions[] = [
                            'action' => 'users/send-password-reset-email',
                            'label' => Craft::t('app', 'Send password reset email')
                        ];
                        if ($userSession->checkPermission('administrateUsers')) {
                            $statusActions[] = [
                                'id' => 'copy-passwordreset-url',
                                'label' => Craft::t('app', 'Copy password reset URL')
                            ];
                        }
                    }
                    break;
            }

            if (!$isCurrentUser) {
                if (Craft::$app->getUsers()->canImpersonate($currentUser, $user)) {
                    $sessionActions[] = [
                        'action' => 'users/impersonate',
                        'label' => Craft::t('app', 'Login as {user}', ['user' => $user->getName()])
                    ];
                }

                if ($userSession->checkPermission('moderateUsers') && $user->getStatus() != User::STATUS_SUSPENDED) {
                    $destructiveActions[] = [
                        'action' => 'users/suspend-user',
                        'label' => Craft::t('app', 'Suspend')
                    ];
                }
            }

            if ($isCurrentUser || $userSession->checkPermission('deleteUsers')) {
                // Even if they have delete user permissions, we don't want a non-admin
                // to be able to delete an admin.
                if (($currentUser && $currentUser->admin) || !$user->admin) {
                    $destructiveActions[] = [
                        'id' => 'delete-btn',
                        'label' => Craft::t('app', 'Delete…')
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
            $event->destructiveActions,
        ]);

        // Set the appropriate page title
        // ---------------------------------------------------------------------

        if (!$isNewUser) {
            if ($isCurrentUser) {
                $title = Craft::t('app', 'My Account');
            } else if ($name = trim($user->getName())) {
                $title = Craft::t('app', '{user}’s Account', ['user' => $name]);
            } else {
                $title = Craft::t('app', 'Edit User');
            }
        } else {
            $title = Craft::t('app', 'Register a new user');
        }

        $selectedTab = 'account';

        $tabs = [
            'account' => [
                'label' => Craft::t('app', 'Account'),
                'url' => '#account',
            ]
        ];

        foreach ($user->getFieldLayout()->getTabs() as $index => $tab) {
            // Skip if the tab doesn't have any fields
            if (empty($tab->getFields())) {
                continue;
            }

            // Do any of the fields on this tab have errors?
            $hasErrors = false;

            if ($user->hasErrors()) {
                foreach ($tab->getFields() as $field) {
                    /** @var Field $field */
                    if ($hasErrors = $user->hasErrors($field->handle . '.*')) {
                        break;
                    }
                }
            }

            $tabs['profile' . $index] = [
                'label' => Craft::t('site', $tab->name),
                'url' => '#profile-' . $tab->getHtmlId(),
                'class' => $hasErrors ? 'error' : null
            ];
        }

        // Show the permission tab for the users that can change them on Craft Pro editions
        if (
            $edition === Craft::Pro &&
            (
                $userSession->checkPermission('assignUserPermissions') ||
                $userSession->checkPermission('assignUserGroups')
            )
        ) {
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
                    'firstName',
                    'lastName',
                    'email',
                    'password',
                    'newPassword',
                    'currentPassword',
                    'passwordResetRequired',
                    'preferredLanguage'
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

        // Load the resources and render the page
        // ---------------------------------------------------------------------

        // Body class
        $bodyClass = 'edit-user';

        $this->getView()->registerAssetBundle(EditUserAsset::class);

        $userIdJs = Json::encode($user->id);
        $isCurrentJs = ($isCurrentUser ? 'true' : 'false');
        $settingsJs = Json::encode([
            'deleteModalRedirect' => Craft::$app->getSecurity()->hashData(Craft::$app->getEdition() === Craft::Pro ? 'users' : 'dashboard'),
        ]);
        $this->getView()->registerJs('new Craft.AccountSettingsForm(' . $userIdJs . ', ' . $isCurrentJs . ', ' . $settingsJs . ');', View::POS_END);

        return $this->renderTemplate('users/_edit', compact(
            'user',
            'isNewUser',
            'statusLabel',
            'actions',
            'bodyClass',
            'title',
            'tabs',
            'selectedTab'
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
    public function actionSaveUser()
    {
        $this->requirePostRequest();

        $request = Craft::$app->getRequest();
        $userSession = Craft::$app->getUser();
        $currentUser = $userSession->getIdentity();
        $canAdministrateUsers = $currentUser && $currentUser->can('administrateUsers');
        $generalConfig = Craft::$app->getConfig()->getGeneral();
        $userSettings = Craft::$app->getProjectConfig()->get('users') ?? [];
        $requireEmailVerification = $userSettings['requireEmailVerification'] ?? true;

        // Get the user being edited
        // ---------------------------------------------------------------------

        $userId = $request->getBodyParam('userId');
        $isNewUser = !$userId;
        $isPublicRegistration = false;

        // Are we editing an existing user?
        if ($userId) {
            $user = User::find()
                ->id($userId)
                ->anyStatus()
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
            }

            $user = new User();
        }

        $isCurrentUser = $user->getIsCurrent();

        if ($isCurrentUser) {
            // Remember the old username in case it changes
            $oldUsername = $user->username;
        }

        // Handle secure properties (email and password)
        // ---------------------------------------------------------------------

        $sendVerificationEmail = false;

        // Are they allowed to set the email address?
        if ($isNewUser || $isCurrentUser || $canAdministrateUsers) {
            $newEmail = $request->getBodyParam('email');

            // Make sure it actually changed
            if ($newEmail && $newEmail === $user->email) {
                $newEmail = null;
            }

            if ($newEmail) {
                // Should we be sending a verification email now?
                $sendVerificationEmail = (
                    $requireEmailVerification && (
                        $isPublicRegistration ||
                        ($isCurrentUser && !$canAdministrateUsers) ||
                        $request->getBodyParam('sendVerificationEmail')
                    )
                );

                if ($sendVerificationEmail) {
                    $user->unverifiedEmail = $newEmail;
                }

                if (!$sendVerificationEmail || $isNewUser) {
                    $user->email = $newEmail;
                }
            }
        }

        // Are they allowed to set a new password?
        if ($isPublicRegistration) {
            if (!$generalConfig->deferPublicRegistrationPassword) {
                $user->newPassword = $request->getBodyParam('password', '');
            }
        } else {
            if ($isCurrentUser) {
                // If there was a newPassword input but it was empty, pretend it didn't exist
                $user->newPassword = $request->getBodyParam('newPassword') ?: null;
            }
        }

        // If editing an existing user and either of these properties are being changed,
        // require the user's current password for additional security
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
            $user->username = $request->getBodyParam('username', ($user->username ?: $user->email));
        }

        $user->firstName = $request->getBodyParam('firstName', $user->firstName);
        $user->lastName = $request->getBodyParam('lastName', $user->lastName);

        // New users should always be initially saved in a pending state,
        // even if an admin is doing this and opted to not send the verification email
        if ($isNewUser) {
            $user->pending = true;
        }

        // There are some things only admins can change
        if ($currentUser && $currentUser->admin) {
            $user->passwordResetRequired = (bool)$request->getBodyParam('passwordResetRequired', $user->passwordResetRequired);

            // Is their admin status changing?
            if (($adminParam = $request->getBodyParam('admin', $user->admin)) != $user->admin) {
                // Making someone an admin requires an elevated session
                if ($adminParam) {
                    $this->requireElevatedSession();
                }

                $user->admin = (bool)$adminParam;
            }
        }

        // If this is Craft Pro, grab any profile content from post
        $user->setFieldValuesFromRequest('fields');

        // Validate and save!
        // ---------------------------------------------------------------------

        $photo = UploadedFile::getInstanceByName('photo');

        if ($photo && !Image::canManipulateAsImage($photo->getExtension())) {
            $user->addError('photo', Craft::t('app', 'The user photo provided is not an image.'));
        }

        // Don't validate required custom fields if it's public registration
        if (!$isPublicRegistration) {
            $user->setScenario(Element::SCENARIO_LIVE);
        }

        // Manually validate the user so we can pass $clearErrors=false
        if (
            !$user->validate(null, false) ||
            !Craft::$app->getElements()->saveElement($user, false)
        ) {
            Craft::info('User not saved due to validation error.', __METHOD__);

            if ($isPublicRegistration) {
                // Move any 'newPassword' errors over to 'password'
                $user->addErrors(['password' => $user->getErrors('newPassword')]);
                $user->clearErrors('newPassword');
            }

            // Copy any 'unverifiedEmail' errors to 'email'
            // todo: clear out the 'unverifiedEmail' errors in Craft 4
            if (!$user->hasErrors('email')) {
                $user->addErrors(['email' => $user->getErrors('unverifiedEmail')]);
            }

            if ($request->getAcceptsJson()) {
                return $this->asJson([
                    'errors' => $user->getErrors(),
                ]);
            }

            Craft::$app->getSession()->setError(Craft::t('app', 'Couldn’t save user.'));

            // Send the account back to the template
            Craft::$app->getUrlManager()->setRouteParams([
                'user' => $user
            ]);

            return null;
        }

        // If this is a new user and email verification isn't required,
        // go ahead and activate them now.
        if ($isNewUser && !$requireEmailVerification) {
            Craft::$app->getUsers()->activateUser($user);
        }

        // Save their preferences too
        $preferences = [
            'language' => $request->getBodyParam('preferredLanguage', $user->getPreference('language')),
            'weekStartDay' => $request->getBodyParam('weekStartDay', $user->getPreference('weekStartDay')),
        ];

        if ($user->admin) {
            $preferences = array_merge($preferences, [
                'enableDebugToolbarForSite' => (bool)$request->getBodyParam('enableDebugToolbarForSite', $user->getPreference('enableDebugToolbarForSite')),
                'enableDebugToolbarForCp' => (bool)$request->getBodyParam('enableDebugToolbarForCp', $user->getPreference('enableDebugToolbarForCp')),
                'showExceptionView' => (bool)$request->getBodyParam('showExceptionView', $user->getPreference('showExceptionView')),
                'profileTemplates' => (bool)$request->getBodyParam('profileTemplates', $user->getPreference('profileTemplates')),
            ]);
        }

        Craft::$app->getUsers()->saveUserPreferences($user, $preferences);

        // Is this the current user?
        if ($user->getIsCurrent()) {
            // Make sure these preferences make it to the main identity user
            if ($user !== $currentUser) {
                $currentUser->mergePreferences($preferences);
            }

            $userSession->saveDebugPreferencesToSession();
        }

        // Is this the current user, and did their username just change?
        if ($isCurrentUser && $user->username !== $oldUsername) {
            // Update the username cookie
            $userSession->sendUsernameCookie($user);
        }

        // Save the user's photo, if it was submitted
        $this->_processUserPhoto($user);

        // If this is public registration, assign the user to the default user group
        if ($isPublicRegistration) {
            // Assign them to the default user group
            Craft::$app->getUsers()->assignUserToDefaultGroup($user);
        } else {
            // Assign user groups and permissions if the current user is allowed to do that
            $this->_processUserGroupsPermissions($user);
        }

        // Do we need to send a verification email out?
        if ($sendVerificationEmail) {
            // Temporarily set the unverified email on the User so the verification email goes to the
            // right place
            $originalEmail = $user->email;
            $user->email = $user->unverifiedEmail;

            if ($isNewUser) {
                // Send the activation email
                $emailSent = Craft::$app->getUsers()->sendActivationEmail($user);
            } else {
                // Send the standard verification email
                $emailSent = Craft::$app->getUsers()->sendNewEmailVerifyEmail($user);
            }

            if (!$emailSent) {
                Craft::$app->getSession()->setError(Craft::t('app', 'User saved, but couldn’t send verification email. Check your email settings.'));
            }

            // Put the original email back into place
            $user->email = $originalEmail;
        }

        // Is this public registration, and was the user going to be activated automatically?
        $publicActivation = $isPublicRegistration && $user->getStatus() === User::STATUS_ACTIVE;

        if ($publicActivation) {
            // Maybe automatically log them in
            $this->_maybeLoginUserAfterAccountActivation($user);
        }

        if ($request->getAcceptsJson()) {
            return $this->asJson([
                'success' => true,
                'id' => $user->id
            ]);
        }

        if ($isPublicRegistration) {
            $message = $request->getParam('userRegisteredNotice') ?? Craft::t('app', 'User registered.');
            Craft::$app->getSession()->setNotice($message);
        } else {
            Craft::$app->getSession()->setNotice(Craft::t('app', 'User saved.'));
        }

        // Is this public registration, and is the user going to be activated automatically?
        if ($publicActivation) {
            return $this->_redirectUserToCp($user) ?? $this->_redirectUserAfterAccountActivation($user);
        }

        return $this->redirectToPostedUrl($user);
    }

    /**
     * Upload a user photo.
     *
     * @return Response|null
     * @throws BadRequestHttpException if the uploaded file is not an image
     */
    public function actionUploadUserPhoto()
    {
        $this->requireAcceptsJson();
        $this->requireLogin();

        $userId = Craft::$app->getRequest()->getRequiredBodyParam('userId');

        if ($userId != Craft::$app->getUser()->getIdentity()->id) {
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
            ]);
        } catch (\Throwable $exception) {
            /** @noinspection UnSafeIsSetOverArrayInspection - FP */
            if (isset($fileLocation)) {
                try {
                    FileHelper::unlink($fileLocation);
                } catch (\Throwable $e) {
                    // Let it go
                }
            }

            Craft::error('There was an error uploading the photo: ' . $exception->getMessage(), __METHOD__);

            return $this->asErrorJson(Craft::t('app', 'There was an error uploading your photo: {error}', [
                'error' => $exception->getMessage()
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
        $this->requireLogin();

        $userId = Craft::$app->getRequest()->getRequiredBodyParam('userId');

        if ($userId != Craft::$app->getUser()->getIdentity()->id) {
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
     * @return Response
     * @throws BadRequestHttpException if the user is not pending
     */
    public function actionSendActivationEmail(): Response
    {
        $this->requirePostRequest();

        $request = Craft::$app->getRequest();
        $userId = $request->getRequiredBodyParam('userId');

        $user = User::find()
            ->id($userId)
            ->anyStatus()
            ->addSelect(['users.password'])
            ->one();

        if (!$user) {
            $this->_noUserExists();
        }

        // Only allow activation emails to be send to pending users.
        /** @var User $user */
        if ($user->getStatus() !== User::STATUS_PENDING) {
            throw new BadRequestHttpException('Activation emails can only be sent to pending users');
        }

        $emailSent = Craft::$app->getUsers()->sendActivationEmail($user);

        if ($request->getAcceptsJson()) {
            return $this->asJson(['success' => $emailSent]);
        }

        if ($emailSent) {
            Craft::$app->getSession()->setNotice(Craft::t('app', 'Activation email sent.'));
        } else {
            Craft::$app->getSession()->setError(Craft::t('app', 'Couldn’t send activation email. Check your email settings.'));
        }

        return $this->redirectToPostedUrl();
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
        $this->requireLogin();
        $this->requirePermission('moderateUsers');

        $userId = Craft::$app->getRequest()->getRequiredBodyParam('userId');
        $user = Craft::$app->getUsers()->getUserById($userId);

        if (!$user) {
            $this->_noUserExists();
        }

        // Even if you have moderateUsers permissions, only and admin should be able to unlock another admin.
        if ($user->admin) {
            $currentUser = Craft::$app->getUser()->getIdentity();
            if (!$currentUser->admin) {
                throw new ForbiddenHttpException('Only admins can unlock other admins.');
            }

            // And admins can't unlock themselves by impersonating another admin
            $previousUserId = Craft::$app->getSession()->get(User::IMPERSONATE_KEY);
            if ($previousUserId && $user->id == $previousUserId) {
                throw new ForbiddenHttpException('You can’t unlock yourself via impersonation.');
            }
        }

        Craft::$app->getUsers()->unlockUser($user);

        Craft::$app->getSession()->setNotice(Craft::t('app', 'User activated.'));

        return $this->redirectToPostedUrl();
    }

    /**
     * Suspends a user.
     *
     * @return Response|null
     * @throws ForbiddenHttpException if a non-admin is attempting to suspend an admin
     */
    public function actionSuspendUser()
    {
        $this->requirePostRequest();
        $this->requireLogin();
        $this->requirePermission('moderateUsers');

        $userId = Craft::$app->getRequest()->getRequiredBodyParam('userId');
        $user = Craft::$app->getUsers()->getUserById($userId);

        if (!$user) {
            $this->_noUserExists();
        }

        // Even if you have moderateUsers permissions, only and admin should be able to suspend another admin.
        $currentUser = Craft::$app->getUser()->getIdentity();

        if ($user->admin && !$currentUser->admin) {
            throw new ForbiddenHttpException('Only admins can suspend other admins');
        }

        if (!Craft::$app->getUsers()->suspendUser($user)) {
            Craft::$app->getSession()->setError(Craft::t('app', 'Couldn’t suspend user.'));

            return null;
        }

        Craft::$app->getSession()->setNotice(Craft::t('app', 'User suspended.'));

        return $this->redirectToPostedUrl();
    }

    /**
     * Returns a summary of the content that is owned by a given user ID(s).
     *
     * @return Response|null
     * @since 3.0.13
     */
    public function actionUserContentSummary(): Response
    {
        $this->requirePostRequest();
        $this->requireLogin();

        $userIds = Craft::$app->getRequest()->getRequiredBodyParam('userId');

        if ($userIds !== (string)Craft::$app->getUser()->getIdentity()->id) {
            $this->requirePermission('deleteUsers');
        }

        $summary = [];

        $entryCount = Entry::find()
            ->authorId($userIds)
            ->siteId('*')
            ->unique()
            ->anyStatus()
            ->count();

        if ($entryCount) {
            $summary[] = $entryCount == 1 ? Craft::t('app', '1 entry') : Craft::t('app', '{num} entries', ['num' => $entryCount]);
        }

        // Fire a 'defineUserContentSummary' event
        $event = new DefineUserContentSummaryEvent([
            'contentSummary' => $summary,
        ]);
        $this->trigger(self::EVENT_DEFINE_CONTENT_SUMMARY, $event);

        return $this->asJson($event->contentSummary);
    }

    /**
     * Deletes a user.
     *
     * @return Response|null
     */
    public function actionDeleteUser()
    {
        $this->requirePostRequest();
        $this->requireLogin();

        $request = Craft::$app->getRequest();
        $userId = $request->getRequiredBodyParam('userId');
        $user = Craft::$app->getUsers()->getUserById($userId);

        if (!$user) {
            $this->_noUserExists();
        }

        if (!$user->getIsCurrent()) {
            $this->requirePermission('deleteUsers');

            // Even if you have deleteUser permissions, only and admin should be able to delete another admin.
            if ($user->admin) {
                $this->requireAdmin();
            }
        }

        // Are we transferring the user's content to a different user?
        $transferContentToId = $request->getBodyParam('transferContentTo');

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
            Craft::$app->getSession()->setError(Craft::t('app', 'Couldn’t delete the user.'));

            return null;
        }

        Craft::$app->getSession()->setNotice(Craft::t('app', 'User deleted.'));

        return $this->redirectToPostedUrl();
    }

    /**
     * Unsuspends a user.
     *
     * @return Response|null
     * @throws ForbiddenHttpException if a non-admin is attempting to unsuspend an admin
     */
    public function actionUnsuspendUser()
    {
        $this->requirePostRequest();
        $this->requireLogin();
        $this->requirePermission('moderateUsers');

        $userId = Craft::$app->getRequest()->getRequiredBodyParam('userId');
        $user = Craft::$app->getUsers()->getUserById($userId);

        if (!$user) {
            $this->_noUserExists();
        }

        // Even if you have moderateUsers permissions, only and admin should be able to unsuspend another admin.
        $currentUser = Craft::$app->getUser()->getIdentity();

        if ($user->admin && !$currentUser->admin) {
            throw new ForbiddenHttpException('Only admins can unsuspend other admins');
        }

        if (!Craft::$app->getUsers()->unsuspendUser($user)) {
            Craft::$app->getSession()->setError(Craft::t('app', 'Couldn’t unsuspend user.'));

            return null;
        }

        Craft::$app->getSession()->setNotice(Craft::t('app', 'User unsuspended.'));

        return $this->redirectToPostedUrl();
    }

    /**
     * Saves the user field layout.
     *
     * @return Response|null
     */
    public function actionSaveFieldLayout()
    {
        $this->requirePostRequest();
        $this->requireAdmin();

        // Set the field layout
        $fieldLayout = Craft::$app->getFields()->assembleLayoutFromPost();
        $fieldLayout->type = User::class;

        if (!Craft::$app->getUsers()->saveLayout($fieldLayout)) {
            Craft::$app->getSession()->setError(Craft::t('app', 'Couldn’t save user fields.'));

            return null;
        }

        Craft::$app->getSession()->setNotice(Craft::t('app', 'User fields saved.'));

        return $this->redirectToPostedUrl();
    }

    /**
     * Verifies a password for a user.
     *
     * @return Response
     */
    public function actionVerifyPassword(): Response
    {
        $this->requireAcceptsJson();

        if ($this->_verifyExistingPassword()) {
            return $this->asJson(['success' => true]);
        }

        return $this->asErrorJson(Craft::t('app', 'Invalid password.'));
    }

    /**
     * Handles a failed login attempt.
     *
     * @param string|null $authError
     * @param User|null $user
     * @return Response|null
     * @throws ServiceUnavailableHttpException
     */
    private function _handleLoginFailure(string $authError = null, User $user = null)
    {
        $message = UserHelper::getLoginFailureMessage($authError, $user);

        // Fire a 'loginFailure' event
        $event = new LoginFailureEvent([
            'authError' => $authError,
            'message' => $message,
            'user' => $user,
        ]);
        $this->trigger(self::EVENT_LOGIN_FAILURE, $event);

        $request = Craft::$app->getRequest();
        if ($request->getAcceptsJson()) {
            return $this->asJson([
                'errorCode' => $authError,
                'error' => $event->message
            ]);
        }

        Craft::$app->getSession()->setError($event->message);

        Craft::$app->getUrlManager()->setRouteParams([
            'loginName' => $request->getBodyParam('loginName'),
            'rememberMe' => (bool)$request->getBodyParam('rememberMe'),
            'errorCode' => $authError,
            'errorMessage' => $event->message,
        ]);

        return null;
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
        $request = Craft::$app->getRequest();
        if ($request->getAcceptsJson()) {
            $return = [
                'success' => true,
                'returnUrl' => $returnUrl
            ];

            if (Craft::$app->getConfig()->getGeneral()->enableCsrfProtection) {
                $return['csrfTokenValue'] = $request->getCsrfToken();
            }

            return $this->asJson($return);
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
        $view = $this->getView();

        // If this is a site request, see if a custom Set Password template exists
        if (Craft::$app->getRequest()->getIsSiteRequest()) {
            $templatePath = Craft::$app->getConfig()->getGeneral()->getSetPasswordPath();
            if ($view->doesTemplateExist($templatePath)) {
                return $this->renderTemplate($templatePath, $variables);
            }
        }

        // Otherwise go with the CP's template
        return $this->renderTemplate('setpassword', $variables, View::TEMPLATE_MODE_CP);
    }

    /**
     * Throws a "no user exists" exception
     *
     * @throws NotFoundHttpException
     */
    private function _noUserExists()
    {
        throw new NotFoundHttpException('User not found');
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
     * Verifies that the current user's password was submitted with the request.
     *
     * @return bool
     */
    private function _verifyExistingPassword(): bool
    {
        $currentUser = Craft::$app->getUser()->getIdentity();

        if (!$currentUser) {
            return false;
        }

        $request = Craft::$app->getRequest();
        $currentPassword = $request->getParam('currentPassword') ?? $request->getParam('password');
        if ($currentPassword === null) {
            return false;
        }

        $currentHashedPassword = $currentUser->password;

        try {
            return Craft::$app->getSecurity()->validatePassword($currentPassword, $currentHashedPassword);
        } catch (InvalidArgumentException $e) {
            return false;
        }
    }

    /**
     * @param User $user
     */
    private function _processUserPhoto(User $user)
    {
        // Delete their photo?
        $users = Craft::$app->getUsers();

        if (Craft::$app->getRequest()->getBodyParam('deletePhoto')) {
            $users->deleteUserPhoto($user);
            $user->photoId = null;
            Craft::$app->getElements()->saveElement($user);
        }

        // Did they upload a new one?
        if ($photo = UploadedFile::getInstanceByName('photo')) {
            $fileLocation = Assets::tempFilePath($photo->getExtension());
            move_uploaded_file($photo->tempName, $fileLocation);
            try {
                $users->saveUserPhoto($fileLocation, $user, $photo->name);
            } catch (\Throwable $e) {
                if (file_exists($fileLocation)) {
                    FileHelper::unlink($fileLocation);
                }
                throw $e;
            }
        }
    }

    /**
     * @param User $user
     * @throws ForbiddenHttpException if the user account doesn't have permission to assign the attempted permissions/groups
     */
    private function _processUserGroupsPermissions(User $user)
    {
        $userSession = Craft::$app->getUser();
        if (($currentUser = $userSession->getIdentity()) === null) {
            return;
        }

        $request = Craft::$app->getRequest();
        $edition = Craft::$app->getEdition();

        if ($edition === Craft::Pro && $currentUser->can('assignUserPermissions')) {
            // Save any user permissions
            if ($user->admin) {
                $permissions = [];
            } else {
                $permissions = $request->getBodyParam('permissions');

                // it will be an empty string if no permissions were assigned during user saving.
                if ($permissions === '') {
                    $permissions = [];
                }
            }

            if (is_array($permissions)) {
                // See if there are any new permissions in here
                $hasNewPermissions = false;

                foreach ($permissions as $permission) {
                    if (!$user->can($permission)) {
                        $hasNewPermissions = true;

                        // Make sure the current user even has permission to grant it
                        if (!$userSession->checkPermission($permission)) {
                            throw new ForbiddenHttpException("Your account doesn't have permission to assign the {$permission} permission to a user.");
                        }
                    }
                }

                if ($hasNewPermissions) {
                    $this->requireElevatedSession();
                }

                Craft::$app->getUserPermissions()->saveUserPermissions($user->id, $permissions);
            }
        }

        // Only Craft Pro has user groups
        if ($edition === Craft::Pro && $currentUser->can('assignUserGroups')) {
            // Save any user groups
            $groupIds = $request->getBodyParam('groups');

            if ($groupIds !== null) {
                if ($groupIds === '') {
                    $groupIds = [];
                }

                $groupUidMap = [];
                $allGroups = Craft::$app->getUserGroups()->getAllGroups();
                foreach ($allGroups as $group) {
                    $groupUidMap[$group->id] = $group->uid;
                }

                // See if there are any new groups in here
                $oldGroupIds = [];

                foreach ($user->getGroups() as $group) {
                    $oldGroupIds[] = $group->id;
                }

                $hasNewGroups = false;

                foreach ($groupIds as $groupId) {
                    if (!in_array($groupId, $oldGroupIds, false)) {
                        $hasNewGroups = true;

                        // Make sure the current user is in the group or has permission to assign it
                        if (
                            !$currentUser->isInGroup($groupId) &&
                            !$currentUser->can('assignUserGroup:' . $groupUidMap[$groupId])
                        ) {
                            throw new ForbiddenHttpException("Your account doesn't have permission to assign user group {$groupId} to a user.");
                        }
                    }
                }

                if ($hasNewGroups) {
                    $this->requireElevatedSession();
                }

                Craft::$app->getUsers()->assignUserToGroups($user->id, $groupIds);
            }
        }
    }

    /**
     * @return array|Response
     */
    private function _processTokenRequest()
    {
        $request = Craft::$app->getRequest();
        $uid = $request->getRequiredParam('id');
        $code = $request->getRequiredParam('code');

        /** @var User|null $user */
        $user = User::find()
            ->uid($uid)
            ->anyStatus()
            ->addSelect(['users.password'])
            ->one();

        if (!$user) {
            return $this->_processInvalidToken();
        }

        // If someone is logged in and it's not this person, log them out
        $userSession = Craft::$app->getUser();
        if (!$userSession->getIsGuest() && $userSession->getId() != $user->id) {
            $userSession->logout();
        }

        // Fire a 'beforeVerifyUser' event
        $usersService = Craft::$app->getUsers();
        if ($usersService->hasEventHandlers(Users::EVENT_BEFORE_VERIFY_EMAIL)) {
            $usersService->trigger(Users::EVENT_BEFORE_VERIFY_EMAIL, new UserEvent([
                'user' => $user
            ]));
        }

        if (!Craft::$app->getUsers()->isVerificationCodeValidForUser($user, $code)) {
            return $this->_processInvalidToken();
        }

        // Fire an 'afterVerifyUser' event
        if ($usersService->hasEventHandlers(Users::EVENT_AFTER_VERIFY_EMAIL)) {
            $usersService->trigger(Users::EVENT_AFTER_VERIFY_EMAIL, new UserEvent([
                'user' => $user
            ]));
        }

        return [$user, $uid, $code];
    }

    /**
     * @return Response
     * @throws HttpException if the verification code is invalid
     */
    private function _processInvalidToken(): Response
    {
        // If they're already logged-in, just send them to the post-login URL
        $userSession = Craft::$app->getUser();
        if (!$userSession->getIsGuest()) {
            $returnUrl = $userSession->getReturnUrl();
            $userSession->removeReturnUrl();
            return $this->redirect($returnUrl);
        }

        // If the invalidUserTokenPath config setting is set, send them there
        if (Craft::$app->getRequest()->getIsSiteRequest()) {
            $url = Craft::$app->getConfig()->getGeneral()->getInvalidUserTokenPath();
            return $this->redirect(UrlHelper::siteUrl($url));
        }

        throw new HttpException('200', Craft::t('app', 'Invalid verification code. Please login or reset your password.'));
    }

    /**
     * Takes over after a user has been activated.
     *
     * @param User $user The user that was just activated
     * @return Response|null
     */
    private function _onAfterActivateUser(User $user)
    {
        $this->_maybeLoginUserAfterAccountActivation($user);

        if (!Craft::$app->getRequest()->getAcceptsJson()) {
            return $this->_redirectUserToCp($user) ?? $this->_redirectUserAfterAccountActivation($user);
        }

        return null;
    }

    /**
     * Possibly log a user in right after they were activated or reset their password, if Craft is configured to do so.
     *
     * @param User $user The user that was just activated or reset their password
     */
    private function _maybeLoginUserAfterAccountActivation(User $user)
    {
        $generalConfig = Craft::$app->getConfig()->getGeneral();
        if ($generalConfig->autoLoginAfterAccountActivation === true) {
            Craft::$app->getUser()->login($user, $generalConfig->userSessionDuration);
        }
    }

    /**
     * Redirects a user to the `postCpLoginRedirect` location, if they have access to the control panel.
     *
     * @param User $user The user to redirect
     * @return Response|null
     */
    private function _redirectUserToCp(User $user)
    {
        // Can they access the CP?
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
    private function _handleSendPasswordResetError(array $errors, string $loginName = null)
    {
        if (Craft::$app->getRequest()->getAcceptsJson()) {
            /** @noinspection CallableParameterUseCaseInTypeContextInspection */
            $errors = implode(', ', $errors);

            return $this->asErrorJson($errors);
        }

        // Send the data back to the template
        Craft::$app->getUrlManager()->setRouteParams([
            'errors' => $errors,
            'loginName' => $loginName,
        ]);

        return null;
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

        return $view->renderTemplate('users/_photo', [
            'user' => $user
        ], $templateMode);
    }
}
