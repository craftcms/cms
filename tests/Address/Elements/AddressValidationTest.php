<?php

declare(strict_types=1);

use CraftCms\Cms\Address\Elements\Address;
use CraftCms\Cms\Address\Models\Address as AddressModel;
use CraftCms\Cms\Cms;

describe('Country code format validation', function () {
    test('valid country codes are accepted', function (string $code) {
        $address = AddressModel::factory()->createElement();
        $address->countryCode = $code;

        $address->validate(['countryCode']);

        expect($address->hasErrors('countryCode'))->toBeFalse();
    })->with([
        ['US'],
        ['BE'],
        ['GB'],
        ['DE'],
        ['FR'],
        ['NL'],
        ['CA'],
        ['AU'],
    ]);

    test('invalid country codes are rejected', function (string $code) {
        $address = AddressModel::factory()->createElement(['countryCode' => 'US']);
        $address->countryCode = $code;

        $address->validate(['countryCode']);

        expect($address->hasErrors('countryCode'))->toBeTrue();
    })->with([
        'XX is invalid' => ['XX'],
        'lowercase us is invalid' => ['us'],
    ]);
});

describe('String trimming validation', function () {
    test('string fields are trimmed', function (string $field, string $input, string $expected, ?string $countryCode = null) {
        $address = AddressModel::factory()->createElement(['countryCode' => $countryCode ?? 'US']);
        $address->{$field} = $input;

        $address->validate([$field]);

        expect($address->{$field})->toBe($expected);
    })->with([
        'countryCode is trimmed' => ['countryCode', '  US  ', 'US'],
        'addressLine1 is trimmed' => ['addressLine1', '  123 Main St  ', '123 Main St'],
        'addressLine2 is trimmed' => ['addressLine2', '  Apt 4  ', 'Apt 4'],
        'addressLine3 is trimmed' => ['addressLine3', '  Building C  ', 'Building C'],
        'locality is trimmed' => ['locality', '  New York  ', 'New York'],
        'administrativeArea is trimmed' => ['administrativeArea', '  NY  ', 'NY', 'US'],
        'organization is trimmed' => ['organization', '  Acme Inc  ', 'Acme Inc'],
        'fullName is trimmed' => ['fullName', '  John Doe  ', 'John Doe'],
    ]);

    test('postalCode is trimmed', function () {
        $address = AddressModel::factory()->createElement(['countryCode' => 'US']);
        $address->postalCode = '  10001  ';

        $address->validate(['postalCode']);

        expect($address->postalCode)->toBe('10001');
    });
});

describe('String max length validation (255 chars)', function () {
    test('string fields validate max length', function (string $field, string $char, int $length, bool $expectError) {
        $address = AddressModel::factory()->createElement(['countryCode' => 'US']);
        $address->{$field} = str_repeat($char, $length);

        $address->validate([$field]);

        expect($address->hasErrors($field))->toBe($expectError);
    })->with([
        'addressLine1 accepts 255 chars' => ['addressLine1', 'a', 255, false],
        'addressLine1 rejects 256 chars' => ['addressLine1', 'a', 256, true],
        'addressLine2 accepts 255 chars' => ['addressLine2', 'a', 255, false],
        'addressLine2 rejects 256 chars' => ['addressLine2', 'a', 256, true],
        'locality accepts 255 chars' => ['locality', 'a', 255, false],
        'locality rejects 256 chars' => ['locality', 'a', 256, true],
        'postalCode accepts 255 chars' => ['postalCode', '1', 255, false],
        'postalCode rejects 256 chars' => ['postalCode', '1', 256, true],
        'organization accepts 255 chars' => ['organization', 'a', 255, false],
        'organization rejects 256 chars' => ['organization', 'a', 256, true],
        'fullName accepts 255 chars' => ['fullName', 'a', 255, false],
        'fullName rejects 256 chars' => ['fullName', 'a', 256, true],
    ]);
});

describe('Latitude validation', function () {
    test('latitude accepts valid values', function (mixed $value, bool $expectError) {
        $address = AddressModel::factory()->createElement();
        $address->latitude = $value;

        $address->validate(['latitude']);

        expect($address->hasErrors('latitude'))->toBe($expectError);
    })->with([
        'positive value 45.5 is valid' => ['45.5', false],
        'negative value -45.5 is valid' => ['-45.5', false],
        'zero is valid' => ['0', false],
        'exactly 90 is valid' => ['90', false],
        'exactly -90 is valid' => ['-90', false],
        'greater than 90 is invalid' => ['91', true],
        'less than -90 is invalid' => ['-91', true],
        'null is valid' => [null, false],
    ]);
});

describe('Longitude validation', function () {
    test('longitude accepts valid values', function (mixed $value, bool $expectError) {
        $address = AddressModel::factory()->createElement();
        $address->longitude = $value;

        $address->validate(['longitude']);

        expect($address->hasErrors('longitude'))->toBe($expectError);
    })->with([
        'positive value 120.5 is valid' => ['120.5', false],
        'negative value -120.5 is valid' => ['-120.5', false],
        'zero is valid' => ['0', false],
        'exactly 180 is valid' => ['180', false],
        'exactly -180 is valid' => ['-180', false],
        'greater than 180 is invalid' => ['181', true],
        'less than -180 is invalid' => ['-181', true],
        'null is valid' => [null, false],
    ]);
});

describe('Country-specific required fields (SCENARIO_LIVE)', function () {
    test('US address required fields on SCENARIO_LIVE', function (string $field, bool $expectError) {
        $address = AddressModel::factory()->createElement(['countryCode' => 'US']);
        $address->setScenario(Address::SCENARIO_LIVE);
        $address->{$field} = null;

        $address->validate([$field]);

        expect($address->hasErrors($field))->toBe($expectError);
    })->with([
        'locality is required' => ['locality', true],
        'administrativeArea is required' => ['administrativeArea', true],
        'postalCode is required' => ['postalCode', true],
        'addressLine1 is required' => ['addressLine1', true],
    ]);

    test('BE address required fields on SCENARIO_LIVE', function (string $field, bool $expectError) {
        $address = AddressModel::factory()->createElement(['countryCode' => 'BE']);
        $address->setScenario(Address::SCENARIO_LIVE);
        $address->{$field} = null;

        $address->validate([$field]);

        expect($address->hasErrors($field))->toBe($expectError);
    })->with([
        'locality is required' => ['locality', true],
        'postalCode is required' => ['postalCode', true],
        'administrativeArea is not required' => ['administrativeArea', false],
    ]);

    test('fields not required by country pass validation on SCENARIO_LIVE', function () {
        $address = AddressModel::factory()->createElement(['countryCode' => 'US']);
        $address->setScenario(Address::SCENARIO_LIVE);
        $address->dependentLocality = null;
        $address->sortingCode = null;

        $address->validate(['dependentLocality', 'sortingCode']);

        expect($address->hasErrors('dependentLocality'))->toBeFalse();
        expect($address->hasErrors('sortingCode'))->toBeFalse();
    });

    test('required fields are not required on default scenario', function () {
        $address = AddressModel::factory()->createElement(['countryCode' => 'US']);
        $address->locality = null;
        $address->administrativeArea = null;
        $address->postalCode = null;
        $address->addressLine1 = null;

        $address->validate(['locality', 'administrativeArea', 'postalCode', 'addressLine1']);

        expect($address->hasErrors('locality'))->toBeFalse();
        expect($address->hasErrors('administrativeArea'))->toBeFalse();
        expect($address->hasErrors('postalCode'))->toBeFalse();
        expect($address->hasErrors('addressLine1'))->toBeFalse();
    });
});

describe('Safe attribute validation', function () {
    test('firstName is a safe attribute when config enabled', function () {
        Cms::config()->showFirstAndLastNameFields = true;

        $address = AddressModel::factory()->createElement();
        $address->firstName = 'John';

        $address->validate(['firstName']);

        expect($address->hasErrors('firstName'))->toBeFalse();
    });

    test('lastName is a safe attribute when config enabled', function () {
        Cms::config()->showFirstAndLastNameFields = true;

        $address = AddressModel::factory()->createElement();
        $address->lastName = 'Doe';

        $address->validate(['lastName']);

        expect($address->hasErrors('lastName'))->toBeFalse();
    });
});

describe('Edge cases', function () {
    test('unicode characters are handled in address fields', function () {
        $address = AddressModel::factory()->createElement();
        $address->addressLine1 = '東京都渋谷区';
        $address->locality = 'München';
        $address->organization = 'Société Générale';

        $address->validate(['addressLine1', 'locality', 'organization']);

        expect($address->hasErrors('addressLine1'))->toBeFalse();
        expect($address->hasErrors('locality'))->toBeFalse();
        expect($address->hasErrors('organization'))->toBeFalse();
    });

    test('special characters in address fields', function () {
        $address = AddressModel::factory()->createElement();
        $address->addressLine1 = "123 O'Brien Street & Main Ave.";
        $address->organization = 'Smith & Sons, Inc.';

        $address->validate(['addressLine1', 'organization']);

        expect($address->hasErrors('addressLine1'))->toBeFalse();
        expect($address->hasErrors('organization'))->toBeFalse();
    });

    test('multiple validation errors can be collected', function () {
        $address = AddressModel::factory()->createElement(['countryCode' => 'US']);
        $address->countryCode = 'XX';
        $address->latitude = '100';
        $address->longitude = '200';

        $address->validate(['countryCode', 'latitude', 'longitude']);

        expect($address->hasErrors('countryCode'))->toBeTrue();
        expect($address->hasErrors('latitude'))->toBeTrue();
        expect($address->hasErrors('longitude'))->toBeTrue();
    });

    test('empty strings are handled for nullable string fields', function () {
        $address = AddressModel::factory()->createElement();
        $address->addressLine1 = '';
        $address->locality = '';
        $address->organization = '';

        $address->validate(['addressLine1', 'locality', 'organization']);

        expect($address->hasErrors('addressLine1'))->toBeFalse();
        expect($address->hasErrors('locality'))->toBeFalse();
        expect($address->hasErrors('organization'))->toBeFalse();
    });
});
