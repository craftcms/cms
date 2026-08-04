<?php

declare(strict_types=1);

namespace CraftCms\Cms\User\Policies;

use CraftCms\Cms\Edition;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\Policies\ElementPolicy;
use CraftCms\Cms\Support\Facades\UserGroups;
use CraftCms\Cms\Support\Facades\Users;
use CraftCms\Cms\User\Contracts\CraftUser;
use CraftCms\Cms\User\Data\UserGroup;
use CraftCms\Cms\User\Elements\User as UserElement;
use CraftCms\Cms\User\UserPermissions;
use Override;

class UserPolicy extends ElementPolicy
{
    public function __construct(
        private readonly UserPermissions $userPermissions,
    ) {}

    public function view(CraftUser $user, UserElement $target): bool
    {
        if ($user->getCraftUserId() === $target->id) {
            return true;
        }

        return $user->can('viewUsers');
    }

    public function save(CraftUser $user, UserElement $target): bool
    {
        // New user registration
        if (! $target->id) {
            return $user->can('registerUsers') && Users::canCreateUsers();
        }

        // User can always save themselves
        if ($user->getCraftUserId() === $target->id) {
            return true;
        }

        return $user->can('editUsers');
    }

    #[Override]
    protected function shouldCheckSiteAuthorization(ElementInterface $element): bool
    {
        return false;
    }

    public function delete(CraftUser $user, UserElement $target): bool
    {
        if (Edition::get() === Edition::Solo) {
            return false;
        }

        if ($user->getCraftUserId() === $target->id) {
            return true;
        }

        // Need deleteUsers permission
        if (! $user->can('deleteUsers')) {
            return false;
        }

        // Non-admins cannot delete admins
        if ($target->admin && ! $user->isAdmin()) {
            return false;
        }

        return true;
    }

    public function duplicate(CraftUser $user, UserElement $target): bool
    {
        return false;
    }

    public function copy(CraftUser $user, UserElement $target): bool
    {
        return false;
    }

    public function viewPermissionsScreen(CraftUser $user): bool
    {
        if (! Edition::isAtLeast(Edition::Team)) {
            return false;
        }

        return
            (Edition::get() === Edition::Team && $user->isAdmin()) ||
            (Edition::isAtLeast(Edition::Pro) && $user->can('assignUserPermissions')) ||
            $this->assignUserGroups($user);
    }

    public function assignUserGroups(CraftUser $user, ?UserElement $recipient = null): bool
    {
        if (! Edition::isAtLeast(Edition::Pro)) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        foreach (UserGroups::getAllGroups() as $group) {
            if ($user->can("assignUserGroup:$group->uid")) {
                return true;
            }
        }

        return false;
    }

    public function assignUserGroup(CraftUser $user, UserElement $recipient, UserGroup $group): bool
    {
        return $user->can("assignUserGroup:$group->uid");
    }

    public function assignPermission(CraftUser $user, UserElement $recipient, string $permission): bool
    {
        if ($recipient->can($permission)) {
            return true;
        }

        return $user->can($permission);
    }

    public function activate(CraftUser $user, UserElement $target): bool
    {
        if (! $user->can('administrateUsers')) {
            return false;
        }

        // Even if they have administrateUsers permissions, only an admin should be able to activate another admin
        if ($target->admin && ! $user->isAdmin()) {
            return false;
        }

        return true;
    }

    public function deactivate(CraftUser $user, UserElement $target): bool
    {
        if ($user->getCraftUserId() === $target->id) {
            return true;
        }

        if (! $user->can('administrateUsers')) {
            return false;
        }

        if ($target->admin && ! $user->isAdmin()) {
            return false;
        }

        return true;
    }

    public function sendActivationEmail(CraftUser $user, UserElement $target): bool
    {
        if (! in_array($target->getStatus(), [UserElement::STATUS_PENDING, UserElement::STATUS_INACTIVE], true)) {
            return false;
        }

        if ($target->pending) {
            return true;
        }

        return $user->can('moderateUsers');
    }

    public function impersonate(CraftUser $user, UserElement $target): bool
    {
        // Admins can do whatever they want
        if ($user->isAdmin()) {
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

        $userId = $user->getCraftUserId();

        if (! $userId) {
            return false;
        }

        // Make sure the impersonator has at least all the same permissions as the target
        $userPermissions = $this->userPermissions->getPermissionsByUserId($userId)->flip();
        $targetPermissions = $this->userPermissions->getPermissionsByUserId($target->id);

        foreach ($targetPermissions as $permission) {
            if (! isset($userPermissions[$permission]) && $this->userPermissions->validatePermission($permission)) {
                return false;
            }
        }

        return true;
    }

    public function suspend(CraftUser $user, UserElement $target): bool
    {
        if (! $user->can('moderateUsers')) {
            return false;
        }

        // Even if you have moderateUsers permissions, only an admin should be able to suspend another admin
        if (! $user->isAdmin() && $target->admin) {
            return false;
        }

        if ($target->getHasSsoIdentity()) {
            return false;
        }

        return true;
    }
}
