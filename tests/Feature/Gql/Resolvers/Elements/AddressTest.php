<?php

declare(strict_types=1);

use CraftCms\Cms\Element\Queries\AddressQuery;
use CraftCms\Cms\Gql\Gql;
use CraftCms\Cms\Gql\Resolvers\Elements\Address;
use CraftCms\Cms\User\Elements\User;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    actingAs(User::findOne());
    app(Gql::class)->flushCaches();
    gqlActivateFullAccessSchema();
});

it('returns an AddressQuery for top-level resolution', function () {
    $query = Address::prepareQuery(null, []);

    expect($query)->toBeInstanceOf(AddressQuery::class);
});

it('applies arguments as method calls on the query', function () {
    $query = Address::prepareQuery(null, [
        'countryCode' => 'US',
    ]);

    expect($query)->toBeInstanceOf(AddressQuery::class);
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

it('ignores null argument values without throwing', function () {
    $query = Address::prepareQuery(null, [
        'countryCode' => null,
    ]);

    expect($query)->toBeInstanceOf(AddressQuery::class);
});

it('throws BadMethodCallException for invalid non-null argument', function () {
    Address::prepareQuery(null, [
        'nonExistentMethod' => 'some_value',
    ]);
})->throws(BadMethodCallException::class);
