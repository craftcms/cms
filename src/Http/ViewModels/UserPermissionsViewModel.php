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
use Illuminate\Contracts\Support\Arrayable;

use function CraftCms\Cms\t;

class UserPermissionsViewModel implements Arrayable
{
    public bool $readOnly = false;

    public ?string $details = null;

    /** @var array{
     *     id: int,
     *     username: string|null,
     *     admin: bool,
     *     isCurrent: bool,
     *     isCredentialed: bool,
     * }
     */
    public array $user;

    /** @var array<int, array{
     *     id: int,
     *     name: string,
     *     handle: string,
     *     description: string|null,
     *     permissions: string[],
     * }>
     */
    public array $groups;

    /** @var int[] */
    public array $currentGroupIds;

    /** @var array<int, array{
     *     heading: string,
     *     permissions: array<string, Permission>,
     *     handle: string,
     *     keys: string[],
     * }>
     */
    public array $permissions;

    /** @var string[] */
    public array $directPermissions;

    /** @var string[] */
    public array $inheritedPermissions;

    public bool $showAdminSwitch;

    /** @var array{
     *     allowAdminChanges: bool,
     *     settingsUrl: string,
     *     path: string[],
     * }|null
     */
    public ?array $teamPermissionsNotice;

    /** @var array{
     *     assignUserPermissions: bool,
     *     assignUserGroups: bool,
     *     createGroups: bool,
     *     canSendActivationEmail: bool,
     * }
     */
    public array $can;

    public function __construct(UserElement $user, CraftUser $currentUser)
    {
        $groupPermissions = $user->id
            ? UserPermissions::getGroupPermissionsByUserId($user->id)->values()
            : collect();

        $userPermissions = $user->id
            ? UserPermissions::getPermissionsByUserId($user->id)
            : collect();

        $this->user = [
            'id' => $user->id,
            'username' => $user->username,
            'admin' => $user->admin,
            'isCurrent' => $user->getIsCurrent(),
            'isCredentialed' => $user->getIsCredentialed(),
        ];

        $this->groups = UserGroups::getAssignableGroups($user)
            ->map(fn (UserGroup $group): array => [
                'id' => $group->id,
                'name' => $group->name,
                'handle' => $group->handle,
                'description' => $group->description,
                'permissions' => UserPermissions::getPermissionsByGroupId($group->id)->values()->all(),
            ])
            ->values()
            ->all();

        $this->currentGroupIds = collect($user->getGroups())->pluck('id')->filter()->values()->all();
        $this->permissions = UserPermissions::getAssignablePermissions($user)->values()->toArray();
        $this->directPermissions = $userPermissions->diff($groupPermissions)->values()->all();
        $this->inheritedPermissions = $groupPermissions->all();
        $this->showAdminSwitch = $currentUser->isAdmin();
        $this->teamPermissionsNotice = $this->teamPermissionsNotice();
        $this->can = $this->permissionsAbilities($currentUser, $user);
    }

    public function toArray(): array
    {
        return [
            'readOnly' => $this->readOnly,
            'details' => $this->details,
            'user' => $this->user,
            'groups' => $this->groups,
            'currentGroupIds' => $this->currentGroupIds,
            'permissions' => $this->permissions,
            'directPermissions' => $this->directPermissions,
            'inheritedPermissions' => $this->inheritedPermissions,
            'showAdminSwitch' => $this->showAdminSwitch,
            'teamPermissionsNotice' => $this->teamPermissionsNotice,
            'can' => $this->can,
        ];
    }

    private function teamPermissionsNotice(): ?array
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

    private function permissionsAbilities(CraftUser $currentUser, UserElement $user): array
    {
        return [
            'assignUserPermissions' => $currentUser->can('assignUserPermissions'),
            'assignUserGroups' => $currentUser->can('assignUserGroups', $user),
            'createGroups' => $currentUser->isAdmin() && Cms::config()->allowAdminChanges && Edition::get()->value >= Edition::Pro->value,
            'canSendActivationEmail' => ! $user->getIsCredentialed() && $user->username && $currentUser->can('sendActivationEmail', $user),
        ];
    }
}
