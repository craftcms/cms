<?php

declare(strict_types=1);

use CraftCms\Cms\Address\Elements\Address;

it('applies the address Form control’s nested fields', function () {
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
