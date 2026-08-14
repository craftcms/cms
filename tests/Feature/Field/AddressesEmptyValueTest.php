<?php

declare(strict_types=1);

use CraftCms\Cms\Entry\Models\Entry as EntryModel;
use CraftCms\Cms\Field\Addresses;

function addressesEmptyValueField(): array
{
    $result = EntryModel::factory()
        ->withField('addressesField', Addresses::class, [], value: '')
        ->createElementWithFields(save: false);

    /** @var Addresses $field */
    $field = $result->element->getFieldLayout()->getFieldByHandle('addressesField');

    return [$field, $result->element];
}

test('an empty request value clears all addresses', function () {
    [$field, $element] = addressesEmptyValueField();

    // An empty POST value arrives as null (Laravel's ConvertEmptyStringsToNull
    // middleware), and means every address was removed.
    expect($field->normalizeValueFromRequest(null, $element)->getResultOverride())->toBe([])
        ->and($field->normalizeValueFromRequest('', $element)->getResultOverride())->toBe([])
        // Outside a request, null keeps lazy-loading the stored addresses.
        ->and($field->normalizeValue(null, $element)->getResultOverride())->toBeNull();
});

test('an empty delta value clears all addresses', function () {
    [$field, $element] = addressesEmptyValueField();

    // What the Matrix form control posts once every address has been removed.
    $value = ['entries' => [], 'sortOrder' => []];

    expect($field->normalizeValueFromRequest($value, $element)->getResultOverride())->toBe([]);
});

test('delta values are keyed by UUID rather than treated as addresses', function () {
    [$field, $element] = addressesEmptyValueField();

    $uid = '369f71c3-873f-4842-8fe9-90641773b62b';

    $value = [
        'entries' => [
            "uid:$uid" => [
                'type' => 'address',
                'countryCode' => 'US',
                'title' => 'Home',
                'address' => [
                    'addressLine1' => '123 Fake St.',
                    'locality' => 'Chicago',
                    'administrativeArea' => 'IL',
                    'postalCode' => '60641',
                ],
            ],
        ],
        'sortOrder' => ["uid:$uid"],
    ];

    $addresses = $field->normalizeValueFromRequest($value, $element)->getResultOverride();

    expect($addresses)->toHaveCount(1)
        ->and($addresses[0]->uid)->toBe($uid)
        ->and($addresses[0]->title)->toBe('Home')
        ->and($addresses[0]->countryCode)->toBe('US')
        // The Address form control nests the address format fields under `address`
        ->and($addresses[0]->addressLine1)->toBe('123 Fake St.')
        ->and($addresses[0]->locality)->toBe('Chicago')
        ->and($addresses[0]->administrativeArea)->toBe('IL')
        ->and($addresses[0]->postalCode)->toBe('60641');
});

test('the legacy flat value format still creates addresses', function () {
    [$field, $element] = addressesEmptyValueField();

    $value = [
        'new1' => [
            'title' => 'Home',
            'countryCode' => 'US',
            'addressLine1' => '123 Fake St.',
        ],
    ];

    $addresses = $field->normalizeValueFromRequest($value, $element)->getResultOverride();

    expect($addresses)->toHaveCount(1)
        ->and($addresses[0]->title)->toBe('Home')
        ->and($addresses[0]->addressLine1)->toBe('123 Fake St.');
});
