<?php

declare(strict_types=1);

use CraftCms\Cms\Address\Elements\Address;
use CraftCms\Cms\Entry\Elements\Entry as EntryElement;
use CraftCms\Cms\Entry\Models\Entry;
use CraftCms\Cms\Entry\Models\EntryType;
use CraftCms\Cms\Field\Addresses as AddressesField;
use CraftCms\Cms\Field\Models\Field;
use CraftCms\Cms\FieldLayout\LayoutElements\CustomField;
use CraftCms\Cms\FieldLayout\LayoutElements\Entries\EntryTitleField;
use CraftCms\Cms\FieldLayout\Models\FieldLayout;
use CraftCms\Cms\Import\Import;
use CraftCms\Cms\Import\Importers\ElementImporter;
use CraftCms\Cms\Section\Models\Section;
use CraftCms\Cms\Support\Facades\Sites;
use CraftCms\Cms\Support\Str;

beforeEach(function () {
    $this->import = app(Import::class);

    $addressesField = Field::factory()->create([
        'name' => 'My Addresses',
        'handle' => 'myAddresses',
        'type' => AddressesField::class,
    ]);

    Fields::refreshFields();

    $fieldLayout = FieldLayout::factory()
        ->withContentTab([
            new EntryTitleField(['uid' => Str::uuid()->toString(), 'required' => true]),
            CustomField::make($addressesField->handle),
        ])
        ->create();

    $entryType = EntryType::factory()
        ->withFieldLayout($fieldLayout)
        ->create([
            'name' => 'With Addresses Field',
            'handle' => 'withAddressesField',
            'hasTitleField' => true,
        ]);

    $section = Section::factory()->withEntryTypes($entryType)->create(['minAuthors' => 0]);

    $result = Entry::factory()
        ->forSection($section)
        ->forEntryType($entryType)
        ->withFieldLayout($fieldLayout)
        ->createElementWithFields([
            'title' => 'some entry',
            'slug' => 'some-entry',
        ]);

    $this->section = $result->element->getSection();
    $this->entryType = $result->element->getType();

    $this->importer = ElementImporter::create()
        ->className(EntryElement::class)
        ->site(Sites::getPrimarySite()->handle)
        ->transformer(null);

    $this->entryData = fn (array $addresses) => [
        'title' => 'imported entry',
        'sectionId' => $this->section->handle,
        'typeId' => $this->entryType->handle,
        'myAddresses' => $addresses,
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

    $addressLine1Updated = '999 Updated Ave';

    $updated = array_merge($addressWithCriteria, ['addressLine1' => $addressLine1Updated]);
    $this->import->importItem($importer, ($this->entryData)([$updated]));

    expect(Address::find()->ownerId($entry->id)->count())->toBe(1);
    expect(Address::find()->ownerId($entry->id)->one()->addressLine1)->toBe($addressLine1Updated);
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
