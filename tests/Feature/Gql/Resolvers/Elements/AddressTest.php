<?php

declare(strict_types=1);

use CraftCms\Cms\Address\Elements\Address as AddressElement;
use CraftCms\Cms\Address\Models\Address as AddressModel;
use CraftCms\Cms\Element\ElementCollection;
use CraftCms\Cms\Element\Queries\AddressQuery;
use CraftCms\Cms\Gql\Data\GqlSchema;
use CraftCms\Cms\Gql\Gql;
use CraftCms\Cms\Gql\Resolvers\Elements\Address;
use CraftCms\Cms\User\Elements\User as UserElement;
use CraftCms\Cms\User\Models\User as UserModel;
use CraftCms\Cms\User\Models\UserGroup;
use CraftCms\Cms\User\Users;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    actingAs(UserElement::findOne());
    app(Gql::class)->flushCaches();
});

it('returns empty collection when schema has no concrete user group access', function () {
    app(Gql::class)->setActiveSchema(new GqlSchema([
        'scope' => ['usergroups.everyone:read'],
    ]));

    $result = Address::prepareQuery(null, []);

    expect($result)->toBeInstanceOf(ElementCollection::class)
        ->and($result)->toBeEmpty();
});

it('returns an AddressQuery for top-level resolution when schema grants access to a user group', function () {
    $group = UserGroup::factory()->create();

    app(Gql::class)->setActiveSchema(new GqlSchema([
        'scope' => ["usergroups.{$group->uid}:read"],
    ]));

    $query = Address::prepareQuery(null, []);

    expect($query)->toBeInstanceOf(AddressQuery::class);
});

it('restricts top-level results to addresses owned by users in allowed groups', function () {
    $group = UserGroup::factory()->create();
    $allowedUser = UserModel::factory()->active()->createElement();
    $otherUser = UserModel::factory()->active()->createElement();

    app(Users::class)->assignUserToGroups($allowedUser->id, [$group->id]);

    $allowedAddress = AddressModel::factory()->create([
        'primaryOwnerId' => $allowedUser->id,
        'countryCode' => 'US',
    ]);
    AddressModel::factory()->create([
        'primaryOwnerId' => $otherUser->id,
        'countryCode' => 'CA',
    ]);

    app(Gql::class)->setActiveSchema(new GqlSchema([
        'scope' => ["usergroups.{$group->uid}:read"],
    ]));

    $query = Address::prepareQuery(null, []);

    $addresses = $query->all();

    expect($query)->toBeInstanceOf(AddressQuery::class)
        ->and(array_map(fn (AddressElement $address) => $address->id, $addresses))->toBe([$allowedAddress->id]);
});

it('applies arguments as method calls on the query', function () {
    $group = UserGroup::factory()->create();

    app(Gql::class)->setActiveSchema(new GqlSchema([
        'scope' => ["usergroups.{$group->uid}:read"],
    ]));

    $query = Address::prepareQuery(null, [
        'countryCode' => 'US',
    ]);

    expect($query)->toBeInstanceOf(AddressQuery::class)
        ->and($query->countryCode)->toBe('US');
});

it('returns preloaded data when source field is not an element query', function () {
    $preloaded = collect([
        (object) ['id' => 1],
        (object) ['id' => 2],
    ]);

    $source = new stdClass;
    $source->addresses = $preloaded;

    $result = Address::prepareQuery($source, [], 'addresses');

    expect($result)->toBe($preloaded);
});

it('reads from source field for relational resolution', function () {
    $source = new stdClass;
    $source->addresses = AddressElement::find();

    $result = Address::prepareQuery($source, [], 'addresses');

    expect($result)->toBeInstanceOf(AddressQuery::class);
});

it('ignores null argument values without throwing', function () {
    $group = UserGroup::factory()->create();

    app(Gql::class)->setActiveSchema(new GqlSchema([
        'scope' => ["usergroups.{$group->uid}:read"],
    ]));

    $query = Address::prepareQuery(null, [
        'nonExistentMethod' => null,
    ]);

    expect($query)->toBeInstanceOf(AddressQuery::class);
});

it('throws BadMethodCallException for invalid non-null argument', function () {
    $group = UserGroup::factory()->create();

    app(Gql::class)->setActiveSchema(new GqlSchema([
        'scope' => ["usergroups.{$group->uid}:read"],
    ]));

    Address::prepareQuery(null, [
        'nonExistentMethod' => 'some_value',
    ]);
})->throws(BadMethodCallException::class);
