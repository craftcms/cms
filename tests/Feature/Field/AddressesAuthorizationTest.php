<?php

declare(strict_types=1);

use CraftCms\Cms\Address\Elements\Address;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Field\Addresses;
use CraftCms\Cms\User\Models\User as UserModel;
use Illuminate\Support\Facades\Gate;

function addressOwner(int $id, bool $revision = false): Entry
{
    $owner = Mockery::mock(Entry::class)->makePartial();
    $owner->id = $id;
    $owner->allows('getIsRevision')->andReturn($revision);

    return $owner;
}

/** @param list<Entry> $owners */
function ownedAddress(Entry $owner, array $owners): Address
{
    $address = Mockery::mock(Address::class)->makePartial();
    $address->id = 10;
    $address->allows('getOwner')->andReturn($owner);
    $address->allows('getOwners')->andReturn($owners);

    return $address;
}

/** Lets the user save only the elements with these IDs. */
function canSaveOnly(int ...$ids): void
{
    Gate::before(fn ($user, string $ability, array $arguments) => $ability === 'save' && ($arguments[0] ?? null) instanceof ElementInterface
        ? in_array($arguments[0]->id, $ids, true)
        : null);
}

beforeEach(function () {
    $this->user = UserModel::factory()->createElement(['admin' => false]);
});

// #19881: authors who can save a draft, but not the canonical owner, can still edit its addresses.
it('lets a user who can save another owner, such as a draft, save the address', function () {
    $canonical = addressOwner(1);
    canSaveOnly(2);

    $address = ownedAddress($canonical, [$canonical, addressOwner(2)]);

    expect((new Addresses)->canSaveElement($address, $this->user))->toBeTrue();
});

it('denies saving an address when the user can save none of its owners', function () {
    $canonical = addressOwner(1);
    canSaveOnly(3);

    $address = ownedAddress($canonical, [$canonical, addressOwner(2)]);

    expect((new Addresses)->canSaveElement($address, $this->user))->toBeFalse();
});

it('denies saving an address owned by a revision the user can’t save', function () {
    $revision = addressOwner(1, revision: true);
    canSaveOnly(2);

    $address = ownedAddress($revision, [$revision, addressOwner(2)]);

    expect((new Addresses)->canSaveElement($address, $this->user))->toBeFalse();
});
