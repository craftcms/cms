<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Controllers\Users;

use CraftCms\Cms\Auth\Concerns\ConfirmsPasswords;
use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Edition;
use CraftCms\Cms\Element\Elements;
use CraftCms\Cms\Http\RespondsWithFlash;
use CraftCms\Cms\Http\Responses\CpScreenResponse;
use CraftCms\Cms\Http\ViewModels\UserPermissionsViewModel;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\Facades\UserGroups;
use CraftCms\Cms\Support\Facades\UserPermissions;
use CraftCms\Cms\Support\Facades\Users;
use CraftCms\Cms\Support\Flash;
use CraftCms\Cms\User\Contracts\CraftUser;
use CraftCms\Cms\User\Data\UserGroup;
use CraftCms\Cms\User\EditUserScreens;
use CraftCms\Cms\User\Elements\User as UserElement;
use CraftCms\Cms\User\Events\GroupsAndPermissionsAssigned;
use CraftCms\Cms\User\Events\UserGroupsAndPermissionsAssigning;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

use function CraftCms\Cms\t;

readonly class PermissionsController
{
    use ConfirmsPasswords;
    use EditUserTrait;
    use RespondsWithFlash;

    public function index(Request $request, ?int $userId = null): CpScreenResponse
    {
        $user = $this->editedUser($userId);
        $currentUser = $request->craftUser();
        if (! $currentUser) {
            abort(401);
        }

        // Elevation is handled client-side by the Inertia page via
        // `useSettingsSave({elevatedFields: […]})`, and enforced server-side in
        // `update()` via `requireConfirmedPassword()`.
        return $this->asEditUserScreen($user, EditUserScreens::PERMISSIONS)
            ->inertiaPage('users/Permissions', new UserPermissionsViewModel($user, $currentUser));
    }

    public function update(Request $request, Elements $elements, ?int $userId = null): Response
    {
        $request->validate([
            'admin' => ['nullable', 'boolean'],
            'groups' => ['nullable', 'array'],
            'groups.*' => ['integer', Rule::exists(Table::USERGROUPS, 'id')],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string'],
            'sendActivationEmail' => ['nullable', 'boolean'],
        ]);

        if (! $this->showPermissionsScreen()) {
            abort(403, 'User not authorized to perform this action.');
        }

        $currentUser = $request->craftUser();
        if (! $currentUser) {
            abort(401);
        }

        $user = $this->editedUser($userId);

        /** @var array{admin: bool, groups: UserGroup[]|null, permissions: string[]|null} $changes */
        $changes = [
            'admin' => $currentUser->isAdmin() ? $request->boolean('admin', $user->admin) : $user->admin,
            'groups' => null,
            'permissions' => null,
        ];

        if ($changes['admin'] && ! $user->admin) {
            $this->requireConfirmedPassword();
        }

        $proposedUser = clone $user;
        $proposedUser->admin = $changes['admin'];

        if (Edition::isAtLeast(Edition::Pro)) {
            $changes['groups'] = $this->prepareUserGroups($request, $proposedUser, $currentUser);
            if ($changes['groups'] !== null) {
                $proposedUser->setGroups($changes['groups']);
            }
            $changes['permissions'] = $this->prepareUserPermissions($request, $proposedUser, $currentUser, $changes['groups']);
        }

        if ($changes['admin'] !== $user->admin) {
            $user->admin = $changes['admin'];
            if (! $elements->saveElement($user, false)) {
                return $this->asFailure(t('Couldn’t save permissions.'));
            }
        }

        if (Edition::isAtLeast(Edition::Pro)) {
            event(new UserGroupsAndPermissionsAssigning($user));

            if ($changes['groups'] !== null) {
                if (! Users::assignUserToGroups($user->id, Arr::pluck($changes['groups'], 'id'))) {
                    return $this->asFailure(t('Couldn’t save permissions.'));
                }
                $user->setGroups($changes['groups']);
            }

            if ($changes['permissions'] !== null && ! UserPermissions::saveUserPermissions($user->id, $changes['permissions'])) {
                return $this->asFailure(t('Couldn’t save permissions.'));
            }

            event(new GroupsAndPermissionsAssigned($user));
        }

        if (
            ! $user->getIsCredentialed() &&
            $currentUser->can('sendActivationEmail', $user) &&
            $request->boolean('sendActivationEmail')
        ) {
            try {
                Users::sendActivationEmail($user);
            } catch (Throwable $e) {
                Flash::error(t('Couldn’t send the activation email: {error}', [
                    'error' => $e->getMessage(),
                ]));
            }
        }

        return $this->asSuccess(t('Permissions saved.'));
    }

    /** @return UserGroup[]|null */
    private function prepareUserGroups(Request $request, UserElement $user, CraftUser $currentUser): ?array
    {
        if (! $currentUser->can('assignUserGroups', $user)) {
            return null;
        }

        $groupIds = $request->input('groups');

        if ($groupIds === null) {
            return null;
        }

        if ($groupIds === '') {
            $groupIds = [];
        }

        $allGroups = UserGroups::getAllGroups()->keyBy('id');

        // See if there are any new groups in here
        $oldGroupIds = Arr::pluck($user->getGroups(), 'id');
        $hasNewGroups = false;
        $newGroups = [];

        foreach ($groupIds as $groupId) {
            $group = $newGroups[] = $allGroups[$groupId];

            if (! in_array($groupId, $oldGroupIds, false)) {
                $hasNewGroups = true;

                // Make sure the current user is in the group or has permission to assign it
                abort_if(
                    ! $currentUser->can('assignUserGroup', [$user, $group]),
                    403,
                    "Your account doesn't have permission to assign user group “{$group->name}” to a user.",
                );
            }
        }

        if ($hasNewGroups) {
            $this->requireConfirmedPassword();
        }

        return $newGroups;
    }

    /**
     * @param  UserGroup[]|null  $groups
     * @return string[]|null
     */
    private function prepareUserPermissions(Request $request, UserElement $user, CraftUser $currentUser, ?array $groups): ?array
    {
        if (! $currentUser->can('assignUserPermissions')) {
            return null;
        }

        // Resolve the permission set
        if ($user->admin) {
            // Granular permissions aren’t stored for administrators, because they’re implicitly authorized to do anything:
            $permissions = [];
        } else {
            // Laravel’s {@see \Illuminate\Foundation\Http\Middleware\ConvertEmptyStringsToNull} middleware makes `$request->input()` ambiguous when an empty set of permissions is sent.

            // If the request doesn’t indicate the current user is trying to update permissions, we can just bail:
            if (! $request->has('permissions')) {
                return null;
            }

            // Now it’s safe to normalize whatever was sent (including an empty set) into an array:
            $permissions = $request->array('permissions');
        }

        // Evaluate inherited permissions against the proposed groups without persisting them.
        $effectivePermissions = null;
        if ($groups !== null && $permissions !== []) {
            $effectivePermissions = DB::table(Table::USERPERMISSIONS)
                ->join(Table::USERPERMISSIONS_USERS, 'permissionId', '=', Table::USERPERMISSIONS.'.id')
                ->where('userId', $user->id)
                ->pluck('name');

            foreach ($groups as $group) {
                $effectivePermissions = $effectivePermissions->merge(UserPermissions::getPermissionsByGroupId($group->id));
            }
        }

        // See if there are any new permissions in here
        $hasNewPermissions = false;

        foreach ($permissions as $permission) {
            $hasPermission = $effectivePermissions !== null
                ? $effectivePermissions->contains(fn (string $name) => strcasecmp($name, $permission) === 0)
                : $user->can($permission);

            if (! $hasPermission) {
                $hasNewPermissions = true;

                // The policy can see old group membership, so also check the grantor when groups are being replaced.
                abort_if(
                    ! $currentUser->can('assignPermission', [$user, $permission]) ||
                    ($groups !== null && ! $currentUser->can($permission)),
                    403,
                    "Your account doesn't have permission to assign the $permission permission to a user.",
                );
            }
        }

        if ($hasNewPermissions) {
            $this->requireConfirmedPassword();
        }

        return $permissions;
    }
}
