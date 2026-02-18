<?php

declare(strict_types=1);

use CraftCms\Cms\User\Elements\User;
use CraftCms\Cms\User\Policies\UserPolicy;
use Illuminate\Support\Facades\Gate;

beforeEach(function () {
    $this->policy = app(UserPolicy::class);
});

it('is registered with the gate', function () {
    $targetUser = createUserTestUser(id: 2);
    $currentUser = createUserTestUser(id: 1, permissions: ['viewUsers']);

    $result = Gate::forUser($currentUser)->allows('view', $targetUser);

    expect($result)->toBeBool();
});

it('allows user to view themselves', function () {
    $user = createUserTestUser(id: 1);

    $result = $this->policy->view($user, $user);

    expect($result)->toBeTrue();
});

it('allows view users permission to view others', function () {
    $currentUser = createUserTestUser(id: 1, permissions: ['viewUsers']);
    $targetUser = createUserTestUser(id: 2);

    $result = $this->policy->view($currentUser, $targetUser);

    expect($result)->toBeTrue();
});

it('denies view without permission', function () {
    $currentUser = createUserTestUser(id: 1);
    $targetUser = createUserTestUser(id: 2);

    $result = $this->policy->view($currentUser, $targetUser);

    expect($result)->toBeFalse();
});

it('allows user to save themselves', function () {
    $user = createUserTestUser(id: 1);

    $result = $this->policy->save($user, $user);

    expect($result)->toBeTrue();
});

it('allows edit users permission to save others', function () {
    $currentUser = createUserTestUser(id: 1, permissions: ['editUsers']);
    $targetUser = createUserTestUser(id: 2);

    $result = $this->policy->save($currentUser, $targetUser);

    expect($result)->toBeTrue();
});

it('denies save without permission', function () {
    $currentUser = createUserTestUser(id: 1);
    $targetUser = createUserTestUser(id: 2);

    $result = $this->policy->save($currentUser, $targetUser);

    expect($result)->toBeFalse();
});

it('allows register users permission to create new user', function () {
    $currentUser = createUserTestUser(id: 1, canRegister: true);
    $newUser = createUserTestUser(); // new user without id

    $result = $this->policy->save($currentUser, $newUser);

    expect($result)->toBeTrue();
});

it('prevents user from deleting themselves', function () {
    $user = createUserTestUser(id: 1, permissions: ['deleteUsers']);

    $result = $this->policy->delete($user, $user);

    expect($result)->toBeFalse();
});

it('allows delete users permission to delete others', function () {
    $currentUser = createUserTestUser(id: 1, permissions: ['deleteUsers']);
    $targetUser = createUserTestUser(id: 2);

    $result = $this->policy->delete($currentUser, $targetUser);

    expect($result)->toBeTrue();
});

it('denies delete without permission', function () {
    $currentUser = createUserTestUser(id: 1);
    $targetUser = createUserTestUser(id: 2);

    $result = $this->policy->delete($currentUser, $targetUser);

    expect($result)->toBeFalse();
});

it('prevents non-admin from deleting admin', function () {
    $nonAdmin = createUserTestUser(id: 1, permissions: ['deleteUsers'], isAdmin: false);
    $adminTarget = createUserTestUser(id: 2, isAdmin: true);

    $result = $this->policy->delete($nonAdmin, $adminTarget);

    expect($result)->toBeFalse();
});

it('allows admin to delete other admin', function () {
    $admin = createUserTestUser(id: 1, permissions: ['deleteUsers'], isAdmin: true);
    $adminTarget = createUserTestUser(id: 2, isAdmin: true);

    $result = $this->policy->delete($admin, $adminTarget);

    expect($result)->toBeTrue();
});

it('prevents users from being duplicated', function () {
    $user = createUserTestUser(id: 1, isAdmin: true);
    $targetUser = createUserTestUser(id: 2);

    $result = $this->policy->duplicate($user, $targetUser);

    expect($result)->toBeFalse();
});

it('prevents users from being copied', function () {
    $user = createUserTestUser(id: 1, isAdmin: true);
    $targetUser = createUserTestUser(id: 2);

    $result = $this->policy->copy($user, $targetUser);

    expect($result)->toBeFalse();
});

// Impersonate tests
it('allows admin to impersonate any user', function () {
    $admin = createUserTestUser(id: 1, isAdmin: true);
    $targetUser = createUserTestUser(id: 2);

    $result = $this->policy->impersonate($admin, $targetUser);

    expect($result)->toBeTrue();
});

it('allows admin to impersonate another admin', function () {
    $admin = createUserTestUser(id: 1, isAdmin: true);
    $targetAdmin = createUserTestUser(id: 2, isAdmin: true);

    $result = $this->policy->impersonate($admin, $targetAdmin);

    expect($result)->toBeTrue();
});

it('denies non-admin from impersonating an admin', function () {
    $user = createUserTestUser(id: 1, permissions: ['impersonateUsers']);
    $admin = createUserTestUser(id: 2, isAdmin: true);

    $result = $this->policy->impersonate($user, $admin);

    expect($result)->toBeFalse();
});

it('denies impersonate without permission', function () {
    $user = createUserTestUser(id: 1);
    $targetUser = createUserTestUser(id: 2);

    $result = $this->policy->impersonate($user, $targetUser);

    expect($result)->toBeFalse();
});

// Suspend tests
it('allows user with moderateUsers to suspend non-admin', function () {
    $moderator = createUserTestUser(id: 1, permissions: ['moderateUsers']);
    $targetUser = createUserTestUser(id: 2);

    $result = $this->policy->suspend($moderator, $targetUser);

    expect($result)->toBeTrue();
});

it('denies suspend without moderateUsers permission', function () {
    $user = createUserTestUser(id: 1);
    $targetUser = createUserTestUser(id: 2);

    $result = $this->policy->suspend($user, $targetUser);

    expect($result)->toBeFalse();
});

it('denies non-admin from suspending an admin', function () {
    $moderator = createUserTestUser(id: 1, permissions: ['moderateUsers']);
    $admin = createUserTestUser(id: 2, isAdmin: true);

    $result = $this->policy->suspend($moderator, $admin);

    expect($result)->toBeFalse();
});

it('allows admin to suspend another admin', function () {
    $admin = createUserTestUser(id: 1, isAdmin: true, permissions: ['moderateUsers']);
    $targetAdmin = createUserTestUser(id: 2, isAdmin: true);

    $result = $this->policy->suspend($admin, $targetAdmin);

    expect($result)->toBeTrue();
});

it('denies suspend when target has SSO identity', function () {
    $moderator = createUserTestUser(id: 1, permissions: ['moderateUsers']);
    $ssoUser = createUserTestUser(id: 2, hasSsoIdentity: true);

    $result = $this->policy->suspend($moderator, $ssoUser);

    expect($result)->toBeFalse();
});

// Helper function
function createUserTestUser(
    ?int $id = null,
    array $permissions = [],
    bool $isAdmin = false,
    bool $canRegister = false,
    bool $hasSsoIdentity = false,
): User {
    $user = new class extends User
    {
        public array $grantedPermissions = [];

        public bool $canRegister = false;

        public bool $hasSso = false;

        public function can($abilities, $arguments = []): bool
        {
            if (is_array($abilities)) {
                return array_all($abilities, fn ($ability) => $this->can($ability, $arguments));
            }

            return in_array($abilities, $this->grantedPermissions, true);
        }

        public function canRegisterUsers(): bool
        {
            return $this->canRegister;
        }

        public function getHasSsoIdentity(): bool
        {
            return $this->hasSso;
        }
    };

    $user->id = $id;
    $user->siteId = null;
    $user->admin = $isAdmin;
    $user->grantedPermissions = $permissions;
    $user->canRegister = $canRegister;
    $user->hasSso = $hasSsoIdentity;

    return $user;
}
