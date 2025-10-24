<?php

declare(strict_types=1);

use CraftCms\Cms\Support\Config;

test('durationInSeconds', function (int $expected, mixed $value) {
    expect(Config::durationInSeconds($value))->toBe($expected);
})->with([
    [86400, 'P1D'],
    [90000, 'P1DT1H'],
    [2, 2],
    [12312, 12312],
    [1, 1],
    [1, true],
    [0, 0],
    [0, false],
    [0, '0'],
]);

test('localizedValue', function (mixed $expected, mixed $value, ?string $siteHandle = null) {
    expect(Config::localizedValue($value, $siteHandle))->toBe($expected);
})->with([
    // Ensure if array that it is accessed by the handle and returns the value of the index.
    ['imavalue', ['imahandle' => 'imavalue'], 'imahandle'],

    // If variable is callable.  Ensure the handle gets passed into the callable.
    [
        'imahandle', fn ($handle) => $handle, 'imahandle',
    ],
    ['imnotavalue', ['imnotahandle' => 'imnotavalue', 'anotherkey' => 'anothervalue'], 'imahandle'],
    ['string', 'string'],
    ['', ''],
    [null, []],
    [123, 123],
    [false, false],
    [true, true],
    [12345678901234567890, 12345678901234567890],
]);
