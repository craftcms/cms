<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Controllers\Users;

use CraftCms\Cms\Auth\Concerns\EnforcesPermissions;
use CraftCms\Cms\Cp\Enums\Appearance;
use CraftCms\Cms\Cp\Html\ContentHtml;
use CraftCms\Cms\Cp\Html\ElementHtml;
use CraftCms\Cms\Http\Responses\CpScreenResponse;
use CraftCms\Cms\User\EditUserScreens;
use CraftCms\Cms\User\Elements\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

use function CraftCms\Cms\currentUser;
use function CraftCms\Cms\t;

trait EditUserTrait
{
    use EnforcesPermissions;

    /**
     * Returns the user being edited.
     *
     * @param  int|null  $userId  The user’s ID, if specified in the request URI
     */
    protected function editedUser(?int $userId): User
    {
        if ($userId === null) {
            return $this->editedUser(currentUser()?->getCraftUserId());
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

    protected function asEditUserScreen(User $user, string $screen, ?CpScreenResponse $response = null): CpScreenResponse
    {
        $screens = app(EditUserScreens::class)->screens($user);

        abort_if(! isset($screens[$screen]), 403, 'User not authorized to perform this action.');

        $pageName = $screens[$screen]['label'];
        $response = ($response ?? new CpScreenResponse)
            ->when(
                $user->getIsCurrent(),
                fn (CpScreenResponse $response) => $response
                    ->title($pageName)
                    ->docTitle($pageName),
                function (CpScreenResponse $response) use ($user, $pageName) {
                    $username = $user->getUiLabel();
                    $docTitle = "$username - $pageName";
                    $response->title($username);
                    $response->docTitle($docTitle);
                }
            );

        $screensService = app(EditUserScreens::class);

        $response->pageSidebarTemplate('_includes/nav', [
            'label' => t('Account'),
            'items' => $screensService->sidebarItems($user, $screen, $screens),
        ])->subnav($screensService->subnav($user, $screen, $screens));

        // Users / {user chip} / {screen}. The chip is no longer the last crumb,
        // so hyperlink it back to the user — `craft-breadcrumbs` derives
        // `aria-current="page"` from position, and that now belongs to the screen.
        $response->crumbs([
            ...$user->getCrumbs(),
            [
                'html' => app(ElementHtml::class)->elementChipHtml($user, [
                    'showDraftName' => false,
                    'class' => 'chromeless',
                    'hyperlink' => true,
                    'attributes' => [
                        'appearance' => Appearance::Plain->value,
                    ],
                ]),
            ],
            ['label' => $pageName],
        ]);

        if ($screen !== EditUserScreens::PROFILE) {
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
        if (! $request->craftUser()) {
            return false;
        }

        $currentPassword = $request->input('currentPassword') ?? $request->input('password');

        if (is_null($currentPassword)) {
            return false;
        }

        $currentHashedPassword = $request->craftUser()->asElement()->password;

        return Hash::check($currentPassword, $currentHashedPassword);
    }

    private function showPermissionsScreen(): bool
    {
        return app(EditUserScreens::class)->showPermissionsScreen();
    }

    private function editUserScreenUrl(User $user, string $screen): string
    {
        return app(EditUserScreens::class)->url($user, $screen);
    }
}
