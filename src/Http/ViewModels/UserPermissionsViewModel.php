<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\ViewModels;

use CraftCms\Cms\Cms;
use CraftCms\Cms\Edition;
use CraftCms\Cms\Support\Facades\UserGroups;
use CraftCms\Cms\Support\Facades\UserPermissions;
use CraftCms\Cms\Support\Url;
use CraftCms\Cms\User\Contracts\CraftUser;
use CraftCms\Cms\User\Data\Permission;
use CraftCms\Cms\User\Data\UserGroup;
use CraftCms\Cms\User\Elements\User as UserElement;
use Illuminate\Support\Collection;

use function CraftCms\Cms\t;

class UserPermissionsViewModel extends ViewModel
{
    public function __construct(
        private readonly UserElement $user,
        private readonly CraftUser $currentUser,
    ) {}

    /** @return array{
     *     id: int,
     *     username: string|null,
     *     admin: bool,
     *     isCurrent: bool,
     *     isCredentialed: bool,
     * }
     */
    public function user(): array
    {
        return [
            'id' => $this->user->id,
            'username' => $this->user->username,
            'admin' => $this->user->admin,
            'isCurrent' => $this->user->getIsCurrent(),
            'isCredentialed' => $this->user->getIsCredentialed(),
        ];
    }

    /** @return array<int, array{
     *     id: int,
     *     name: string,
     *     handle: string,
     *     description: string|null,
     *     permissions: string[],
     * }>
     */
    public function groups(): array
    {
        return UserGroups::getAssignableGroups($this->user)
            ->map(fn (UserGroup $group): array => [
                'id' => $group->id,
                'name' => $group->name,
                'handle' => $group->handle,
                'description' => $group->description,
                'permissions' => UserPermissions::getPermissionsByGroupId($group->id)->values()->all(),
            ])
            ->values()
            ->all();
    }

    /** @return int[] */
    public function currentGroupIds(): array
    {
        return collect($this->user->getGroups())->pluck('id')->filter()->values()->all();
    }

    /** @return array<int, array{
     *     heading: string,
     *     permissions: array<string, Permission>,
     *     handle: string,
     *     keys: string[],
     * }>
     */
    public function permissions(): array
    {
        return UserPermissions::getAssignablePermissions($this->user)->values()->toArray();
    }

    /** @return string[] */
    public function directPermissions(): array
    {
        return $this->userPermissions()
            ->diff($this->groupPermissions())
            ->values()
            ->all();
    }

    /** @return string[] */
    public function inheritedPermissions(): array
    {
        return $this->groupPermissions()->all();
    }

    public function showAdminSwitch(): bool
    {
        return $this->currentUser->isAdmin();
    }

    /** @return array{
     *     allowAdminChanges: bool,
     *     settingsUrl: string,
     *     path: string[],
     * }|null
     */
    public function teamPermissionsNotice(): ?array
    {
        if (Edition::get() !== Edition::Team) {
            return null;
        }

        return [
            'allowAdminChanges' => Cms::config()->allowAdminChanges,
            'settingsUrl' => Url::cpUrl('settings/users'),
            'path' => [t('Settings'), t('Users')],
        ];
    }

    /** @return array{
     *     assignUserPermissions: bool,
     *     assignUserGroups: bool,
     *     createGroups: bool,
     *     canSendActivationEmail: bool,
     * }
     */
    public function can(): array
    {
        return [
            'assignUserPermissions' => $this->currentUser->can('assignUserPermissions'),
            'assignUserGroups' => $this->currentUser->can('assignUserGroups', $this->user),
            'createGroups' => $this->currentUser->isAdmin() && Cms::config()->allowAdminChanges && Edition::get()->value >= Edition::Pro->value,
            'canSendActivationEmail' => ! $this->user->getIsCredentialed() && $this->user->username && $this->currentUser->can('sendActivationEmail', $this->user),
        ];
    }

    /** @return Collection<int, string> */
    private function groupPermissions(): Collection
    {
        return $this->user->id
            ? UserPermissions::getGroupPermissionsByUserId($this->user->id)->values()
            : collect();
    }

    /** @return Collection<int, string> */
    private function userPermissions(): Collection
    {
        return $this->user->id
            ? UserPermissions::getPermissionsByUserId($this->user->id)
            : collect();
    }
}
