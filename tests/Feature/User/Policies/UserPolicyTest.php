<?php

declare(strict_types=1);

use CraftCms\Cms\Edition;
use CraftCms\Cms\Support\Facades\UserGroups;
use CraftCms\Cms\User\Elements\User as UserElement;
use CraftCms\Cms\User\Models\User;
use CraftCms\Cms\User\Models\UserGroup as UserGroupModel;
use CraftCms\Cms\User\Policies\UserPolicy;
use Illuminate\Support\Facades\Gate;

beforeEach(function () {
    $this->policy = app(UserPolicy::class);
});

it('is registered with the gate', function () {
    $targetUser = createUserTestUser(id: 2);
    $currentUser = createUserTestUser(id: 1, permissions: ['viewUsers']);

    $result = Gate::forUser($currentUser)->allows('view', $targetUser->asElement());

    expect($result)->toBeBool();
});

it('supports assignment abilities through the gate', function () {
    Edition::set(Edition::Pro);

    $groupModel = UserGroupModel::factory()->create();
    $group = UserGroups::getGroupById($groupModel->id);
    $targetUser = User::factory()->createElement();
    $user = User::factory()
        ->withPermissions(['viewUsers', 'editUsers', "assignUserGroup:$group->uid"])
        ->create();

    expect(Gate::forUser($user)->allows('assignUserGroups', $targetUser))->toBeTrue()
        ->and(Gate::forUser($user)->allows('assignUserGroup', [$targetUser, $group]))->toBeTrue();
});

it('allows editUsers to save another user through the gate without site permission', function () {
    Edition::set(Edition::Pro);

    $targetUser = User::factory()->createElement();
    $user = User::factory()
        ->withPermissions(['viewUsers', 'editUsers'])
        ->create();

    expect(Gate::forUser($user)->allows('save', $targetUser))->toBeTrue();
});

it('allows user to view themselves', function () {
    $user = createUserTestUser(id: 1);

    $result = $this->policy->view($user, $user->asElement());

    expect($result)->toBeTrue();
});

it('allows view users permission to view others', function () {
    $currentUser = createUserTestUser(id: 1, permissions: ['viewUsers']);
    $targetUser = createUserTestUser(id: 2);

    $result = $this->policy->view($currentUser, $targetUser->asElement());

    expect($result)->toBeTrue();
});

it('denies view without permission', function () {
    $currentUser = createUserTestUser(id: 1);
    $targetUser = createUserTestUser(id: 2);

    $result = $this->policy->view($currentUser, $targetUser->asElement());

    expect($result)->toBeFalse();
});

it('allows user to save themselves', function () {
    $user = createUserTestUser(id: 1);

    $result = $this->policy->save($user, $user->asElement());

    expect($result)->toBeTrue();
});

it('allows edit users permission to save others', function () {
    $currentUser = createUserTestUser(id: 1, permissions: ['editUsers']);
    $targetUser = createUserTestUser(id: 2);

    $result = $this->policy->save($currentUser, $targetUser->asElement());

    expect($result)->toBeTrue();
});

it('denies save without permission', function () {
    $currentUser = createUserTestUser(id: 1);
    $targetUser = createUserTestUser(id: 2);

    $result = $this->policy->save($currentUser, $targetUser->asElement());

    expect($result)->toBeFalse();
});

it('allows register users permission to create new user', function () {
    $currentUser = createUserTestUser(id: 1, permissions: ['registerUsers']);
    $newUser = createUserTestUser(); // new user without id

    $result = $this->policy->save($currentUser, $newUser->asElement());

    expect($result)->toBeTrue();
});

it('does not prevent user from deleting themselves', function () {
    $user = createUserTestUser(id: 1, permissions: ['deleteUsers']);

    $result = $this->policy->delete($user, $user->asElement());

    expect($result)->toBeTrue();
});

it('prevents user from deleting themselves when edition is solo', function () {
    Edition::set(Edition::Solo);

    $user = createUserTestUser(id: 1, permissions: ['deleteUsers']);

    $result = $this->policy->delete($user, $user->asElement());

    expect($result)->toBeFalse();
});

it('allows delete users permission to delete others', function () {
    $currentUser = createUserTestUser(id: 1, permissions: ['deleteUsers']);
    $targetUser = createUserTestUser(id: 2);

    $result = $this->policy->delete($currentUser, $targetUser->asElement());

    expect($result)->toBeTrue();
});

it('denies delete without permission', function () {
    $currentUser = createUserTestUser(id: 1);
    $targetUser = createUserTestUser(id: 2);

    $result = $this->policy->delete($currentUser, $targetUser->asElement());

    expect($result)->toBeFalse();
});

it('prevents non-admin from deleting admin', function () {
    $nonAdmin = createUserTestUser(id: 1, permissions: ['deleteUsers'], isAdmin: false);
    $adminTarget = createUserTestUser(id: 2, isAdmin: true);

    $result = $this->policy->delete($nonAdmin, $adminTarget->asElement());

    expect($result)->toBeFalse();
});

it('allows admin to delete other admin', function () {
    $admin = createUserTestUser(id: 1, permissions: ['deleteUsers'], isAdmin: true);
    $adminTarget = createUserTestUser(id: 2, isAdmin: true);

    $result = $this->policy->delete($admin, $adminTarget->asElement());

    expect($result)->toBeTrue();
});

it('prevents users from being duplicated', function () {
    $user = createUserTestUser(id: 1, isAdmin: true);
    $targetUser = createUserTestUser(id: 2);

    $result = $this->policy->duplicate($user, $targetUser->asElement());

    expect($result)->toBeFalse();
});

it('prevents users from being copied', function () {
    $user = createUserTestUser(id: 1, isAdmin: true);
    $targetUser = createUserTestUser(id: 2);

    $result = $this->policy->copy($user, $targetUser->asElement());

    expect($result)->toBeFalse();
});

it('only shows the permissions screen in supported editions', function () {
    $admin = User::factory()->admin()->create();

    Edition::set(Edition::Solo);
    expect($this->policy->viewPermissionsScreen($admin))->toBeFalse();

    Edition::set(Edition::Team);
    expect($this->policy->viewPermissionsScreen($admin))->toBeTrue();

    Edition::set(Edition::Pro);
    $user = User::factory()
        ->withPermissions(['viewUsers', 'editUsers', 'assignUserPermissions'])
        ->create();

    expect($this->policy->viewPermissionsScreen($user))->toBeTrue();
});

it('authorizes assigning user groups from the edition and group permissions', function () {
    Edition::set(Edition::Pro);

    $groupModel = UserGroupModel::factory()->create();
    $group = UserGroups::getGroupById($groupModel->id);
    $admin = User::factory()->admin()->create();
    $proAdmin = User::factory()->admin()->create();
    $user = User::factory()
        ->withPermissions(['viewUsers', 'editUsers', "assignUserGroup:$group->uid"])
        ->create();

    Edition::set(Edition::Solo);
    expect($this->policy->assignUserGroups($admin))->toBeFalse();

    Edition::set(Edition::Pro);
    expect($this->policy->assignUserGroups($proAdmin))->toBeTrue()
        ->and($this->policy->assignUserGroups($user))->toBeTrue();
});

it('checks the specific group assignment permission', function () {
    Edition::set(Edition::Pro);

    $groupModel = UserGroupModel::factory()->create();
    $group = UserGroups::getGroupById($groupModel->id);
    $targetUser = User::factory()->createElement();
    $user = User::factory()
        ->withPermissions(['viewUsers', 'editUsers', "assignUserGroup:$group->uid"])
        ->create();

    expect($this->policy->assignUserGroup(User::factory()->create(), $targetUser, $group))->toBeFalse()
        ->and($this->policy->assignUserGroup($user, $targetUser, $group))->toBeTrue();
});

it('authorizes assigning permissions without removing existing recipient permissions', function () {
    Edition::set(Edition::Pro);

    $recipient = User::factory()
        ->withPermissions(['viewUsers', 'editUsers'])
        ->createElement();
    $user = User::factory()
        ->withPermissions(['viewUsers', 'deleteUsers'])
        ->create();

    expect($this->policy->assignPermission(User::factory()->create(), $recipient, 'editUsers'))->toBeTrue()
        ->and($this->policy->assignPermission(User::factory()->create(), $recipient, 'deleteUsers'))->toBeFalse()
        ->and($this->policy->assignPermission($user, $recipient, 'deleteUsers'))->toBeTrue();
});

it('authorizes activating users with administrateUsers, but not admin targets unless self is admin', function () {
    Edition::set(Edition::Pro);

    $target = User::factory()->createElement([
        'active' => false,
    ]);
    $adminTarget = User::factory()->admin()->createElement([
        'active' => false,
    ]);
    $user = User::factory()
        ->withPermissions(['viewUsers', 'editUsers'])
        ->create();
    $administrator = User::factory()
        ->withPermissions(['viewUsers', 'editUsers', 'administrateUsers'])
        ->create();
    $admin = User::factory()->admin()->create();

    expect($this->policy->activate($user, $target))->toBeFalse()
        ->and($this->policy->activate($administrator, $target))->toBeTrue()
        ->and($this->policy->activate($administrator, $adminTarget))->toBeFalse()
        ->and($this->policy->activate($admin, $adminTarget))->toBeTrue();
});

it('authorizes deactivating users from self, administrateUsers, and admin target rules', function () {
    Edition::set(Edition::Pro);

    $self = User::factory()->create();
    $target = User::factory()->createElement();
    $adminTarget = User::factory()->admin()->createElement();
    $user = User::factory()
        ->withPermissions(['viewUsers', 'editUsers'])
        ->create();
    $administrator = User::factory()
        ->withPermissions(['viewUsers', 'editUsers', 'administrateUsers'])
        ->create();
    $admin = User::factory()->admin()->create();

    expect($this->policy->deactivate($self, $self->asElement()))->toBeTrue()
        ->and($this->policy->deactivate($user, $target))->toBeFalse()
        ->and($this->policy->deactivate($administrator, $target))->toBeTrue()
        ->and($this->policy->deactivate($administrator, $adminTarget))->toBeFalse()
        ->and($this->policy->deactivate($admin, $adminTarget))->toBeTrue();
});

it('authorizes activation email by pending and inactive status', function () {
    Edition::set(Edition::Pro);

    $user = User::factory()->create();
    $moderator = User::factory()
        ->withPermissions(['viewUsers', 'editUsers', 'moderateUsers'])
        ->create();
    $pendingTarget = User::factory()->pending()->createElement();
    $inactiveTarget = User::factory()->createElement([
        'active' => false,
        'pending' => false,
    ]);
    $activeTarget = User::factory()->active()->createElement([
        'pending' => false,
    ]);

    expect($this->policy->sendActivationEmail($user, $pendingTarget))->toBeTrue()
        ->and($this->policy->sendActivationEmail($user, $inactiveTarget))->toBeFalse()
        ->and($this->policy->sendActivationEmail($moderator, $inactiveTarget))->toBeTrue()
        ->and($this->policy->sendActivationEmail($moderator, $activeTarget))->toBeFalse();
});

// Impersonate tests
it('allows admin to impersonate any user', function () {
    $admin = createUserTestUser(id: 1, isAdmin: true);
    $targetUser = createUserTestUser(id: 2);

    $result = $this->policy->impersonate($admin, $targetUser->asElement());

    expect($result)->toBeTrue();
});

it('allows admin to impersonate another admin', function () {
    $admin = createUserTestUser(id: 1, isAdmin: true);
    $targetAdmin = createUserTestUser(id: 2, isAdmin: true);

    $result = $this->policy->impersonate($admin, $targetAdmin->asElement());

    expect($result)->toBeTrue();
});

it('denies non-admin from impersonating an admin', function () {
    $user = createUserTestUser(id: 1, permissions: ['impersonateUsers']);
    $admin = createUserTestUser(id: 2, isAdmin: true);

    $result = $this->policy->impersonate($user, $admin->asElement());

    expect($result)->toBeFalse();
});

it('denies impersonate without permission', function () {
    $user = createUserTestUser(id: 1);
    $targetUser = createUserTestUser(id: 2);

    $result = $this->policy->impersonate($user, $targetUser->asElement());

    expect($result)->toBeFalse();
});

// Suspend tests
it('allows user with moderateUsers to suspend non-admin', function () {
    $moderator = createUserTestUser(id: 1, permissions: ['moderateUsers']);
    $targetUser = createUserTestUser(id: 2);

    $result = $this->policy->suspend($moderator, $targetUser->asElement());

    expect($result)->toBeTrue();
});

it('denies suspend without moderateUsers permission', function () {
    $user = createUserTestUser(id: 1);
    $targetUser = createUserTestUser(id: 2);

    $result = $this->policy->suspend($user, $targetUser->asElement());

    expect($result)->toBeFalse();
});

it('denies non-admin from suspending an admin', function () {
    $moderator = createUserTestUser(id: 1, permissions: ['moderateUsers']);
    $admin = createUserTestUser(id: 2, isAdmin: true);

    $result = $this->policy->suspend($moderator, $admin->asElement());

    expect($result)->toBeFalse();
});

it('allows admin to suspend another admin', function () {
    $admin = createUserTestUser(id: 1, isAdmin: true, permissions: ['moderateUsers']);
    $targetAdmin = createUserTestUser(id: 2, isAdmin: true);

    $result = $this->policy->suspend($admin, $targetAdmin->asElement());

    expect($result)->toBeTrue();
});

it('denies suspend when target has SSO identity', function () {
    $moderator = createUserTestUser(id: 1, permissions: ['moderateUsers']);
    $ssoUser = createUserTestUser(id: 2, hasSsoIdentity: true);

    $result = $this->policy->suspend($moderator, $ssoUser->asElement());

    expect($result)->toBeFalse();
});

// Helper function
function createUserTestUser(
    ?int $id = null,
    array $permissions = [],
    bool $isAdmin = false,
    bool $hasSsoIdentity = false,
): User {
    $user = new class extends User
    {
        public array $grantedPermissions = [];

        public bool $hasSso = false;

        public function can($abilities, $arguments = []): bool
        {
            if (is_array($abilities)) {
                return array_all($abilities, fn ($ability) => $this->can($ability, $arguments));
            }

            return in_array($abilities, $this->grantedPermissions, true);
        }

        public function asElement(): UserElement
        {
            $element = new class extends UserElement
            {
                public bool $hasSso = false;

                public array $grantedPermissions = [];

                public function can($abilities, $arguments = []): bool
                {
                    if (is_array($abilities)) {
                        return array_all($abilities, fn ($ability) => $this->can($ability, $arguments));
                    }

                    return in_array($abilities, $this->grantedPermissions, true);
                }

                public function getHasSsoIdentity(): bool
                {
                    return $this->hasSso;
                }
            };

            $element->id = $this->id;
            $element->siteId = null;
            $element->admin = $this->admin;
            $element->hasSso = $this->hasSso;
            $element->grantedPermissions = $this->grantedPermissions;

            return $element;
        }
    };

    $user->id = $id;
    $user->siteId = null;
    $user->admin = $isAdmin;
    $user->grantedPermissions = $permissions;
    $user->hasSso = $hasSsoIdentity;

    return $user;
}
