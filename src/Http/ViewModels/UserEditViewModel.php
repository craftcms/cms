<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\ViewModels;

use CraftCms\Cms\Cp\Data\NavItem;
use CraftCms\Cms\Cp\Html\ElementHtml;
use CraftCms\Cms\Http\Requests\ElementRequest;
use CraftCms\Cms\Support\Url;
use CraftCms\Cms\User\EditUserScreens;
use CraftCms\Cms\User\Elements\User;
use Illuminate\Support\Facades\Crypt;
use Override;

use function CraftCms\Cms\t;

/**
 * The Inertia payload for the account's Profile screen (`users/Edit`).
 *
 * Users have no drafts, revisions, or editable status, so this is the field
 * layout plus the account navigation the sibling screens (Permissions,
 * Preferences, Addresses, …) share.
 */
class UserEditViewModel extends ElementEditViewModel
{
    public function __construct(
        private readonly User $user,
        ElementRequest $request,
        bool $canSave = true,
    ) {
        parent::__construct($user, $request, $canSave);
    }

    /** Users have their own store action; the generic one refuses their sensitive attributes. */
    #[Override]
    protected function elementSaveUrl(): string
    {
        return Url::actionUrl('users/save-user');
    }

    /** What `users/save-user` resolves the account from. */
    public function userId(): ?int
    {
        return $this->user->id;
    }

    /**
     * The account navigation, so the profile screen sits beside its siblings.
     *
     * @return list<NavItem>
     */
    public function subnav(): array
    {
        return app(EditUserScreens::class)->subnav($this->user, EditUserScreens::PROFILE);
    }

    /**
     * Where a save lands. A brand-new account goes on to its permissions so it
     * can be given some, mirroring the legacy screen.
     *
     * Encrypted, because the save controllers decrypt whatever `redirect` a
     * request posts rather than trusting it.
     */
    public function redirectUrl(): string
    {
        $screens = app(EditUserScreens::class);

        return Crypt::encrypt(
            $this->user->getIsUnpublishedDraft() && $screens->showPermissionsScreen()
                ? $screens->url($this->user, EditUserScreens::PERMISSIONS)
                : $screens->url($this->user, EditUserScreens::PROFILE)
        );
    }

    /** The current user's own account is "My Account", not their name. */
    #[Override]
    public function title(): string
    {
        return $this->user->getIsCurrent() ? t('My Account') : $this->user->getUiLabel();
    }

    #[Override]
    public function docTitle(): string
    {
        $profile = t('Profile');

        return $this->user->getIsCurrent()
            ? $profile
            : $this->user->getUiLabel()." - $profile";
    }

    /**
     * The user's own crumbs, ending in a chip for the account being edited —
     * the same trail the sibling account screens render.
     *
     * @return list<array<string, mixed>>
     */
    #[Override]
    public function crumbs(): array
    {
        return [
            ...array_map(function (array $crumb): array {
                if (isset($crumb['url'])) {
                    $crumb['url'] = Url::cpUrl($crumb['url']);
                }

                return $crumb;
            }, $this->user->getCrumbs()),
            [
                'html' => app(ElementHtml::class)->elementChipHtml($this->user, [
                    'showDraftName' => false,
                    'class' => 'chromeless',
                ]),
                'current' => true,
            ],
        ];
    }

    #[Override]
    public function submitButtonLabel(): string
    {
        if (! $this->user->getIsUnpublishedDraft()) {
            return t('Save');
        }

        return app(EditUserScreens::class)->showPermissionsScreen()
            ? t('Create and set permissions')
            : mb_ucfirst(t('Create {type}', ['type' => User::lowerDisplayName()]));
    }
}
