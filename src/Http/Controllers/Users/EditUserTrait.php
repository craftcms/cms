<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Controllers\Users;

use CraftCms\Cms\Auth\Concerns\EnforcesPermissions;
use CraftCms\Cms\Cp\Html\ContentHtml;
use CraftCms\Cms\Cp\Html\ElementHtml;
use CraftCms\Cms\Edition;
use CraftCms\Cms\Http\Responses\CpScreenResponse;
use CraftCms\Cms\Support\URL;
use CraftCms\Cms\User\Elements\User;
use CraftCms\Cms\User\Events\DefineEditUserScreens;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

use function CraftCms\Cms\t;

trait EditUserTrait
{
    use EnforcesPermissions;

    private const string SCREEN_PROFILE = 'profile';

    private const string SCREEN_ADDRESSES = 'addresses';

    private const string SCREEN_PERMISSIONS = 'permissions';

    private const string SCREEN_PREFERENCES = 'preferences';

    private const string SCREEN_PASSWORD = 'password';

    private const string SCREEN_PASSKEYS = 'passkeys';

    /**
     * Returns the user being edited.
     *
     * @param  int|null  $userId  The user’s ID, if specified in the request URI
     */
    protected function editedUser(?int $userId): User
    {
        if ($userId === null) {
            return $this->editedUser(Auth::user()->id);
        }

        /** @var User|null $user */
        $user = User::find()
            ->addSelect(['users.password', 'users.passwordResetRequired'])
            ->id($userId)
            ->drafts(null)
            ->status(null)
            ->first();

        abort_if(is_null($user), 400, 'No user was identified by the request.');

        if (! $user->getIsCurrent()) {
            // Make sure they have permission to view other users
            $this->requirePermission('viewUsers');
        }

        return $user;
    }

    protected function asEditUserScreen(User $user, string $screen): CpScreenResponse
    {
        $screens = [
            self::SCREEN_PROFILE => ['label' => t('Profile')],
        ];

        if ($this->showPermissionsScreen()) {
            $screens[self::SCREEN_PERMISSIONS] = ['label' => t('Permissions')];
        }

        if ($user->getIsCurrent()) {
            $screens[self::SCREEN_PREFERENCES] = ['label' => t('Preferences')];
        }

        $screens[self::SCREEN_ADDRESSES] = ['label' => t('Addresses')];

        $currentUser = Auth::user();

        event($event = new DefineEditUserScreens($currentUser, $user, $screens));

        $screens = $event->screens;

        if ($user->getIsCurrent() && $user->getHasPassword()) {
            $screens[self::SCREEN_PASSWORD] = ['label' => t('Password & Verification')];
            $screens[self::SCREEN_PASSKEYS] = ['label' => t('Passkeys')];
        }

        abort_if(! isset($screens[$screen]), 403, 'User not authorized to perform this action.');

        $pageName = $screens[$screen]['label'];
        $response = new CpScreenResponse()
            ->when(
                $user->getIsCurrent(),
                fn (CpScreenResponse $response) => $response
                    ->title(t('My Account'))
                    ->docTitle($pageName),
                function (CpScreenResponse $response) use ($user, $pageName) {
                    $username = $user->getUiLabel();
                    $docTitle = "$username - $pageName";
                    $response->title($username);
                    $response->docTitle($docTitle);
                }
            );

        $navItems = [];
        $currentNavItems = &$navItems;

        foreach ($screens as $s => $screenInfo) {
            if ($s === self::SCREEN_PASSWORD) {
                $navItem = [
                    'heading' => t('Account Security'),
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
            'label' => t('Account'),
            'items' => $navItems,
        ]);

        if ($screen !== self::SCREEN_PROFILE) {
            $response->crumbs([
                ...$user->getCrumbs(),
                [
                    'html' => app(ElementHtml::class)->elementChipHtml($user, [
                        'showDraftName' => false,
                        'class' => 'chromeless',
                    ]),
                    'current' => true,
                ],
            ]);

            $response->addAltAction(t('Save and continue editing'), [
                'redirect' => $this->editUserScreenUrl($user, $screen),
                'shortcut' => true,
                'retainScroll' => true,
            ]);

            $response->actionMenuItems(fn () => array_filter(
                $user->getActionMenuItems(),
                fn (array $item) => ! str_starts_with($item['id'] ?? '', 'action-edit-'),
            ));

            $response->metaSidebarHtml($user->getSidebarHtml(false).app(ContentHtml::class)->metadataHtml($user->getMetadata()));
        }

        return $response;
    }

    protected function existingPasswordVerified(Request $request): bool
    {
        if (! $request->user()) {
            return false;
        }

        $currentPassword = $request->input('currentPassword') ?? $request->input('password');

        if (is_null($currentPassword)) {
            return false;
        }

        $currentHashedPassword = $request->user()->password;

        return Hash::check($currentPassword, $currentHashedPassword);
    }

    private function showPermissionsScreen(): bool
    {
        $currentUser = Auth::user();

        return
            Edition::get()->value >= Edition::Team->value &&
            (
                (Edition::get() === Edition::Team && $currentUser->admin) ||
                (Edition::get()->value >= Edition::Pro->value && $currentUser->can('assignUserPermissions')) ||
                $currentUser->canAssignUserGroups()
            );
    }

    private function editUserScreenUrl(User $user, string $screen): string
    {
        $basePath = $user->getIsCurrent() ? 'myaccount' : "users/$user->id";
        $path = match ($screen) {
            self::SCREEN_PROFILE => $basePath,
            default => "$basePath/$screen",
        };

        return URL::cpUrl($path);
    }
}
