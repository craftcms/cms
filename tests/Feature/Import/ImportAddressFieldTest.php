<?php

declare(strict_types=1);

use CraftCms\Cms\Address\Elements\Address;
use CraftCms\Cms\Entry\Elements\Entry as EntryElement;
use CraftCms\Cms\Field\Addresses as AddressesField;
use CraftCms\Cms\Field\Models\Field;
use CraftCms\Cms\FieldLayout\LayoutElements\CustomField;
use CraftCms\Cms\Import\Import;
use CraftCms\Cms\Import\Importers\ElementImporter;
use CraftCms\Cms\Support\Facades\Fields;
use CraftCms\Cms\Support\Facades\Sites;
use CraftCms\Cms\Tests\Support\ImportFixtures;

beforeEach(function () {
    $this->import = app(Import::class);

    $addressesField = Field::factory()->create([
        'name' => 'My Addresses',
        'handle' => 'myAddresses',
        'type' => AddressesField::class,
    ]);

    Fields::refreshFields();

    $seed = ImportFixtures::seedEntry(
        [CustomField::make($addressesField->handle)],
        ['name' => 'With Addresses Field', 'handle' => 'withAddressesField'],
        entryAttrs: ['title' => 'some entry', 'slug' => 'some-entry'],
    );

    $this->section = $seed->section;
    $this->entryType = $seed->entryType;

    $this->importer = ElementImporter::create()
        ->className(EntryElement::class)
        ->site(Sites::getPrimarySite()->handle)
        ->transformer(null);

    $this->entryData = fn (array $addresses) => [
        'title' => 'imported entry',
        'sectionId' => $this->section->handle,
        'typeId' => $this->entryType->handle,
        'myAddresses' => $addresses,
        'matchCriteria' => ['title' => 'title'],
    ];

    $this->address = [
        'title' => 'address 1',
        'countryCode' => 'US',
        'addressLine1' => '123 Main St',
        'addressLine2' => 'Apt 4',
        'administrativeArea' => 'UT',
        'postalCode' => '12345',
        'locality' => 'My Town',
    ];
});

it('imports an entry with an addresses field', function () {
    $this->import->importItem($this->importer, ($this->entryData)([$this->address]));

    $entry = EntryElement::find()->title('imported entry')->one();

    expect($entry)->not()->toBeNull();
    expect(Address::find()->ownerId($entry->id)->count())->toBe(1);
});

it('imports multiple addresses into the addresses field', function () {
    $second = array_merge($this->address, ['title' => 'address 2', 'addressLine1' => '456 Elm St']);

    $this->import->importItem($this->importer, ($this->entryData)([$this->address, $second]));

    $entry = EntryElement::find()->title('imported entry')->one();

    expect(Address::find()->ownerId($entry->id)->count())->toBe(2);
});

it('maps native address fields correctly', function () {
    $this->import->importItem($this->importer, ($this->entryData)([$this->address]));

    $entry = EntryElement::find()->title('imported entry')->one();
    $address = Address::find()->ownerId($entry->id)->one();

    expect($address->countryCode)->toBe($this->address['countryCode'])
        ->and($address->addressLine1)->toBe($this->address['addressLine1'])
        ->and($address->addressLine2)->toBe($this->address['addressLine2'])
        ->and($address->administrativeArea)->toBe($this->address['administrativeArea'])
        ->and($address->postalCode)->toBe($this->address['postalCode'])
        ->and($address->locality)->toBe($this->address['locality'])
        ->and($address->title)->toBe($this->address['title']);
});

it('updates an existing address when match criteria matches', function () {
    $importer = (clone $this->importer)->matchCriteria(['title' => 'title']);

    $addressWithCriteria = array_merge($this->address, [
        'matchCriteria' => ['title' => 'title'],
    ]);

    $this->import->importItem($importer, ($this->entryData)([$addressWithCriteria]));

    $entry = EntryElement::find()->title('imported entry')->one();
    expect(Address::find()->ownerId($entry->id)->count())->toBe(1);
    $addressId = Address::find()->ownerId($entry->id)->one()->id;

    $addressLine1Updated = '999 Updated Ave';

    $updated = array_merge($addressWithCriteria, ['addressLine1' => $addressLine1Updated]);
    $this->import->importItem($importer, ($this->entryData)([$updated]));

    $address = Address::find()->ownerId($entry->id)->one();

    expect(Address::find()->ownerId($entry->id)->count())->toBe(1)
        // without the id check this passes whether the address is matched or recreated
        ->and($address->id)->toBe($addressId)
        ->and($address->addressLine1)->toBe($addressLine1Updated);
});

it('creates a new address when match criteria does not match any existing address', function () {
    $importer = (clone $this->importer)->matchCriteria(['title' => 'title']);

    $firstAddress = array_merge($this->address, [
        'matchCriteria' => ['title' => 'title'],
    ]);

    $this->import->importItem($importer, ($this->entryData)([$firstAddress]));

    $entry = EntryElement::find()->title('imported entry')->one();
    expect(Address::find()->ownerId($entry->id)->count())->toBe(1);

    // second import: different address title, so matchCriteria finds no match → creates new address
    $newAddress = array_merge($this->address, [
        'title' => 'address 2',
        'addressLine1' => '456 Elm St',
        'matchCriteria' => ['title' => 'title'],
    ]);
    $this->import->importItem($importer, ($this->entryData)([$firstAddress, $newAddress]));

    expect(Address::find()->ownerId($entry->id)->count())->toBe(2);
});

// the manual fixture supplies latitude/longitude as strings inside a nested latLong object
it('ignores a latLong value when the address layout has no lat/long field', function () {
    $this->import->importItem($this->importer, ($this->entryData)([
        [...$this->address, 'latLong' => ['latitude' => '37.7749', 'longitude' => '-122.4194']],
    ]));

    $entry = EntryElement::find()->title('imported entry')->one();
    $address = Address::find()->ownerId($entry->id)->one();

    expect($address)->not->toBeNull()
        ->and($address->addressLine1)->toBe($this->address['addressLine1'])
        ->and($address->latitude)->toBeNull()
        ->and($address->longitude)->toBeNull();
});

it('imports latLong given as a nested object of strings', function () {
    $this->import->importItem($this->importer, ($this->entryData)([
        [...$this->address, 'latLong' => ['latitude' => '37.7749', 'longitude' => '-122.4194']],
    ]));

    $entry = EntryElement::find()->title('imported entry')->one();
    $address = Address::find()->ownerId($entry->id)->one();

    expect($address->latitude)->toEqual('37.7749')
        ->and($address->longitude)->toEqual('-122.4194');
})->skip('needs an address field layout containing a LatLongField (as the real project config has); the default test layout has none, and swapping in a layout with one makes every address fail validation');

// the manual fixture sends "country", while the element exposes countryCode - this pins which one
// the Import actually reads
it('reads the country code from countryCode, not from a country key', function () {
    $address = $this->address;
    unset($address['countryCode']);

    $this->import->importItem($this->importer, ($this->entryData)([[...$address, 'country' => 'GB']]));

    $entry = EntryElement::find()->title('imported entry')->one();
    $imported = Address::find()->ownerId($entry->id)->one();

    expect($imported)->not->toBeNull()
        ->and($imported->countryCode)->not->toBe('GB');
});

// Nested criteria supplied by the importer config rather than inlined in each row: the config's
// criteria for the container is what a type-less row resolves against.
it('matches an address using nested match criteria from the importer config', function () {
    $importer = (clone $this->importer)->matchCriteria([
        'title' => 'title',
        'myAddresses' => ['title' => 'title'],
    ]);

    ImportFixtures::importWithConfigCriteria($this->import, $importer, ($this->entryData)([$this->address]));

    $entry = EntryElement::find()->title('imported entry')->one();
    $addressId = Address::find()->ownerId($entry->id)->one()->id;

    ImportFixtures::importWithConfigCriteria($this->import, $importer, ($this->entryData)([
        [...$this->address, 'addressLine1' => '999 Updated Ave'],
    ]));

    $address = Address::find()->ownerId($entry->id)->one();

    expect(Address::find()->ownerId($entry->id)->count())->toBe(1)
        ->and($address->id)->toBe($addressId)
        ->and($address->addressLine1)->toBe('999 Updated Ave');
});

// clearableItems declared under an addresses field has to reach the type-less rows too
it('clears a native address field marked clearable when it is missing from a later import', function () {
    $importer = (clone $this->importer)
        ->matchCriteria(['title' => 'title'])
        ->clearableItems(['myAddresses' => ['addressLine2' => true]]);

    $addressWithCriteria = [...$this->address, 'matchCriteria' => ['title' => 'title']];

    $this->import->importItem($importer, ($this->entryData)([$addressWithCriteria]));

    $entry = EntryElement::find()->title('imported entry')->one();
    $address = Address::find()->ownerId($entry->id)->one();
    expect($address->addressLine2)->toBe($this->address['addressLine2']);
    $addressId = $address->id;

    $withoutLine2 = $addressWithCriteria;
    unset($withoutLine2['addressLine2']);

    $this->import->importItem($importer, ($this->entryData)([$withoutLine2]));

    $address = Address::find()->ownerId($entry->id)->one();

    expect($address->id)->toBe($addressId)
        ->and($address->addressLine2)->toBeNull();
});

// a row that only says how to match, with no address content, isn't an address at all
it('creates no address for a row that carries only match criteria', function () {
    $this->import->importItem($this->importer, ($this->entryData)([
        ['matchCriteria' => ['title' => 'title']],
    ]));

    $entry = EntryElement::find()->title('imported entry')->one();

    expect($entry)->not->toBeNull()
        ->and(Address::find()->ownerId($entry->id)->count())->toBe(0);
});

it('ignores a criteria-only row while importing the real ones', function () {
    $this->import->importItem($this->importer, ($this->entryData)([
        $this->address,
        ['matchCriteria' => ['title' => 'title']],
    ]));

    $entry = EntryElement::find()->title('imported entry')->one();
    $addresses = Address::find()->ownerId($entry->id)->all();

    expect($addresses)->toHaveCount(1)
        ->and($addresses[0]->title)->toBe($this->address['title'])
        ->and($addresses[0]->addressLine1)->toBe($this->address['addressLine1']);
});
