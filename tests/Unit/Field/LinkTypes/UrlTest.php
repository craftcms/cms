<?php

declare(strict_types=1);

use CraftCms\Cms\Field\LinkTypes\Url;

test('validateValue', function (string $value, bool $expected, array $config = []) {
    $error = null;
    $urlField = new Url($config);

    expect($urlField->validateValue($value, $error))->toBe($expected);
})->with([
    ['https://google.com', true],
    ['https://münchen-ost.com', true],
    ['https://www.münchen-ost.com', true],
    ['/some-relative-url', true, [
        'allowRootRelativeUrls' => true,
    ]],
    ['/some-relative-url', false, [
        'allowRootRelativeUrls' => false,
    ]],
    ['#anchor', false, [
        'allowAnchors' => false,
    ]],
    ['#anchor', true, [
        'allowAnchors' => true,
    ]],
]);

test('the input only asks the browser to validate absolute URLs', function (array $config, string $type) {
    expect(new Url($config)->pickerConfig()['inputAttributes'])
        ->toBe(['type' => $type, 'inputmode' => 'url']);
})->with([
    [[], 'url'],
    [['allowRootRelativeUrls' => true], 'text'],
    [['allowAnchors' => true], 'text'],
    [['allowCustomSchemes' => true], 'url'],
]);
