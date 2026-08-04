<?php

declare(strict_types=1);

use CraftCms\Cms\Shared\Enums\Color;

test('try from status', function (string $status, ?Color $expected) {
    expect(Color::tryFromStatus($status))->toBe($expected);
})->with([
    ['on', Color::Teal],
    ['live', Color::Teal],
    ['active', Color::Teal],
    ['enabled', Color::Teal],
    ['turquoise', Color::Teal],
    ['off', Color::Red],
    ['suspended', Color::Red],
    ['expired', Color::Red],
    ['warning', Color::Amber],
    ['pending', Color::Orange],
    ['grey', Color::Gray],
    ['gray', Color::Gray],
    ['unknown', null],
]);

test('css var', function (string|false $expected, string $color, int $shade) {
    if ($expected === false) {
        $this->expectException(InvalidArgumentException::class);

        Color::from($color)->cssVar($shade);
    }

    expect(Color::from($color)->cssVar($shade))->toBe($expected);
})->with([
    ['var(--red-050)', 'red', 50],
    ['var(--red-100)', 'red', 100],
    ['var(--red-500)', 'red', 500],
    ['var(--red-900)', 'red', 900],
    ['var(--white)', 'white', 500],
    ['var(--gray)', 'gray', 500],
    ['var(--black)', 'black', 500],
    [false, 'red', 0],
    [false, 'red', 49],
    [false, 'red', 99],
    [false, 'red', 101],
    [false, 'red', 901],
    [false, 'red', 1000],
]);
