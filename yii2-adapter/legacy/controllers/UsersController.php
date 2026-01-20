<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\controllers;

use Craft;
use craft\base\Event as YiiEvent;
use craft\base\ModelInterface;
use craft\elements\Entry;
use craft\events\DefineEditUserScreensEvent;
use craft\events\DefineUserContentSummaryEvent;
use craft\events\FindLoginUserEvent;
use craft\events\LoginFailureEvent;
use craft\events\UserEvent;
use craft\helpers\UrlHelper;
use craft\web\assets\authmethodsetup\AuthMethodSetupAsset;
use craft\web\Controller;
use craft\web\Request;
use craft\web\View;
use CraftCms\Cms\Auth\Concerns\ConfirmsPasswords;
use CraftCms\Cms\Auth\Events\LoginUserRetrieved;
use CraftCms\Cms\Auth\Events\RetrievingLoginUser;
use CraftCms\Cms\Auth\Impersonation;
use CraftCms\Cms\Auth\Methods\AuthMethodInterface;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Field\Fields;
use CraftCms\Cms\Support\Facades\Sections;
use CraftCms\Cms\Support\Facades\Users;
use CraftCms\Cms\User\Elements\User;
use CraftCms\Cms\User\Events\AssigningGroupsAndPermissions;
use CraftCms\Cms\User\Events\DefineEditUserScreens;
use CraftCms\Cms\User\Events\GroupsAndPermissionsAssigned;
use Illuminate\Auth\Events\Failed;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\URL;
use yii\base\InvalidArgumentException;
use yii\base\InvalidConfigException;
use yii\base\Model;
use yii\web\BadRequestHttpException;
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
