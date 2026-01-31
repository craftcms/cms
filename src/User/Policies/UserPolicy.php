<?php

declare(strict_types=1);

namespace CraftCms\Cms\User\Policies;

use CraftCms\Cms\Element\Policies\ElementPolicy;
use CraftCms\Cms\User\Elements\User;

final class UserPolicy extends ElementPolicy
{
    public function view(User $user, User $element): bool
    {
        return $user->id === $element->id || $user->can('viewUsers');
    }

    public function save(User $user, User $element): bool
    {
        // New user registration
        if (! $element->id) {
            return $user->canRegisterUsers();
        }

        // User can always save themselves
        if ($user->id === $element->id) {
            return true;
        }

        return $user->can('editUsers');
    }

    public function delete(User $user, User $element): bool
    {
        // Cannot delete yourself
        if ($user->id === $element->id) {
            return false;
        }

        // Need deleteUsers permission
        if (! $user->can('deleteUsers')) {
            return false;
        }

        // Non-admins cannot delete admins
        if ($element->admin && ! $user->admin) {
            return false;
        }

        return true;
    }

    public function duplicate(User $user, User $element): bool
    {
        return false;
    }

    public function copy(User $user, User $element): bool
    {
        return false;
    }
}
