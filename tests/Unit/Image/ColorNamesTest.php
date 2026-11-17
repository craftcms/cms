<?php

declare(strict_types=1);

use CraftCms\Cms\Image\ColorNames;

it('names colors the way people see them', function (string $hex, string $name): void {
    expect(ColorNames::name($hex))->toBe($name);
})->with([
    ['#ff0000', 'red'],
    ['#ffa500', 'orange'],
    ['#ffff00', 'yellow'],
    ['#00ff00', 'green'],
    ['#008080', 'teal'],
    ['#0000ff', 'blue'],
    ['#3a6ea5', 'blue'],
    ['#800080', 'purple'],
    ['#ff69b4', 'pink'],
    ['#8b4513', 'brown'],
    ['#000000', 'black'],
    ['#1a1a1a', 'black'],
    ['#ffffff', 'white'],
    ['#808080', 'gray'],
]);

it('only uses the names it lists', function (): void {
    foreach (['#123456', '#abcdef', '#fedcba', '#a0522d', '#40e0d0', '#ee82ee', '#c0c0c0'] as $hex) {
        expect(ColorNames::NAMES)->toContain(ColorNames::name($hex));
    }
});
