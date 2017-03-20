<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\controllers;

use Craft;
use craft\elements\Asset;
use craft\elements\User;
use craft\errors\UploadFailedException;
use craft\events\LoginFailureEvent;
use craft\events\RegisterUserActionsEvent;
use craft\events\UserTokenEvent;
use craft\helpers\Assets;
use craft\helpers\DateTimeHelper;
use craft\helpers\FileHelper;
use craft\helpers\Image;
use craft\helpers\Json;
use craft\helpers\UrlHelper;
use craft\services\Users;
use craft\web\assets\edituser\EditUserAsset;
use craft\web\Controller;
use craft\web\UploadedFile;
use craft\web\View;
use Exception;
use yii\web\BadRequestHttpException;
use yii\web\ForbiddenHttpException;
use yii\web\HttpException;
use yii\web\NotFoundHttpException;
use yii\web\Response;

/** @noinspection ClassOverridesFieldOfSuperClassInspection */

/**
 * The UsersController class is a controller that handles various user account related tasks such as logging-in,
 * impersonating a user, logging out, forgetting passwords, setting passwords, validating accounts, activating
 * accounts, creating users, saving users, processing user avatars, deleting, suspending and un-suspending users.
 *
 * Note that all actions in the controller, except [[actionLogin]], [[actionLogout]], [[actionGetRemainingSessionTime]],
 * [[actionSendPasswordResetEmail]], [[actionSetPassword]], [[actionVerifyEmail]] and [[actionSaveUser]] require an
 * authenticated Craft session via [[Controller::allowAnonymous]].
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class UsersController extends Controller
{
    // Constants
    // =========================================================================

    /**
     * @event LoginFailureEvent The event that is triggered when a failed login attempt was made
     */
    const EVENT_LOGIN_FAILURE = 'loginFailure';

    /**
     * @event RegisterUserActionsEvent The event that is triggered when a user’s available actions are being registered
     */
    const EVENT_REGISTER_USER_ACTIONS = 'registerUserActions';

    // Properties
    // =========================================================================

    /**
     * @inheritdoc
     */
    protected $allowAnonymous = [
        'login',
        'logout',
        'get-remaining-session-time',
        'send-password-reset-email',
        'send-activation-email',
        'save-user',
        'set-password',
        'verify-email'
    ];

    // Public Methods
    // =========================================================================

    /**
     * Displays the login template, and handles login post requests.
     *
     * @return Response|null
     */
    public function actionLogin()
    {
        if (!Craft::$app->getUser()->getIsGuest()) {
            // Too easy.
            return $this->_handleSuccessfulLogin(false);
        }

        if (!Craft::$app->getRequest()->getIsPost()) {
            return null;
        }

        // First, a little house-cleaning for expired, pending users.
        Craft::$app->getUsers()->purgeExpiredPendingUsers();

        $loginName = Craft::$app->getRequest()->getBodyParam('loginName');
        $password = Craft::$app->getRequest()->getBodyParam('password');
        $rememberMe = (bool)Craft::$app->getRequest()->getBodyParam('rememberMe');

        // Does a user exist with that username/email?
        $user = Craft::$app->getUsers()->getUserByUsernameOrEmail($loginName);

        // Delay randomly between 0 and 1.5 seconds.
        usleep(random_int(0, 1500000));

        if (!$user) {
            // Delay again to match $user->authenticate()'s delay
            Craft::$app->getSecurity()->validatePassword($password, '$2y$13$nj9aiBeb7RfEfYP3Cum6Revyu14QelGGxwcnFUKXIrQUitSodEPRi');

            return $this->_handleLoginFailure(User::AUTH_USERNAME_INVALID);
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

        // Log them in
        if (Craft::$app->getUser()->login($user, $duration)) {
            return $this->_handleSuccessfulLogin(true);
        }

        // Unknown error
        return $this->_handleLoginFailure(null, $user);
    }

    /**
     * Logs a user in for impersonation.  Requires you to be an administrator.
     *
     * @return Response|null
     */
    public function actionImpersonate()
    {
        $this->requireLogin();
        $this->requireAdmin();
        $this->requirePostRequest();

        $userService = Craft::$app->getUser();
        $session = Craft::$app->getSession();
        $request = Craft::$app->getRequest();

        $userId = $request->getBodyParam('userId');
        $originalUserId = $userService->getId();

        $session->set(User::IMPERSONATE_KEY, $originalUserId);

        if (!$userService->loginByUserId($userId)) {
            $session->remove(User::IMPERSONATE_KEY);
            $session->setError(Craft::t('app', 'There was a problem impersonating this user.'));
            Craft::error($userService->getIdentity()->username.' tried to impersonate userId: '.$userId.' but something went wrong.', __METHOD__);

            return null;
        }

        $session->setNotice(Craft::t('app', 'Logged in.'));

        return $this->_handleSuccessfulLogin(true);
    }

    /**
     * Returns how many seconds are left in the current user session.
     *
     * @return Response
     */
    public function actionGetRemainingSessionTime(): Response
    {
        $this->requireAcceptsJson();

        $return = ['timeout' => Craft::$app->getUser()->getRemainingSessionTime()];

        if (Craft::$app->getConfig()->getGeneral()->enableCsrfProtection) {
            $return['csrfTokenValue'] = Craft::$app->getRequest()->getCsrfToken();
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
        $password = Craft::$app->getRequest()->getBodyParam('password');
        $success = Craft::$app->getUser()->startElevatedSession($password);

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

        if (Craft::$app->getRequest()->getAcceptsJson()) {
            return $this->asJson([
                'success' => true
            ]);
        } else {
            return $this->redirect('');
        }
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

        $errors = [];
        $existingUser = false;
        $loginName = null;

        // If someone's logged in and they're allowed to edit other users, then see if a userId was submitted
        if (Craft::$app->getUser()->checkPermission('editUsers')) {
            $userId = Craft::$app->getRequest()->getBodyParam('userId');

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
            $loginName = Craft::$app->getRequest()->getBodyParam('loginName');

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

        if (!empty($user) && !Craft::$app->getUsers()->sendPasswordResetEmail($user)) {
            $errors[] = Craft::t('app', 'There was a problem sending the password reset email.');
        }

        // If there haven't been any errors, or there were, and it's not one logged in user editing another
        // // and we want to pretend like there wasn't any errors...
        if (empty($errors) || (count($errors) > 0 && !$existingUser && Craft::$app->getConfig()->getGeneral()->preventUserEnumeration)) {
            if (Craft::$app->getRequest()->getAcceptsJson()) {
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
        $this->requireAdmin();

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
        if (!Craft::$app->getRequest()->getIsPost()) {
            if (!is_array($info = $this->_processTokenRequest())) {
                return $info;
            }

            $userToProcess = $info['userToProcess'];
            $id = $info['id'];
            $code = $info['code'];

            Craft::$app->getUser()->sendUsernameCookie($userToProcess);

            // Send them to the set password template.
            return $this->_renderSetPasswordTemplate($userToProcess, [
                'code' => $code,
                'id' => $id,
                'newUser' => $userToProcess->password ? false : true,
            ]);
        }

        // POST request. They've just set the password.
        $code = Craft::$app->getRequest()->getRequiredBodyParam('code');
        $id = Craft::$app->getRequest()->getRequiredParam('id');
        $userToProcess = Craft::$app->getUsers()->getUserByUid($id);

        // See if we still have a valid token.
        $isCodeValid = Craft::$app->getUsers()->isVerificationCodeValidForUser($userToProcess, $code);

        if (!$userToProcess || !$isCodeValid) {
            return $this->_processInvalidToken($userToProcess);
        }

        $userToProcess->newPassword = Craft::$app->getRequest()->getRequiredBodyParam('newPassword');
        $userToProcess->setScenario(User::SCENARIO_PASSWORD);

        if (Craft::$app->getElements()->saveElement($userToProcess)) {
            if ($userToProcess->getStatus() == User::STATUS_PENDING) {
                // Activate them
                Craft::$app->getUsers()->activateUser($userToProcess);

                // Treat this as an activation request
                if (($response = $this->_onAfterActivateUser($userToProcess)) !== null) {
                    return $response;
                }
            }

            // Can they access the CP?
            if ($userToProcess->can('accessCp')) {
                // Send them to the CP login page
                $url = UrlHelper::cpUrl('login');
            } else {
                // Send them to the 'setPasswordSuccessPath'.
                $setPasswordSuccessPath = Craft::$app->getConfig()->getGeneral()->getSetPasswordSuccessPath();
                $url = UrlHelper::siteUrl($setPasswordSuccessPath);
            }

            return $this->redirect($url);
        }

        Craft::$app->getSession()->setNotice(Craft::t('app',
            'Couldn’t update password.'));

        $errors = $userToProcess->getErrors('newPassword');

        return $this->_renderSetPasswordTemplate($userToProcess, [
            'errors' => $errors,
            'code' => $code,
            'id' => $id,
            'newUser' => $userToProcess->password ? false : true,
        ]);
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

        $userToProcess = $info['userToProcess'];
        $userIsPending = $userToProcess->status == User::STATUS_PENDING;

        Craft::$app->getUsers()->verifyEmailForUser($userToProcess);

        // If they're logged in, give them a success notice
        if (!Craft::$app->getUser()->getIsGuest()) {
            Craft::$app->getSession()->setNotice(Craft::t('app', 'Email verified'));
        }

        if ($userIsPending) {
            // They were just activated, so treat this as an activation request
            if (($response = $this->_onAfterActivateUser($userToProcess)) !== null) {
                return $response;
            }
        }

        // Redirect to the site/CP root
        $url = UrlHelper::url('');

        return $this->redirect($url);
    }

    /**
     * Manually activates a user account.  Only admins have access.
     *
     * @return Response
     */
    public function actionActivateUser(): Response
    {
        $this->requireAdmin();
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
     * @param User|null       $user   The user being edited, if there were any validation errors.
     *
     * @return Response
     * @throws NotFoundHttpException if the requested user cannot be found
     * @throws BadRequestHttpException if there’s a mismatch between|null $userId and|null $user
     */
    public function actionEditUser($userId = null, User $user = null): Response
    {
        // Determine which user account we're editing
        // ---------------------------------------------------------------------

        $edition = Craft::$app->getEdition();
        $isClientAccount = false;

        if ($user === null) {
            // Are we editing a specific user account?
            if ($userId !== null) {
                switch ($userId) {
                    case 'current':
                        if ($user) {
                            /** @var User $user */
                            // Make sure it's actually the current user
                            if (!$user->getIsCurrent()) {
                                throw new BadRequestHttpException('Not the current user');
                            }
                        } else {
                            // Get the current user
                            $user = Craft::$app->getUser()->getIdentity();
                        }
                        break;
                    case 'client':
                        $isClientAccount = true;

                        if ($user) {
                            // Make sure it's the client account
                            /** @var User $user */
                            if (!$user->client) {
                                throw new BadRequestHttpException('Not the client account');
                            }
                        } else {
                            // Get the existing client account, if there is one
                            $user = Craft::$app->getUsers()->getClient();

                            if (!$user) {
                                // Registering the Client
                                $user = new User();
                                $user->client = true;
                            }
                        }
                        break;
                    default:
                        if ($user) {
                            // Make sure they have the right ID
                            /** @var User $user */
                            if ($user->id != $userId) {
                                throw new BadRequestHttpException('Not the right user ID');
                            }
                        } else {
                            // Get the user by its ID
                            $user = Craft::$app->getUsers()->getUserById($userId);

                            if (!$user) {
                                throw new NotFoundHttpException('User not found');
                            }

                            if ($user->client) {
                                $isClientAccount = true;
                            }
                        }
                }
            } else {
                if ($edition === Craft::Pro) {
                    // Registering a new user
                    $user = new User();
                } else {
                    // Nada.
                    throw new NotFoundHttpException('User not found');
                }
            }
        }

        /** @var User $user */

        $isNewAccount = !$user->id;

        // Make sure they have permission to edit this user
        // ---------------------------------------------------------------------

        if (!$user->getIsCurrent()) {
            if ($isNewAccount) {
                $this->requirePermission('registerUsers');
            } else {
                $this->requirePermission('editUsers');
            }
        }

        // Determine which actions should be available
        // ---------------------------------------------------------------------

        $statusActions = [];
        $sessionActions = [];
        $destructiveActions = [];
        $miscActions = [];

        if ($edition >= Craft::Client && !$isNewAccount) {
            switch ($user->getStatus()) {
                case User::STATUS_PENDING:
                    $statusLabel = Craft::t('app', 'Unverified');
                    $statusActions[] = [
                        'action' => 'users/send-activation-email',
                        'label' => Craft::t('app', 'Send activation email')
                    ];
                    if (Craft::$app->getUser()->getIsAdmin()) {
                        $statusActions[] = [
                            'id' => 'copy-passwordreset-url',
                            'label' => Craft::t('app', 'Copy activation URL')
                        ];
                        $statusActions[] = [
                            'action' => 'users/activate-user',
                            'label' => Craft::t('app', 'Activate account')
                        ];
                    }
                    break;
                case User::STATUS_LOCKED:
                    $statusLabel = Craft::t('app', 'Locked');
                    if (Craft::$app->getUser()->checkPermission('administrateUsers')) {
                        $statusActions[] = [
                            'action' => 'users/unlock-user',
                            'label' => Craft::t('app', 'Unlock')
                        ];
                    }
                    break;
                case User::STATUS_SUSPENDED:
                    $statusLabel = Craft::t('app', 'Suspended');
                    if (Craft::$app->getUser()->checkPermission('administrateUsers')) {
                        $statusActions[] = [
                            'action' => 'users/unsuspend-user',
                            'label' => Craft::t('app', 'Unsuspend')
                        ];
                    }
                    break;
                case User::STATUS_ACTIVE:
                    $statusLabel = Craft::t('app', 'Active');
                    if (!$user->getIsCurrent()) {
                        $statusActions[] = [
                            'action' => 'users/send-password-reset-email',
                            'label' => Craft::t('app',
                                'Send password reset email')
                        ];
                        if (Craft::$app->getUser()->getIsAdmin()) {
                            $statusActions[] = [
                                'id' => 'copy-passwordreset-url',
                                'label' => Craft::t('app',
                                    'Copy password reset URL')
                            ];
                        }
                    }
                    break;
            }

            if (!$user->getIsCurrent()) {
                if (Craft::$app->getUser()->getIsAdmin()) {
                    $sessionActions[] = [
                        'action' => 'users/impersonate',
                        'label' => Craft::t('app', 'Login as {user}', ['user' => $user->getName()])
                    ];
                }

                if (Craft::$app->getUser()->checkPermission('administrateUsers') && $user->getStatus() != User::STATUS_SUSPENDED) {
                    $destructiveActions[] = [
                        'action' => 'users/suspend-user',
                        'label' => Craft::t('app', 'Suspend')
                    ];
                }

                if (Craft::$app->getUser()->checkPermission('deleteUsers')) {
                    // Even if they have delete user permissions, we don't want a non-admin
                    // to be able to delete an admin.
                    $currentUser = Craft::$app->getUser()->getIdentity();

                    if (($currentUser && $currentUser->admin) || !$user->admin) {
                        $destructiveActions[] = [
                            'id' => 'delete-btn',
                            'label' => Craft::t('app', 'Delete…')
                        ];
                    }
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

        if (!$isNewAccount) {
            if ($user->getIsCurrent()) {
                $title = Craft::t('app', 'My Account');
            } else {
                $title = Craft::t('app', '{user}’s Account', ['user' => $user->getName()]);
            }
        } else {
            if ($isClientAccount) {
                $title = Craft::t('app', 'Register the client’s account');
            } else {
                $title = Craft::t('app', 'Register a new user');
            }
        }

        $selectedTab = 'account';

        $tabs = [
            'account' => [
                'label' => Craft::t('app', 'Account'),
                'url' => '#account',
            ]
        ];

        // No need to show the Profile tab if it's a new user (can't have an avatar yet) and there's no user fields.
        if (!$isNewAccount || ($edition === Craft::Pro && $user->getFieldLayout()->getFields())) {
            $tabs['profile'] = [
                'label' => Craft::t('app', 'Profile'),
                'url' => '#profile',
            ];
        }

        // Show the permission tab for the users that can change them on Craft Client+ editions (unless
        // you're on Client and you're the admin account. No need to show since we always need an admin on Client)
        if (
            ($edition === Craft::Pro && Craft::$app->getUser()->checkPermission('assignUserPermissions')) ||
            ($edition === Craft::Client && $isClientAccount && Craft::$app->getUser()->getIsAdmin())
        ) {
            $tabs['perms'] = [
                'label' => Craft::t('app', 'Permissions'),
                'url' => '#perms',
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

        $this->getView()->registerAssetBundle(EditUserAsset::class);

        $userIdJs = Json::encode($user->id);
        $isCurrentJs = ($user->getIsCurrent() ? 'true' : 'false');
        $settingsJs = Json::encode([
            'deleteModalRedirect' => Craft::$app->getSecurity()->hashData(Craft::$app->getEdition() === Craft::Pro ? 'users' : 'dashboard'),
        ]);
        $this->getView()->registerJs('new Craft.AccountSettingsForm('.$userIdJs.', '.$isCurrentJs.', '.$settingsJs.');', View::POS_END);

        $this->getView()->registerTranslations('app', [
            'Please enter your current password.',
            'Please enter your password.',
        ]);

        return $this->renderTemplate('users/_edit', [
            'account' => $user,
            'isNewAccount' => $isNewAccount,
            'statusLabel' => $statusLabel ?? null,
            'actions' => $actions,
            'title' => $title,
            'tabs' => $tabs,
            'selectedTab' => $selectedTab
        ]);
    }

    /**
     * Provides an endpoint for saving a user account.
     *
     * This action accounts for the following scenarios:
     *
     * - An admin registering a new user account.
     * - An admin editing an existing user account.
     * - A normal user with user-administration permissions registering a new user account.
     * - A normal user with user-administration permissions editing an existing user account.
     * - A guest registering a new user account ("public registration").
     *
     * This action behaves the same regardless of whether it was requested from the Control Panel or the front-end site.
     *
     * @return Response|null
     * @throws NotFoundHttpException if the requested user cannot be found
     * @throws BadRequestHttpException if attempting to create a client account, and one already exists
     * @throws ForbiddenHttpException if attempting public registration but public registration is not allowed
     */
    public function actionSaveUser()
    {
        $this->requirePostRequest();

        $edition = Craft::$app->getEdition();
        $request = Craft::$app->getRequest();
        $userComponent = Craft::$app->getUser();
        $currentUser = $userComponent->getIdentity();
        $requireEmailVerification = Craft::$app->getSystemSettings()->getSetting('users', 'requireEmailVerification');

        // Get the user being edited
        // ---------------------------------------------------------------------

        $userId = $request->getBodyParam('userId');
        $isNewUser = !$userId;
        $thisIsPublicRegistration = false;

        // Are we editing an existing user?
        if ($userId) {
            $user = User::find()
                ->id($userId)
                ->status(null)
                ->addSelect(['users.password'])
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
            if ($edition === Craft::Client) {
                // Make sure they're logged in
                $this->requireAdmin();

                // Make sure there's no Client user yet
                if (Craft::$app->getUsers()->getClient()) {
                    throw new BadRequestHttpException('A client account already exists');
                }

                $user = new User();
                $user->client = true;
            } else {
                // Make sure this is Craft Pro, since that's required for having multiple user accounts
                Craft::$app->requireEdition(Craft::Pro);

                // Is someone logged in?
                if ($currentUser) {
                    // Make sure they have permission to register users
                    $this->requirePermission('registerUsers');
                } else {
                    // Make sure public registration is allowed
                    if (!Craft::$app->getSystemSettings()->getSetting('users',
                        'allowPublicRegistration')
                    ) {
                        throw new ForbiddenHttpException('Public registration is not allowed');
                    }

                    $thisIsPublicRegistration = true;
                }

                $user = new User();
            }
        }

        $isCurrentUser = $user->getIsCurrent();

        if ($isCurrentUser) {
            // Remember the old username in case it changes
            $oldUsername = $user->username;
        }

        // Handle secure properties (email and password)
        // ---------------------------------------------------------------------

        $verifyNewEmail = false;

        // Are they allowed to set the email address?
        if ($isNewUser || $isCurrentUser || $currentUser->can('changeUserEmails')) {
            $newEmail = $request->getBodyParam('email');

            // Make sure it actually changed
            if ($newEmail && $newEmail === $user->email) {
                $newEmail = null;
            }

            if ($newEmail) {
                // Does that email need to be verified?
                if ($requireEmailVerification && (!$currentUser || !$currentUser->admin || $request->getBodyParam('sendVerificationEmail'))) {
                    // Save it as an unverified email for now
                    $user->unverifiedEmail = $newEmail;
                    $verifyNewEmail = true;

                    if ($isNewUser) {
                        $user->email = $newEmail;
                    }
                } else {
                    // We trust them
                    $user->email = $newEmail;
                }
            }
        }

        // Are they allowed to set a new password?
        if ($thisIsPublicRegistration) {
            if (!Craft::$app->getConfig()->getGeneral()->deferPublicRegistrationPassword) {
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
            Craft::warning('Tried to change the email or password for userId: '.$user->id.', but the current password does not match what the user supplied.', __METHOD__);
            $user->addError('currentPassword', Craft::t('app', 'Incorrect current password.'));
        }

        // Handle the rest of the user properties
        // ---------------------------------------------------------------------

        // Is the site set to use email addresses as usernames?
        if (Craft::$app->getConfig()->getGeneral()->useEmailAsUsername) {
            $user->username = $user->email;
        } else {
            $user->username = $request->getBodyParam('username', ($user->username ?: $user->email));
        }

        $user->firstName = $request->getBodyParam('firstName', $user->firstName);
        $user->lastName = $request->getBodyParam('lastName', $user->lastName);

        // If email verification is required, then new users will be saved in a pending state,
        // even if an admin is doing this and opted to not send the verification email
        if ($isNewUser && $requireEmailVerification) {
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
        if ($edition === Craft::Pro) {
            $user->setFieldValuesFromRequest('fields');
        }

        // Validate and save!
        // ---------------------------------------------------------------------

        $imageValidates = true;
        $photo = UploadedFile::getInstanceByName('photo');

        if ($photo && !Image::isImageManipulatable($photo->getExtension())) {
            $imageValidates = false;
            $user->addError('photo', Craft::t('app', 'The user photo provided is not an image.'));
        }

        if ($thisIsPublicRegistration) {
            $user->validateCustomFields = false;
        }

        if (!$imageValidates || !Craft::$app->getElements()->saveElement($user)) {
            if ($thisIsPublicRegistration) {
                // Move any 'newPassword' errors over to 'password'
                $user->addErrors(['password' => $user->getErrors('newPassword')]);
                $user->clearErrors('newPassword');
            }

            if ($request->getAcceptsJson()) {
                return $this->asErrorJson(Craft::t('app', 'Couldn’t save user.'));
            }

            Craft::$app->getSession()->setError(Craft::t('app', 'Couldn’t save user.'));

            // Send the account back to the template
            Craft::$app->getUrlManager()->setRouteParams([
                'user' => $user
            ]);

            return null;
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
            ]);
        }

        Craft::$app->getUsers()->saveUserPreferences($user, $preferences);

        // Is this the current user?
        if ($user->getIsCurrent()) {
            // Make sure these preferences make it to the main identity user
            if ($user !== $currentUser) {
                $currentUser->mergePreferences($preferences);
            }

            $userComponent->saveDebugPreferencesToSession();
        }

        // Is this the current user, and did their username just change?
        if ($isCurrentUser && $user->username !== $oldUsername) {
            // Update the username cookie
            Craft::$app->getUser()->sendUsernameCookie($user);
        }

        // Save the user's photo, if it was submitted
        $this->_processUserPhoto($user);

        // If this is public registration, assign the user to the default user group
        if ($thisIsPublicRegistration) {
            // Assign them to the default user group
            Craft::$app->getUsers()->assignUserToDefaultGroup($user);
        } else {
            // Assign user groups and permissions if the current user is allowed to do that
            $this->_processUserGroupsPermissions($user);
        }

        // Do we need to send a verification email out?
        if ($verifyNewEmail) {
            // Temporarily set the unverified email on the User so the verification email goes to the
            // right place
            $originalEmail = $user->email;
            $user->email = $user->unverifiedEmail;

            if ($isNewUser) {
                // Send the activation email
                $success = Craft::$app->getUsers()->sendActivationEmail($user);
            } else {
                // Send the standard verification email
                $success = Craft::$app->getUsers()->sendNewEmailVerifyEmail($user);
            }

            if (!$success) {
                Craft::$app->getSession()->setError(Craft::t('app', 'User saved, but couldn’t send verification email. Check your email settings.'));
            }

            // Put the original email back into place
            $user->email = $originalEmail;
        }

        // Is this public registration, and was the user going to be activated automatically?
        $publicActivation = $thisIsPublicRegistration && $user->status == User::STATUS_ACTIVE;

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

        if ($thisIsPublicRegistration) {
            Craft::$app->getSession()->setNotice(Craft::t('app', 'User registered.'));
        } else {
            Craft::$app->getSession()->setNotice(Craft::t('app', 'User saved.'));
        }

        // Is this public registration, and is the user going to be activated automatically?
        if ($publicActivation) {
            return $this->_redirectUserAfterAccountActivation($user);
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

        if ($userId !== Craft::$app->getUser()->getIdentity()->id) {
            $this->requirePermission('editUsers');
        }

        $file = UploadedFile::getInstanceByName('photo');

        try {
            // Make sure a file was uploaded
            if ($file) {
                if ($file->getHasError()) {
                    throw new UploadFailedException($file->error);
                }

                $users = Craft::$app->getUsers();
                $user = $users->getUserById($userId);

                // Move to our own temp location
                $fileLocation = Assets::tempFilePath($file->getExtension());
                move_uploaded_file($file->tempName, $fileLocation);
                $users->saveUserPhoto($fileLocation, $user, $file->name);
                FileHelper::removeFile($fileLocation);

                $html = $this->getView()->renderTemplate('users/_photo', ['account' => $user]);

                return $this->asJson(['html' => $html]);
            }
        } catch (Exception $exception) {
            /** @noinspection UnSafeIsSetOverArrayInspection - FP */
            if (isset($fileLocation)) {
                FileHelper::removeFile($fileLocation);
            }

            Craft::error('There was an error uploading the photo: '.$exception->getMessage(), __METHOD__);

            return $this->asErrorJson(Craft::t('app',
                'There was an error uploading your photo: {error}', ['error' => $exception->getMessage()]));
        }

        return null;
    }

    /**
     * Delete all the photos for current user.
     *
     * @return Response
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

        $html = $this->getView()->renderTemplate('users/_photo',
            [
                'account' => $user
            ]
        );

        return $this->asJson(['html' => $html]);
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

        $userId = Craft::$app->getRequest()->getRequiredBodyParam('userId');

        $user = User::find()
            ->id($userId)
            ->status(null)
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

        if (Craft::$app->getRequest()->getAcceptsJson()) {
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
        $this->requirePermission('administrateUsers');

        $userId = Craft::$app->getRequest()->getRequiredBodyParam('userId');
        $user = Craft::$app->getUsers()->getUserById($userId);

        if (!$user) {
            $this->_noUserExists();
        }

        // Even if you have administrateUsers permissions, only and admin should be able to unlock another admin.
        $currentUser = Craft::$app->getUser()->getIdentity();

        if ($user->admin && !$currentUser->admin) {
            throw new ForbiddenHttpException('Only admins can unlock other admins');
        }

        Craft::$app->getUsers()->unlockUser($user);

        Craft::$app->getSession()->setNotice(Craft::t('app',
            'User activated.'));

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
        $this->requirePermission('administrateUsers');

        $userId = Craft::$app->getRequest()->getRequiredBodyParam('userId');
        $user = Craft::$app->getUsers()->getUserById($userId);

        if (!$user) {
            $this->_noUserExists();
        }

        // Even if you have administrateUsers permissions, only and admin should be able to suspend another admin.
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
     * Deletes a user.
     *
     * @return Response|null
     */
    public function actionDeleteUser()
    {
        $this->requirePostRequest();
        $this->requireLogin();

        $this->requirePermission('deleteUsers');

        $userId = Craft::$app->getRequest()->getRequiredBodyParam('userId');
        $user = Craft::$app->getUsers()->getUserById($userId);

        if (!$user) {
            $this->_noUserExists();
        }

        // Even if you have deleteUser permissions, only and admin should be able to delete another admin.
        if ($user->admin) {
            $this->requireAdmin();
        }

        // Are we transferring the user's content to a different user?
        $transferContentToId = Craft::$app->getRequest()->getBodyParam('transferContentTo');

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
        $this->requirePermission('administrateUsers');

        $userId = Craft::$app->getRequest()->getRequiredBodyParam('userId');
        $user = Craft::$app->getUsers()->getUserById($userId);

        if (!$user) {
            $this->_noUserExists();
        }

        // Even if you have administrateUsers permissions, only and admin should be able to un-suspend another admin.
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
     * Saves the asset field layout.
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
        Craft::$app->getFields()->deleteLayoutsByType(User::class);

        if (!Craft::$app->getFields()->saveLayout($fieldLayout)) {
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

    // Private Methods
    // =========================================================================

    /**
     * Handles a failed login attempt.
     *
     * @param string|null $authError
     * @param User|null   $user
     *
     * @return Response|null
     */
    private function _handleLoginFailure(string $authError = null, User $user = null)
    {
        // Fire a 'loginFailure' event
        $this->trigger(self::EVENT_LOGIN_FAILURE, new LoginFailureEvent([
            'authError' => $authError,
            'user' => $user,
        ]));

        switch ($authError) {
            case User::AUTH_PENDING_VERIFICATION:
                $message = Craft::t('app', 'Account has not been activated.');
                break;
            case User::AUTH_ACCOUNT_LOCKED:
                $message = Craft::t('app', 'Account locked.');
                break;
            case User::AUTH_ACCOUNT_COOLDOWN:
                $timeRemaining = null;

                if ($user !== null) {
                    $timeRemaining = $user->getRemainingCooldownTime();
                }

                if ($timeRemaining) {
                    $message = Craft::t('app', 'Account locked. Try again in {time}.', ['time' => DateTimeHelper::humanDurationFromInterval($timeRemaining)]);
                } else {
                    $message = Craft::t('app', 'Account locked.');
                }
                break;
            case User::AUTH_PASSWORD_RESET_REQUIRED:
                $message = Craft::t('app', 'You need to reset your password. Check your email for instructions.');
                Craft::$app->getUsers()->sendPasswordResetEmail($user);
                break;
            case User::AUTH_ACCOUNT_SUSPENDED:
                $message = Craft::t('app', 'Account suspended.');
                break;
            case User::AUTH_NO_CP_ACCESS:
                $message = Craft::t('app', 'You cannot access the CP with that account.');
                break;
            case User::AUTH_NO_CP_OFFLINE_ACCESS:
                $message = Craft::t('app', 'You cannot access the CP while the system is offline with that account.');
                break;
            case User::AUTH_NO_SITE_OFFLINE_ACCESS:
                $message = Craft::t('app', 'You cannot access the site while the system is offline with that account.');
                break;
            default:
                if (Craft::$app->getConfig()->getGeneral()->useEmailAsUsername) {
                    $message = Craft::t('app', 'Invalid email or password.');
                } else {
                    $message = Craft::t('app', 'Invalid username or password.');
                }
        }

        if (Craft::$app->getRequest()->getAcceptsJson()) {
            return $this->asJson([
                'errorCode' => $authError,
                'error' => $message
            ]);
        }

        Craft::$app->getSession()->setError($message);

        Craft::$app->getUrlManager()->setRouteParams([
            'loginName' => Craft::$app->getRequest()->getBodyParam('loginName'),
            'rememberMe' => (bool)Craft::$app->getRequest()->getBodyParam('rememberMe'),
            'errorCode' => $authError,
            'errorMessage' => $message,
        ]);

        return null;
    }

    /**
     * Redirects the user after a successful login attempt, or if they visited the Login page while they were already
     * logged in.
     *
     * @param bool $setNotice Whether a flash notice should be set, if this isn't an Ajax request.
     *
     * @return Response
     */
    private function _handleSuccessfulLogin(bool $setNotice): Response
    {
        // Get the return URL
        $userService = Craft::$app->getUser();
        $returnUrl = $userService->getReturnUrl();

        // Clear it out
        $userService->removeReturnUrl();

        // If this was an Ajax request, just return success:true
        if (Craft::$app->getRequest()->getAcceptsJson()) {
            return $this->asJson([
                'success' => true,
                'returnUrl' => $returnUrl
            ]);
        } else {
            if ($setNotice) {
                Craft::$app->getSession()->setNotice(Craft::t('app', 'Logged in.'));
            }

            return $this->redirectToPostedUrl($userService->getIdentity(), $returnUrl);
        }
    }

    /**
     * Renders the Set Password template for a given user.
     *
     * @param User  $user
     * @param array $variables
     *
     * @return Response
     */
    private function _renderSetPasswordTemplate(User $user, array $variables): Response
    {
        $view = $this->getView();

        // If the user doesn't have CP access, see if a custom Set Password template exists
        if (!$user->can('accessCp')) {
            $view->setTemplateMode($view::TEMPLATE_MODE_SITE);
            $templatePath = Craft::$app->getConfig()->getGeneral()->getSetPasswordPath();

            if ($view->doesTemplateExist($templatePath)) {
                return $this->renderTemplate($templatePath, $variables);
            }
        }

        // Otherwise go with the CP's template
        $view->setTemplateMode($view::TEMPLATE_MODE_CP);

        return $this->renderTemplate('setpassword', $variables);
    }

    /**
     * Throws a "no user exists" exception
     *
     * @return void
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

        $currentHashedPassword = $currentUser->password;
        $currentPassword = Craft::$app->getRequest()->getRequiredParam('password');

        return Craft::$app->getSecurity()->validatePassword($currentPassword,
            $currentHashedPassword);
    }

    /**
     * @param User $user
     *
     * @return void
     */
    private function _processUserPhoto(User $user)
    {
        // Delete their photo?
        $users = Craft::$app->getUsers();

        if (Craft::$app->getRequest()->getBodyParam('deletePhoto')) {
            $users->deleteUserPhoto($user);
        }

        // Did they upload a new one?
        if ($photo = UploadedFile::getInstanceByName('photo')) {
            $fileLocation = Assets::tempFilePath($photo->getExtension());
            move_uploaded_file($photo->tempName, $fileLocation);
            $users->saveUserPhoto($fileLocation, $user, $photo->name);
            FileHelper::removeFile($fileLocation);
        }
    }

    /**
     * @param User $user
     *
     * @return void
     */
    private function _processUserGroupsPermissions(User $user)
    {
        $request = Craft::$app->getRequest();
        $edition = Craft::$app->getEdition();

        // Make sure there are assignUserPermissions
        if (Craft::$app->getUser()->checkPermission('assignUserPermissions')) {
            // Only Craft Pro has user groups
            if ($edition === Craft::Pro) {
                // Save any user groups
                $groupIds = $request->getBodyParam('groups');

                if ($groupIds !== null) {
                    if (is_array($groupIds)) {
                        // See if there are any new groups in here
                        $oldGroupIds = [];

                        foreach ($user->getGroups() as $group) {
                            $oldGroupIds[] = $group->id;
                        }

                        foreach ($groupIds as $groupId) {
                            if (!in_array($groupId, $oldGroupIds, false)) {
                                // Yep. This will require an elevated session
                                $this->requireElevatedSession();
                                break;
                            }
                        }
                    }

                    Craft::$app->getUsers()->assignUserToGroups($user->id, $groupIds);
                }
            }

            // Craft Client+ has user permissions.
            if ($edition >= Craft::Client) {
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
                    foreach ($permissions as $permission) {
                        if (!$user->can($permission)) {
                            // Yep. This will require an elevated session
                            $this->requireElevatedSession();
                            break;
                        }
                    }

                    Craft::$app->getUserPermissions()->saveUserPermissions($user->id, $permissions);
                }
            }
        }
    }

    /**
     * @return array|Response
     */
    private function _processTokenRequest()
    {
        $id = Craft::$app->getRequest()->getRequiredParam('id');
        $code = Craft::$app->getRequest()->getRequiredParam('code');
        $isCodeValid = false;

        $userToProcess = User::find()
            ->uid($id)
            ->status(null)
            ->addSelect(['users.password', 'users.unverifiedEmail'])
            ->one();

        // If someone is logged in and it's not this person, log them out
        $userService = Craft::$app->getUser();
        if (($identity = $userService->getIdentity()) !== null && $identity->id != $userToProcess->id) {
            $userService->logout();
        }

        if ($userToProcess) {
            // Fire a 'beforeVerifyUser' event
            Craft::$app->getUsers()->trigger(Users::EVENT_BEFORE_VERIFY_EMAIL,
                new UserTokenEvent([
                    'user' => $userToProcess
                ]));

            $isCodeValid = Craft::$app->getUsers()->isVerificationCodeValidForUser($userToProcess, $code);
        }

        if (!$userToProcess || !$isCodeValid) {
            return $this->_processInvalidToken($userToProcess);
        }

        // Fire an 'afterVerifyUser' event
        Craft::$app->getUsers()->trigger(Users::EVENT_AFTER_VERIFY_EMAIL,
            new UserTokenEvent([
                'user' => $userToProcess
            ]));

        return [
            'code' => $code,
            'id' => $id,
            'userToProcess' => $userToProcess
        ];
    }

    /**
     * @param User $user
     *
     * @return Response
     * @throws HttpException if the verification code is invalid
     */
    private function _processInvalidToken(User $user): Response
    {
        // If they're already logged-in, just send them to the post-login URL
        $userService = Craft::$app->getUser();
        if (!$userService->getIsGuest()) {
            $returnUrl = $userService->getReturnUrl();
            $userService->removeReturnUrl();

            return $this->redirect($returnUrl);
        }

        // If the invalidUserTokenPath config setting is set, send them there
        if ($url = Craft::$app->getConfig()->getGeneral()->getInvalidUserTokenPath()) {
            return $this->redirect(UrlHelper::siteUrl($url));
        }

        if ($user && $user->can('accessCp')) {
            $url = UrlHelper::cpUrl('login');
        } else {
            $url = UrlHelper::siteUrl(Craft::$app->getConfig()->getGeneral()->getLoginPath());
        }

        throw new HttpException('200', Craft::t('app', 'Invalid verification code. Please [login or reset your password]({loginUrl}).', ['loginUrl' => $url]));
    }

    /**
     * Takes over after a user has been activated.
     *
     * @param User $user The user that was just activated
     *
     * @return Response|null
     */
    private function _onAfterActivateUser(User $user)
    {
        $this->_maybeLoginUserAfterAccountActivation($user);

        if (!Craft::$app->getRequest()->getAcceptsJson()) {
            return $this->_redirectUserAfterAccountActivation($user);
        }

        return null;
    }

    /**
     * Possibly log a user in right after they were activate, if Craft is configured to do so.
     *
     * @param User $user The user that was just activated
     *
     * @return bool Whether the user was just logged in
     */
    private function _maybeLoginUserAfterAccountActivation(User $user): bool
    {
        if (Craft::$app->getConfig()->getGeneral()->autoLoginAfterAccountActivation === true) {
            return Craft::$app->getUser()->login($user);
        }

        return false;
    }

    /**
     * Redirect the browser after a user’s account has been activated.
     *
     * @param User $user The user that was just activated
     *
     * @return Response|null
     */
    private function _redirectUserAfterAccountActivation(User $user)
    {
        // Can they access the CP?
        if ($user->can('accessCp')) {
            $postCpLoginRedirect = Craft::$app->getConfig()->getGeneral()->postCpLoginRedirect;
            $url = UrlHelper::cpUrl($postCpLoginRedirect);

            return $this->redirect($url);
        }

        $activateAccountSuccessPath = Craft::$app->getConfig()->getGeneral()->getActivateAccountSuccessPath();
        $url = UrlHelper::siteUrl($activateAccountSuccessPath);

        return $this->redirectToPostedUrl($user, $url);
    }

    /**
     * @param string[]    $errors
     * @param string|null $loginName
     *
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
}
