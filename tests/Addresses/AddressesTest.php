<?php

declare(strict_types=1);

use CommerceGuys\Addressing\AddressFormat\AddressField;
use CommerceGuys\Addressing\AddressFormat\AddressFormatRepository;
use CommerceGuys\Addressing\AddressFormat\AdministrativeAreaType;
use CommerceGuys\Addressing\AddressFormat\LocalityType;
use CommerceGuys\Addressing\AddressFormat\PostalCodeType;
use CommerceGuys\Addressing\Country\CountryRepository;
use craft\models\FieldLayout;
use CraftCms\Cms\Address\Addresses;
use CraftCms\Cms\Address\Events\DefineAddressCountries;
use CraftCms\Cms\Address\Events\DefineAddressFieldLabel;
use CraftCms\Cms\Address\Events\DefineAddressSubdivisions;
use CraftCms\Cms\Address\Events\DefineAddressUsedFields;
use CraftCms\Cms\Address\Events\DefineAddressUsedSubdivisionFields;
use CraftCms\Cms\Address\Repositories\SubdivisionRepository;
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
    Event::listen(DefineAddressSubdivisions::class, function (DefineAddressSubdivisions $event) {
        $event->subdivisions = ['foo'];
    });

    expect($this->addresses->defineAddressSubdivisions([]))->toBe(['foo']);
});

it('can get the country list', function () {
    expect($this->addresses->getCountryList()['BE'])->toBe('Belgium');
});

it('can add countries through the event', function () {
    Event::listen(DefineAddressCountries::class, function (DefineAddressCountries $event) {
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
    Event::listen(DefineAddressUsedFields::class, function (DefineAddressUsedFields $event) {
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
    Event::listen(DefineAddressUsedSubdivisionFields::class, function (DefineAddressUsedSubdivisionFields $event) {
        $event->fields = ['changed'];
    });

    expect($this->addresses->getUsedSubdivisionFields('BE'))->toBe(['changed']);
});

it('can get a field label for a field and country code', function () {
    expect($this->addresses->getFieldLabel(AddressField::LOCALITY, 'BE'))->toBe('City');
});

it('can change the field label with an event', function () {
    Event::listen(DefineAddressFieldLabel::class, function (DefineAddressFieldLabel $event) {
        $event->label = 'foo';
    });

    expect($this->addresses->getFieldLabel(AddressField::LOCALITY, 'BE'))->toBe('foo');
});

it('can format an address')->todo('When Address Element is ported');

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

it('can save the fieldlayout')->todo('When Field layouts are ported');
it('can handle changed address field layout')->todo('When Field layouts are ported');
