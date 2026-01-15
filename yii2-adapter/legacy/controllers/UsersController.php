<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\controllers;

use Craft;
use craft\base\Element;
use craft\base\Event as YiiEvent;
use craft\base\ModelInterface;
use craft\base\NameTrait;
use craft\elements\Entry;
use craft\events\DefineEditUserScreensEvent;
use craft\events\DefineUserContentSummaryEvent;
use craft\events\FindLoginUserEvent;
use craft\events\InvalidUserTokenEvent;
use craft\events\LoginFailureEvent;
use craft\events\UserEvent;
use craft\helpers\Assets;
use craft\helpers\Db;
use craft\helpers\FileHelper;
use craft\helpers\Image;
use craft\helpers\UrlHelper;
use craft\web\assets\authmethodsetup\AuthMethodSetupAsset;
use craft\web\Controller;
use craft\web\Request;
use craft\web\UploadedFile;
use craft\web\View;
use CraftCms\Cms\Auth\Concerns\ConfirmsPasswords;
use CraftCms\Cms\Auth\Events\LoginUserRetrieved;
use CraftCms\Cms\Auth\Events\RetrievingLoginUser;
use CraftCms\Cms\Auth\Impersonation;
use CraftCms\Cms\Auth\Methods\AuthMethodInterface;
use CraftCms\Cms\Auth\RememberedUsername;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Edition;
use CraftCms\Cms\Field\Fields;
use CraftCms\Cms\ProjectConfig\ProjectConfig;
use CraftCms\Cms\Support\Facades\Deprecator;
use CraftCms\Cms\Support\Facades\Sections;
use CraftCms\Cms\Support\Facades\Sites;
use CraftCms\Cms\Support\Facades\Users;
use CraftCms\Cms\Support\Html;
use CraftCms\Cms\User\Elements\User;
use CraftCms\Cms\User\Events\AssigningGroupsAndPermissions;
use CraftCms\Cms\User\Events\DefineEditUserScreens;
use CraftCms\Cms\User\Events\GroupsAndPermissionsAssigned;
use Illuminate\Auth\Events\Failed;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\URL;
use Throwable;
use yii\base\InvalidArgumentException;
use yii\base\InvalidConfigException;
use yii\base\Model;
use yii\web\BadRequestHttpException;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;
use yii\web\Response;
use function CraftCms\Cms\t;

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
    use ConfirmsPasswords;
    use EditUserTrait;

    /**
     * @event FindLoginUserEvent The event that is triggered before attempting to find a user to sign in
     *
     * ```php
     * use Craft;
     * use craft\controllers\UsersController;
     * use CraftCms\Cms\User\Elements\User;
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
     * @deprecated 6.0.0 use {@see RetrievingLoginUser} instead.
     */
    public const EVENT_BEFORE_FIND_LOGIN_USER = 'beforeFindLoginUser';

    /**
     * @event FindLoginUserEvent The event that is triggered after attempting to find a user to sign in
     * @since 4.2.0
     * @deprecated 6.0.0 use {@see LoginUserRetrieved} instead.
     */
    public const EVENT_AFTER_FIND_LOGIN_USER = 'afterFindLoginUser';

    /**
     * @event LoginFailureEvent The event that is triggered when a failed login attempt was made
     * @deprecated 6.0.0 use {@see Failed} instead.
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
        'auth-form' => self::ALLOW_ANONYMOUS_LIVE | self::ALLOW_ANONYMOUS_OFFLINE,
        'save-user' => self::ALLOW_ANONYMOUS_LIVE,
    ];

    /**
     * @inheritdoc
     */
    public function beforeAction($action): bool
    {
        // Don't enable CSRF validation for login requests if the user is already logged-in.
        // (Guards against double-clicking a Login button.)
        if ($action->id === 'login' && !Auth::guest()) {
            $this->enableCsrfValidation = false;
        }

        return parent::beforeAction($action);
    }

    /**
     * Redirects the user to the default post-login URL.
     *
     * @return Response
     */
    public function actionRedirect(): Response
    {
        return $this->redirect(URL::defaultReturnUrl());
    }

    /**
     * Returns a 2FA setup screen, for users who require a 2FA method.
     *
     * @return Response
     * @since 5.0.0
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\Http\Middleware\Enforce2fa} instead.
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
        $currentUser = Auth::user();
        $canAdministrateUsers = $currentUser && $currentUser->can('administrateUsers');
        $generalConfig = Cms::config();
        $userSettings = app(ProjectConfig::class)->get('users') ?? [];
        $requireEmailVerification = (
            Edition::get()->value >= Edition::Pro->value &&
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
            Edition::require(Edition::Team);

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
            $user->addError('currentPassword', t('Incorrect current password.'));
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
            $groups = Users::getDefaultUserGroups($user);
            if (!empty($groups)) {
                $user->setGroups($groups);
            }

            // keep track of which site they registered from
            // (do this even if it's not a multi-site install, in case it becomes one later.)
            $user->affiliatedSiteId = Sites::getCurrentSite()->id;
        }

        // If this is Craft Pro, grab any profile content from post
        $fieldsLocation = $this->request->getParam('fieldsLocation', 'fields');
        $user->setFieldValuesFromRequest($fieldsLocation);

        // Validate and save!
        // ---------------------------------------------------------------------

        $photo = UploadedFile::getInstanceByName('photo');

        if ($photo && !Image::canManipulateAsImage($photo->getExtension())) {
            $user->addError('photo', t('The user photo provided is not an image.'));
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
                mb_ucfirst(t('Couldn’t save {type}.', [
                    'type' => User::lowerDisplayName(),
                ])),
                $userVariable
            );
        }

        // If this is a new user and email verification isn't required,
        // go ahead and activate them now.
        if ($isNewUser && !$requireEmailVerification && !$deactivateByDefault) {
            Users::activateUser($user);
        }

        // Is this the current user, and did their username just change?
        // todo: remove comment when WI-51866 is fixed
        /** @noinspection PhpUndefinedVariableInspection */
        if ($isCurrentUser && $user->username !== $oldUsername) {
            // Update the username cookie
            RememberedUsername::set($user);
        }

        // Save the user’s photo, if it was submitted
        $this->_processUserPhoto($user);

        if (Edition::get()->value >= Edition::Pro->value) {
            // If this is public registration, assign the user to the default user group
            if ($isPublicRegistration) {
                // Assign them to the default user group
                Users::assignUserToDefaultGroup($user);
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
                Users::sendActivationEmail($user);
            } else {
                // Send the standard verification email
                Users::sendNewEmailVerifyEmail($user);
            }

            // Put the original email back into place
            $user->email = $originalEmail;
        }

        // Is this public registration, and was the user going to be activated automatically?
        $publicActivation = $isPublicRegistration && $user->getStatus() === User::STATUS_ACTIVE;
        $loggedIn = $publicActivation && $this->_maybeLoginUserAfterAccountActivation($user);
        $returnCsrfToken = $returnCsrfToken || $loggedIn;

        if ($this->request->getAcceptsJson()) {
            return $this->asModelSuccess(
                $user,
                t('{type} saved.', ['type' => User::displayName()]),
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
                Deprecator::log('userRegisteredNotice', 'The `userRegisteredNotice` param has been deprecated for `users/save-user` requests. Use a hashed `successMessage` param instead.');
            } else {
                $default = t('User registered.');
            }
            $this->setSuccessFlash($default);
        } else {
            $this->setSuccessFlash(t('{type} saved.', [
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

        foreach (Sections::getAllSections() as $section) {
            $entryCount = Entry::find()
                ->sectionId($section->id)
                ->authorId($userId)
                ->site('*')
                ->unique()
                ->status(null)
                ->count();

            if ($entryCount) {
                $summary[] = t('{num, number} {section} {num, plural, =1{entry} other{entries}}', [
                    'num' => $entryCount,
                    'section' => t($section->name, category: 'site'),
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
     * Saves the user field layout.
     *
     * @return Response|null
     */
    public function actionSaveFieldLayout(): ?Response
    {
        $this->requirePostRequest();
        $this->requireAdmin();

        // Set the field layout
        $fieldLayout = app(Fields::class)->assembleLayoutFromPost();
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

        if (!Users::saveLayout($fieldLayout)) {
            Craft::$app->getUrlManager()->setRouteParams([
                'variables' => [
                    'fieldLayout' => $fieldLayout,
                ],
            ]);
            $this->setFailFlash(t('Couldn’t save user fields.'));
            return null;
        }

        $this->setSuccessFlash(t('User fields saved.'));
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

        return $this->asFailure(t('Invalid password.'));
    }

    public function actionAuthForm(): Response
    {
        // If the current user is being impersonated, use the impersonator
        $user = app(Impersonation::class)->getImpersonator() ?? app(\CraftCms\Cms\Auth\Auth::class)->getUser();

        if (!$user) {
            if ($this->request->getIsSiteRequest()) {
                $loginPath = Cms::config()->getLoginPath();
                if (!$loginPath) {
                    throw new InvalidConfigException('The loginPath config setting is disabled.');
                }
                return $this->redirect($loginPath);
            }

            return $this->redirect(Request::CP_PATH_LOGIN);
        }

        $activeMethods = app(\CraftCms\Cms\Auth\Auth::class)->getActiveMethods($user);
        $methodClass = $this->request->getParam('method');

        if ($methodClass) {
            /** @var AuthMethodInterface|null $method */
            $method = $activeMethods->first(
                fn(AuthMethodInterface $method) => $method::class === $methodClass,
            );

            if (!$method) {
                throw new BadRequestHttpException("Invalid method class: $methodClass");
            }
            $activeMethods = $activeMethods->filter(fn($m) => $m !== $method)->values();
        } else {
            if ($activeMethods->isEmpty()) {
                throw new BadRequestHttpException('User has no active two-step verification methods.');
            }
            $method = $activeMethods->first();
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
                $defaultReturnUrl = UrlHelper::cpUrl(Cms::config()->getPostCpLoginRedirect());
            } else {
                $defaultReturnUrl = UrlHelper::siteUrl(Cms::config()->getPostLoginRedirect());
            }
            $returnUrl = URL::returnUrl($defaultReturnUrl);
        }

        $authFormData = [
            'authMethod' => $method::class,
            'otherMethods' => $activeMethods->map(fn(AuthMethodInterface $method) => [
                'name' => $method::displayName(),
                'class' => $method::class,
            ])->all(),
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
     * Verifies that the user has an elevated session, or that their current password was submitted with the request.
     *
     * @return bool
     */
    private function _verifyElevatedSession(): bool
    {
        return ($this->isPasswordConfirmed() || $this->_verifyExistingPassword());
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
        if ($this->request->getBodyParam('deletePhoto')) {
            Users::deleteUserPhoto($user);
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
                Users::saveUserPhoto($fileLocation, $user, $filename, $mimeType);
            } catch (Throwable $e) {
                if (file_exists($fileLocation)) {
                    FileHelper::unlink($fileLocation);
                }

                throw $e;
            }
        }
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
        if (!Cms::config()->autoLoginAfterAccountActivation) {
            return false;
        }

        Auth::login($user);

        return true;
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
            $postCpLoginRedirect = Cms::config()->getPostCpLoginRedirect();
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
        $activateAccountSuccessPath = Cms::config()->getActivateAccountSuccessPath();
        $url = UrlHelper::siteUrl($activateAccountSuccessPath);
        return $this->redirectToPostedUrl($user, $url);
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
        mixed $model,
        ?string $message = null,
        ?string $modelName = null,
        array $data = [],
        ?string $redirect = null,
    ): Response {
        $this->clearPassword($model);
        return parent::asModelSuccess($model, $message, $modelName, $data, $redirect);
    }

    public function asModelFailure(
        mixed $model,
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

    public static function registerEvents(): void
    {
        Event::listen(DefineEditUserScreens::class, function(DefineEditUserScreens $event) {
            if (YiiEvent::hasHandlers(UsersController::class, UsersController::EVENT_DEFINE_EDIT_SCREENS)) {
                $currentUser = User::find()->id($event->currentUser->id)->one();
                $editedUser = User::find()->id($event->editedUser->id)->one();

                $yiiEvent = new DefineEditUserScreensEvent([
                    'currentUser' => $currentUser,
                    'editedUser' => $editedUser,
                    'screens' => $event->screens,
                ]);

                YiiEvent::trigger(UsersController::class, UsersController::EVENT_DEFINE_EDIT_SCREENS, $yiiEvent);
                $event->screens = $yiiEvent->screens;
            }
        });

        Event::listen(AssigningGroupsAndPermissions::class, function(AssigningGroupsAndPermissions $event) {
            if (YiiEvent::hasHandlers(UsersController::class, UsersController::EVENT_BEFORE_ASSIGN_GROUPS_AND_PERMISSIONS)) {
                $user = User::find()->id($event->user->id)->one();

                $yiiEvent = new UserEvent([
                    'user' => $user,
                ]);

                YiiEvent::trigger(UsersController::class, UsersController::EVENT_BEFORE_ASSIGN_GROUPS_AND_PERMISSIONS, $yiiEvent);
            }
        });

        Event::listen(GroupsAndPermissionsAssigned::class, function(GroupsAndPermissionsAssigned $event) {
            if (YiiEvent::hasHandlers(UsersController::class, UsersController::EVENT_AFTER_ASSIGN_GROUPS_AND_PERMISSIONS)) {
                $yiiEvent = new UserEvent([
                    'user' => $event->user,
                ]);

                YiiEvent::trigger(UsersController::class, UsersController::EVENT_AFTER_ASSIGN_GROUPS_AND_PERMISSIONS, $yiiEvent);
            }
        });

        Event::listen(RetrievingLoginUser::class, function(RetrievingLoginUser $event) {
            if (YiiEvent::hasHandlers(UsersController::class, UsersController::EVENT_BEFORE_FIND_LOGIN_USER)) {
                $yiiEvent = new FindLoginUserEvent([
                    'loginName' => $event->loginName,
                    'user' => $event->user,
                ]);

                YiiEvent::trigger(UsersController::class, UsersController::EVENT_BEFORE_FIND_LOGIN_USER, $yiiEvent);

                $event->user = $yiiEvent->user;
            }
        });

        Event::listen(LoginUserRetrieved::class, function(LoginUserRetrieved $event) {
            if (YiiEvent::hasHandlers(UsersController::class, UsersController::EVENT_AFTER_FIND_LOGIN_USER)) {
                $yiiEvent = new FindLoginUserEvent([
                    'loginName' => $event->loginName,
                    'user' => $event->user,
                ]);

                YiiEvent::trigger(UsersController::class, UsersController::EVENT_AFTER_FIND_LOGIN_USER, $yiiEvent);
            }
        });

        Event::listen(Failed::class, function(Failed $event) {
            if (YiiEvent::hasHandlers(UsersController::class, UsersController::EVENT_LOGIN_FAILURE)) {
                $yiiEvent = new LoginFailureEvent([
                    'user' => $event->user,
                ]);

                YiiEvent::trigger(UsersController::class, UsersController::EVENT_LOGIN_FAILURE, $yiiEvent);
            }
        });
    }
}
