<?php

use CraftCms\Cms\Addresses\Models\Address;
use CraftCms\Cms\Database\Queries\AddressQuery;

it('queries addresses', function () {
    Address::factory()->create();

    expect(new AddressQuery()->count())->toBe(1);
});

it('queries by properties', function (string $property, mixed $value, mixed $param, int $expectedCount) {
    Address::factory()->create([$property => 'bar']);
    Address::factory()->create([$property => $value]);

    expect(new AddressQuery()->$property($param)->count())->toBe($expectedCount);
})->with([
    'countryCode',
    'administrativeArea',
    'locality',
    'dependentLocality',
    'postalCode',
    'sortingCode',
    'organization',
    'organizationTaxId',
    'addressLine1',
    'addressLine2',
    'addressLine3',
    'lastName',
    'firstName',
    'fullName',
])->with([
    ['foo', 'foo', 1],
    ['foo', 'f*', 1],
    ['foo', 'not foo', 1],
    ['foo', ['not',  'foo'], 1],
    ['foo', ['foo', 'bar'], 2],
]);
