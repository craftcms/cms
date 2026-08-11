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
use CraftCms\Cms\User\EditUserScreens;
use CraftCms\Cms\User\Elements\User as UserElement;
use CraftCms\Cms\User\Events\GroupsAndPermissionsAssigned;
use CraftCms\Cms\User\Events\UserGroupsAndPermissionsAssigning;
use Illuminate\Http\Request;
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

        // Is their admin status changing?
        if ($currentUser->isAdmin()) {
            $adminParam = $request->boolean('admin', $user->admin);

            if ($adminParam !== $user->admin) {
                if ($adminParam) {
                    $this->requireConfirmedPassword();
                }

                $user->admin = $adminParam;
                $elements->saveElement($user, false);
            }
        }

        if (Edition::isAtLeast(Edition::Pro)) {
            event(new UserGroupsAndPermissionsAssigning($user));

            // Assign user groups and permissions if the current user is allowed to do that
            $this->saveUserGroups($request, $user, $currentUser);
            $this->saveUserPermissions($request, $user, $currentUser);

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

    private function saveUserGroups(Request $request, UserElement $user, CraftUser $currentUser): void
    {
        if (! $currentUser->can('assignUserGroups', $user)) {
            return;
        }

        $groupIds = $request->input('groups');

        if ($groupIds === null) {
            return;
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

        Users::assignUserToGroups($user->id, $groupIds);

        $user->setGroups($newGroups);
    }

    private function saveUserPermissions(Request $request, UserElement $user, CraftUser $currentUser): void
    {
        if (! $currentUser->can('assignUserPermissions')) {
            return;
        }

        // Resolve the permission set
        if ($user->admin) {
            // Granular permissions aren’t stored for administrators, because they’re implicitly authorized to do anything:
            $permissions = [];
        } else {
            // Laravel’s {@see \Illuminate\Foundation\Http\Middleware\ConvertEmptyStringsToNull} middleware makes `$request->input()` ambiguous when an empty set of permissions is sent.

            // If the request doesn’t indicate the current user is trying to update permissions, we can just bail:
            if (! $request->has('permissions')) {
                return;
            }

            // Now it’s safe to normalize whatever was sent (including an empty set) into an array:
            $permissions = $request->array('permissions');
        }

        // See if there are any new permissions in here
        $hasNewPermissions = false;

        foreach ($permissions as $permission) {
            if (! $user->can($permission)) {
                $hasNewPermissions = true;

                // Make sure the current user even has permission to grant it
                abort_if(
                    ! $currentUser->can('assignPermission', [$user, $permission]),
                    403,
                    "Your account doesn't have permission to assign the $permission permission to a user.",
                );
            }
        }

        if ($hasNewPermissions) {
            $this->requireConfirmedPassword();
        }

        UserPermissions::saveUserPermissions($user->id, $permissions);
    }
}
