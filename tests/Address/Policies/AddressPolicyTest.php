<?php

declare(strict_types=1);

use CraftCms\Cms\Address\Elements\Address;
use CraftCms\Cms\Address\Policies\AddressPolicy;
use CraftCms\Cms\User\Elements\User;
use Illuminate\Support\Facades\Gate;

beforeEach(function () {
    $this->policy = app(AddressPolicy::class);
});

it('is registered with the gate', function () {
    $address = new Address;
    $currentUser = createAddressTestUser(id: 1, permissions: ['viewUsers']);

    $result = Gate::forUser($currentUser)->allows('view', $address);

    expect($result)->toBeBool();
});

it('returns false without owner for view', function () {
    $user = createAddressTestUser(id: 1);
    $address = createAddressTestAddress(owner: null);

    $result = $this->policy->view($user, $address);

    expect($result)->toBeFalse();
});

it('returns false without owner for save', function () {
    $user = createAddressTestUser(id: 1);
    $address = createAddressTestAddress(owner: null);

    $result = $this->policy->save($user, $address);

    expect($result)->toBeFalse();
});

it('returns false without owner for delete', function () {
    $user = createAddressTestUser(id: 1);
    $address = createAddressTestAddress(owner: null);

    $result = $this->policy->delete($user, $address);

    expect($result)->toBeFalse();
});

it('create drafts always returns true', function () {
    $user = createAddressTestUser(id: 1);
    $address = createAddressTestAddress(owner: null);

    $result = $this->policy->createDrafts($user, $address);

    expect($result)->toBeTrue();
});

// Helper functions
function createAddressTestUser(?int $id = null, array $permissions = [], bool $isAdmin = false): User
{
    $user = new class extends User
    {
        public array $grantedPermissions = [];

        public function can($abilities, $arguments = []): bool
        {
            if (is_array($abilities)) {
                return array_all($abilities, fn ($ability) => $this->can($ability, $arguments));
            }

            return in_array($abilities, $this->grantedPermissions, true);
        }
    };

    $user->id = $id;
    $user->siteId = null;
    $user->admin = $isAdmin;
    $user->grantedPermissions = $permissions;

    return $user;
}

function createAddressTestAddress(?User $owner): Address
{
    $address = new class extends Address
    {
        public ?User $mockOwner = null;

        public function getOwner(): ?User
        {
            return $this->mockOwner;
        }
    };

    $address->siteId = null;
    $address->mockOwner = $owner;

    return $address;
}
