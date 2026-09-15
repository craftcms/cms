<?php

use CraftCms\Cms\Address\Models\Address;
use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Element\Queries\AddressQuery;
use CraftCms\Cms\Entry\Models\Entry;
use CraftCms\Cms\Field\Addresses;
use CraftCms\Cms\Field\Models\Field;
use CraftCms\Cms\Support\Facades\Fields;
use Illuminate\Support\Facades\DB;

it('queries addresses', function () {
    Address::factory()->create();

    expect(new AddressQuery()->count())->toBe(1);
});

test('field(false) only returns addresses with no field', function () {
    $field = Field::factory()->create([
        'type' => Addresses::class,
    ]);

    Fields::refreshFields();

    $userAddress = Address::factory()->create([
        'primaryOwnerId' => Entry::factory()->create()->id,
    ]);

    $owner = Entry::factory()->create();
    $nested = Address::factory()->create([
        'primaryOwnerId' => $owner->id,
        'fieldId' => $field->id,
    ]);

    DB::table(Table::ELEMENTS_OWNERS)
        ->insert([
            'elementId' => $nested->id,
            'ownerId' => $owner->id,
            'sortOrder' => 1,
        ]);

    expect(new AddressQuery()->field(false)->ids())->toEqualCanonicalizing([$userAddress->id]);
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
