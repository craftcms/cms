<?php

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
    @ini_set('date.timezone', Craft::$app->getTimeZone() ?: 'Europe/Amsterdam');
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

test('sizeToBytes', function (int|float $expected, string $value) {
    expect(PHP::sizeToBytes($value))->toBe($expected);
})->with([
    [1, '1B'],
    [1024, '1K'],
    [1024 ** 2, '1M'],
    [1024 ** 3, '1G'],
]);

test('executable', function () {
    expect(PHP::executable())->not()->toBeNull();
});
