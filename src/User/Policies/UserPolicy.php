<?php

declare(strict_types=1);

namespace CraftCms\Cms\User\Policies;

use CraftCms\Cms\Element\Policies\ElementPolicy;
use CraftCms\Cms\User\Elements\User;
use CraftCms\Cms\User\UserPermissions;

final class UserPolicy extends ElementPolicy
{
    public function __construct(
        private readonly UserPermissions $userPermissions,
    ) {}

    public function view(User $user, User $target): bool
    {
        return $user->id === $target->id || $user->can('viewUsers');
    }

    public function save(User $user, User $target): bool
    {
        // New user registration
        if (! $target->id) {
            return $user->canRegisterUsers();
        }

        // User can always save themselves
        if ($user->id === $target->id) {
            return true;
        }

        return $user->can('editUsers');
    }

    public function delete(User $user, User $target): bool
    {
        // Cannot delete yourself
        if ($user->id === $target->id) {
            return false;
        }

        // Need deleteUsers permission
        if (! $user->can('deleteUsers')) {
            return false;
        }

        // Non-admins cannot delete admins
        if ($target->admin && ! $user->admin) {
            return false;
        }

        return true;
    }

    public function duplicate(User $user, User $target): bool
    {
        return false;
    }

    public function copy(User $user, User $target): bool
    {
        return false;
    }

    public function impersonate(User $user, User $target): bool
    {
        // Admins can do whatever they want
        if ($user->admin) {
            return true;
        }

        // Only admins are allowed to impersonate another admin
        if ($target->admin) {
            return false;
        }

        // impersonateUsers permission is obviously required
        if (! $user->can('impersonateUsers')) {
            return false;
        }

        // Make sure the impersonator has at least all the same permissions as the target
        $userPermissions = $this->userPermissions->getPermissionsByUserId($user->id)->flip();
        $targetPermissions = $this->userPermissions->getPermissionsByUserId($target->id);

        foreach ($targetPermissions as $permission) {
            if (! isset($userPermissions[$permission]) && $this->userPermissions->validatePermission($permission)) {
                return false;
            }
        }

        return true;
    }

    public function suspend(User $user, User $target): bool
    {
        if (! $user->can('moderateUsers')) {
            return false;
        }

        // Even if you have moderateUsers permissions, only an admin should be able to suspend another admin
        if (! $user->admin && $target->admin) {
            return false;
        }

        if ($target->getHasSsoIdentity()) {
            return false;
        }

        return true;
    }
}
