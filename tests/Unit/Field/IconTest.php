<?php

declare(strict_types=1);

use CraftCms\Cms\Field\Icon;

it('only normalizes icon names and custom icons', function (string $value, ?string $expected) {
    expect(new Icon()->normalizeValue($value, null)?->serialize())->toBe($expected);
})->with([
    'icon name' => ['house', 'house'],
    'custom icon' => ['custom:my-icon', 'custom:my-icon'],
    'url' => ['ftp://example.com/icon.svg', null],
    'file path' => ['/var/www/icon.svg', null],
    'alias' => ['@webroot/icon.svg', null],
    'relative file path' => ['PRIVATE_ICON', null],
    'svg markup' => ['<svg xmlns="http://www.w3.org/2000/svg"></svg>', null],
]);
