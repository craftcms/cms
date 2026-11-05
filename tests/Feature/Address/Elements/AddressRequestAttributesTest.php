<?php

declare(strict_types=1);

use CraftCms\Cms\Address\Elements\Address;

it('applies the address UI control’s nested fields', function () {
    $address = new Address;

    $address->setAttributesFromRequest([
        'title' => 'Home',
        'countryCode' => 'US',
        'address' => [
            'addressLine1' => '1 QA Street',
            'administrativeArea' => 'OR',
            'locality' => 'Portland',
            'postalCode' => '97201',
        ],
    ]);

    expect($address->title)->toBe('Home')
        ->and($address->addressLine1)->toBe('1 QA Street')
        ->and($address->administrativeArea)->toBe('OR')
        ->and($address->locality)->toBe('Portland')
        ->and($address->postalCode)->toBe('97201');
});

it('prefers top-level attributes over the nested address fields', function () {
    $address = new Address;

    $address->setAttributesFromRequest([
        'locality' => 'Salem',
        'address' => ['locality' => 'Portland'],
    ]);

    expect($address->locality)->toBe('Salem');
});

it('drops a province from the previous country’s list when the country changes', function () {
    // Autofill can fill a new country and a province chosen from the old country's list together.
    $address = new Address(['countryCode' => 'US']);

    $address->setAttributesFromRequest([
        'countryCode' => 'CA',
        'address' => ['administrativeArea' => 'AR'],
    ]);

    expect($address->countryCode)->toBe('CA')
        ->and($address->administrativeArea)->toBeNull();
});

it('keeps a province that belongs to the new country', function () {
    $address = new Address(['countryCode' => 'US']);

    $address->setAttributesFromRequest([
        'countryCode' => 'CA',
        'address' => ['administrativeArea' => 'NS'],
    ]);

    expect($address->administrativeArea)->toBe('NS');
});

it('keeps an unrecognized province when the country is unchanged', function () {
    $address = new Address(['countryCode' => 'CA', 'administrativeArea' => 'XX']);

    $address->setAttributesFromRequest([
        'countryCode' => 'CA',
        'address' => ['administrativeArea' => 'XX'],
    ]);

    expect($address->administrativeArea)->toBe('XX');
});
