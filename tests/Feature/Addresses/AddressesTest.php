<?php

declare(strict_types=1);

use CommerceGuys\Addressing\AddressFormat\AddressField;
use CommerceGuys\Addressing\AddressFormat\AddressFormatRepository;
use CommerceGuys\Addressing\AddressFormat\AdministrativeAreaType;
use CommerceGuys\Addressing\AddressFormat\LocalityType;
use CommerceGuys\Addressing\AddressFormat\PostalCodeType;
use CommerceGuys\Addressing\Country\CountryRepository;
use CraftCms\Cms\Address\Addresses;
use CraftCms\Cms\Address\Elements\Address;
use CraftCms\Cms\Address\Events\AddressCountriesResolving;
use CraftCms\Cms\Address\Events\AddressFieldLabelResolving;
use CraftCms\Cms\Address\Events\AddressSubdivisionsResolving;
use CraftCms\Cms\Address\Events\AddressUsedFieldsResolving;
use CraftCms\Cms\Address\Events\AddressUsedSubdivisionFieldsResolving;
use CraftCms\Cms\Address\Models\Address as AddressModel;
use CraftCms\Cms\Address\Repositories\SubdivisionRepository;
use CraftCms\Cms\Field\Fields;
use CraftCms\Cms\FieldLayout\FieldLayout;
use CraftCms\Cms\FieldLayout\FieldLayoutTab;
use CraftCms\Cms\FieldLayout\LayoutElements\Addresses\LabelField;
use CraftCms\Cms\ProjectConfig\Events\ItemUpdated;
use CraftCms\Cms\ProjectConfig\ProjectConfig;
use CraftCms\Cms\Support\Str;
use Illuminate\Support\Facades\Event;

beforeEach(function () {
    $this->addresses = app(Addresses::class);
});

it('is a singleton', function () {
    expect($this->addresses)->toBe(app(Addresses::class));
});

it('can get the country repository', function () {
    expect($this->addresses->getCountryRepository())->toBeInstanceOf(CountryRepository::class);
});

it('can get the subdivision repository', function () {
    expect($this->addresses->getSubdivisionRepository())->toBeInstanceOf(SubdivisionRepository::class);
});

it('can get the address format repository', function () {
    expect($this->addresses->getAddressFormatRepository())->toBeInstanceOf(AddressFormatRepository::class);
});

it('can define address subdivisions with an event', function () {
    Event::listen(AddressSubdivisionsResolving::class, function (AddressSubdivisionsResolving $event) {
        $event->subdivisions = ['foo'];
    });

    expect($this->addresses->defineAddressSubdivisions([]))->toBe(['foo']);
});

it('can get the country list', function () {
    expect($this->addresses->getCountryList()['BE'])->toBe('Belgium');
});

it('can add countries through the event', function () {
    Event::listen(AddressCountriesResolving::class, function (AddressCountriesResolving $event) {
        $event->countries['ME'] = 'Middle Earth';
    });

    expect($this->addresses->getCountryList()['ME'])->toBe('Middle Earth');
});

it('can get used fields for a country code', function () {
    expect($this->addresses->getUsedFields('BE'))->toContain(
        AddressField::LOCALITY,
        AddressField::POSTAL_CODE,
        AddressField::ADDRESS_LINE1,
    );
});

it('can change the used fields with an event', function () {
    Event::listen(AddressUsedFieldsResolving::class, function (AddressUsedFieldsResolving $event) {
        $event->fields = ['changed'];
    });

    expect($this->addresses->getUsedFields('BE'))->toBe(['changed']);
});

it('can get used subdivision fields for a country code', function () {
    expect($this->addresses->getUsedSubdivisionFields('BE'))->toContain(
        AddressField::LOCALITY,
    );
});

it('can change the used subdivisionfields with an event', function () {
    Event::listen(AddressUsedSubdivisionFieldsResolving::class, function (AddressUsedSubdivisionFieldsResolving $event) {
        $event->fields = ['changed'];
    });

    expect($this->addresses->getUsedSubdivisionFields('BE'))->toBe(['changed']);
});

it('can get a field label for a field and country code', function () {
    expect($this->addresses->getFieldLabel(AddressField::LOCALITY, 'BE'))->toBe('City');
});

it('can change the field label with an event', function () {
    Event::listen(AddressFieldLabelResolving::class, function (AddressFieldLabelResolving $event) {
        $event->label = 'foo';
    });

    expect($this->addresses->getFieldLabel(AddressField::LOCALITY, 'BE'))->toBe('foo');
});

it('can format an address', function () {
    $address = AddressModel::factory()->createElement([
        'countryCode' => 'US',
        'administrativeArea' => 'NY',
        'locality' => 'New York',
        'postalCode' => '10001',
        'addressLine1' => '20 W 34th St.',
    ]);

    expect($this->addresses->formatAddress($address))->toContain(
        '20 W 34th St.',
        'New York',
        'NY',
        '10001',
        'United States',
    );
});

it('can get a locality type label', function () {
    expect($this->addresses->getLocalityTypeLabel(LocalityType::DISTRICT))->toBe('District');
});

it('can get a dependent locality type label', function () {
    expect($this->addresses->getDependentLocalityTypeLabel(LocalityType::DISTRICT))->toBe('District');
});

it('can get a postal code type label', function () {
    expect($this->addresses->getPostalCodeTypeLabel(PostalCodeType::EIR))->toBe('Eircode');
});

it('can get an administrative area type label', function () {
    expect($this->addresses->getAdministrativeAreaTypeLabel(AdministrativeAreaType::PARISH))->toBe('Parish');
});

it('can get the fieldlayout', function () {
    expect($this->addresses->getFieldLayout())->toBeInstanceOf(FieldLayout::class);
});

it('can save the fieldlayout', function () {
    $layout = new FieldLayout([
        'uid' => Str::uuid()->toString(),
        'type' => Address::class,
        'tabs' => [
            [
                'uid' => Str::uuid()->toString(),
                'name' => 'Content',
                'elements' => [
                    [
                        'uid' => Str::uuid()->toString(),
                        'type' => LabelField::class,
                    ],
                ],
            ],
        ],
    ]);

    expect($this->addresses->saveFieldLayout($layout))->toBeTrue()
        ->and(app(Fields::class)->getLayoutByType(Address::class, false)?->uid)->toBe($layout->uid);
});

it('can handle changed address field layout', function () {
    $layout = $this->addresses->getFieldLayout();
    $layout->uid ??= Str::uuid()->toString();
    $layout->setTabs([
        new FieldLayoutTab([
            'layout' => $layout,
            'uid' => Str::uuid()->toString(),
            'name' => 'Changed',
        ]),
    ]);

    $this->addresses->handleChangedAddressFieldLayout(new ItemUpdated(
        path: ProjectConfig::PATH_ADDRESS_FIELD_LAYOUTS,
        newValue: [$layout->uid => $layout->getConfig()],
    ));

    expect(app(Fields::class)->getLayoutByType(Address::class, false)?->getTabs()[0]->name)->toBe('Changed');
});
