<?php

/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\controllers;

use Craft;
use craft\base\Event;
use craft\elements\User;
use craft\enums\CmsEdition;
use craft\events\DefineEditUserScreensEvent;
use craft\helpers\Cp;
use craft\helpers\UrlHelper;
use craft\web\Controller;
use yii\web\BadRequestHttpException;
use yii\web\ForbiddenHttpException;
use yii\web\Response;

/**
 * Trait EditUserTrait
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 5.1.0
 * @mixin Controller
 * @phpstan-require-extends Controller
 */
trait EditUserTrait
{
    private const SCREEN_PROFILE = 'profile';
    private const SCREEN_ADDRESSES = 'addresses';
    private const SCREEN_PERMISSIONS = 'permissions';
    private const SCREEN_PREFERENCES = 'preferences';
    private const SCREEN_PASSWORD = 'password';
    private const SCREEN_PASSKEYS = 'passkeys';

    /**
     * Returns the user being edited.
     *
     * @param int|null $userId The user’s ID, if specified in the request URI
     * @return User
     * @throws BadRequestHttpException
     */
    protected function editedUser(?int $userId): User
    {
        if ($userId === null) {
            return static::currentUser();
        }

        /** @var User|null $user */
        $user = User::find()
            ->addSelect(['users.password', 'users.passwordResetRequired'])
            ->id($userId)
            ->drafts(null)
            ->status(null)
            ->one();

        if (!$user) {
            throw new BadRequestHttpException('No user was identified by the request.');
        }

        if (!$user->getIsCurrent()) {
            // Make sure they have permission to view other users
            $this->requirePermission('viewUsers');
        }

        return $user;
    }

    /**
     * Prepares the response for a user management screen.
     *
     * @param User $user
     * @param string $screen
     * @return Response
     * @throws ForbiddenHttpException
     */
    protected function asEditUserScreen(User $user, string $screen): Response
    {
        $currentUser = static::currentUser();

        $screens = [
            self::SCREEN_PROFILE => ['label' => Craft::t('app', 'Profile')],
        ];

        if ($this->showPermissionsScreen()) {
            $screens[self::SCREEN_PERMISSIONS] = ['label' => Craft::t('app', 'Permissions')];
        }

        if ($user->getIsCurrent()) {
            $screens[self::SCREEN_PREFERENCES] = ['label' => Craft::t('app', 'Preferences')];
        }

        $screens[self::SCREEN_ADDRESSES] = ['label' => Craft::t('app', 'Addresses')];

        // Fire a 'defineEditScreens' event
        if (Event::hasHandlers(UsersController::class, UsersController::EVENT_DEFINE_EDIT_SCREENS)) {
            $event = new DefineEditUserScreensEvent([
                'currentUser' => $currentUser,
                'editedUser' => $user,
                'screens' => $screens,
            ]);
            Event::trigger(UsersController::class, UsersController::EVENT_DEFINE_EDIT_SCREENS, $event);
            $screens = $event->screens;
        }

        if ($user->getIsCurrent() && $user->getHasPassword()) {
            $screens[self::SCREEN_PASSWORD] = ['label' => Craft::t('app', 'Password & Verification')];
            $screens[self::SCREEN_PASSKEYS] = ['label' => Craft::t('app', 'Passkeys')];
        }

        if (!isset($screens[$screen])) {
            throw new ForbiddenHttpException('User not authorized to perform this action.');
        }

        $pageName = $screens[$screen]["label"];
        $response = $this->asCpScreen();
        if ($user->getIsCurrent()) {
            $response->title(Craft::t('app', 'My Account'));
            $response->docTitle($pageName);
        } else {
            $username = $user->getUiLabel();
            $docTitle = "$username - $pageName";
            $response->title($username);
            $response->docTitle($docTitle);
        }

        $navItems = [];
        $currentNavItems = &$navItems;

        foreach ($screens as $s => $screenInfo) {
            if ($s === self::SCREEN_PASSWORD) {
                $navItem = [
                    'heading' => Craft::t('app', 'Account Security'),
                    'nested' => [],
                ];
                $navItems[] = &$navItem;
                $currentNavItems = &$navItem['nested'];
            }

            $currentNavItems[] = [
                'label' => $screenInfo['label'],
                'url' => $screenInfo['url'] ?? $this->editUserScreenUrl($user, $s),
                'selected' => $s === $screen,
            ];
        }

        $response->pageSidebarTemplate('_includes/nav', [
            'label' => Craft::t('app', 'Account'),
            'items' => $navItems,
        ]);

        if ($screen !== self::SCREEN_PROFILE) {
            $response->crumbs([
                ...$user->getCrumbs(),
                [
                    'html' => Cp::elementChipHtml($user, [
                        'showDraftName' => false,
                        'class' => 'chromeless',
                    ]),
                    'current' => true,
                ],
            ]);

            $response->addAltAction(Craft::t('app', 'Save and continue editing'), [
                'redirect' => $this->editUserScreenUrl($user, $screen),
                'shortcut' => true,
                'retainScroll' => true,
            ]);

            $response->actionMenuItems(fn() => array_filter(
                $user->getActionMenuItems(),
                fn(array $item) => !str_starts_with($item['id'] ?? '', 'action-edit-'),
            ));

            $response->metaSidebarHtml($user->getSidebarHtml(false) . Cp::metadataHtml($user->getMetadata()));
        }

        return $response;
    }

    private function showPermissionsScreen(): bool
    {
        $currentUser = static::currentUser();
        return (
            Craft::$app->edition->value >= CmsEdition::Team->value &&
            (
                (Craft::$app->edition === CmsEdition::Team && $currentUser->admin) ||
                (Craft::$app->edition->value >= CmsEdition::Pro->value && $currentUser->can('assignUserPermissions')) ||
                $currentUser->canAssignUserGroups()
            )
        );
    }

    private function editUserScreenUrl(User $user, string $screen): string
    {
        $basePath = $user->getIsCurrent() ? 'myaccount' : "users/$user->id";
        $path = match ($screen) {
            self::SCREEN_PROFILE => $basePath,
            default => "$basePath/$screen",
        };
        return UrlHelper::cpUrl($path);
    }
}
