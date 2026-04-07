<?php

declare(strict_types=1);

use CraftCms\Cms\Cms;
use CraftCms\Cms\Support\PHP;

test('version', function () {
    expect(PHP::version())->not()->toContain('+');
});

test('configValueAsBool', function () {
    $displayErrorsValue = ini_get('display_errors');
    @ini_set('display_errors', '1');
    expect(PHP::configValueAsBool('display_errors'))->toBeTrue();
    @ini_set('display_errors', $displayErrorsValue);

    $timezoneValue = ini_get('date.timezone');
    @ini_set('date.timezone', Cms::timezone() ?: 'Europe/Amsterdam');
    expect(PHP::configValueAsBool('date.timezone'))->toBeFalse();
    @ini_set('date.timezone', $timezoneValue);

    expect(PHP::configValueAsBool(''))->toBeFalse();
    expect(PHP::configValueAsBool('This is not a config value'))->toBeFalse();
});

test('configValueInBytes', function () {
    expect(PHP::configValueInBytes('memory_limit'))->toBeNumeric();
});

test('normalizePaths', function () {
    expect(PHP::normalizePaths('.'))->toBe([getcwd()]);
    expect(PHP::normalizePaths('./'))->toBe([getcwd()]);
    expect(PHP::normalizePaths('./foo'))->toBe([getcwd().DIRECTORY_SEPARATOR.'foo']);
    expect(PHP::normalizePaths('.\\foo'))->toBe([getcwd().DIRECTORY_SEPARATOR.'foo']);

    putenv('TEST_CONST=/foo/');
    expect(PHP::normalizePaths('.:${TEST_CONST}'))->toBe([getcwd(), DIRECTORY_SEPARATOR.'foo']);
    expect(PHP::normalizePaths(' . ; ${TEST_CONST} '))->toBe([getcwd(), DIRECTORY_SEPARATOR.'foo']);
    putenv('TEST_CONST');
});

test('sizeToBytes', function (int|float $expected, string|int $value) {
    expect(PHP::sizeToBytes($value))->toBe($expected);
})->with([
    [1, '1B'],
    [1024, '1K'],
    [1024 ** 2, '1M'],
    [1024 ** 3, '1G'],
    [5368709120, '5G'],
    [5242880, '5M'],
    [5120, '5K'],
    [5120, 'ABCDEFHIJFLKNOPQRSTUVWXYZ5K'],
    [5, '5ABCDEFHIJFKLKNOPQRSTUVWXYZ'],
    [5120, '!@#$%^5K&*()'],
    [4, '4'],
    [5, 5],
    [0, 'M5'],
]);

test('executable', function () {
    expect(PHP::executable())->not()->toBeNull();
});
