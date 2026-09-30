<?php

declare(strict_types=1);

use CraftCms\Cms\Image\Blurhash;

it('matches the reference encoder', function (array $pixels, int $componentsX, int $componentsY, string $hash) {
    expect(Blurhash::encode($pixels, $componentsX, $componentsY))->toBe($hash);
})->with([
    'uniform' => [array_fill(0, 3, array_fill(0, 4, '#3a6ea5')), 4, 3, 'Lf6v=gtofQtot:o#fQo#fQfQfQfQ'],
    'landscape' => [
        [
            ['#7a6152', '#86786b', '#d69c38', '#cc7143'],
            ['#aca19b', '#c2b8af', '#b99c8c', '#be8e80'],
            ['#a99e98', '#d3c5be', '#a87f76', '#824e44'],
        ],
        4, 3, 'LoKc%{?uJC-U}?ShI@$%VXIoM}sS',
    ],
    'portrait' => [
        [
            ['#7a6152', '#aca19b', '#a99e98'],
            ['#86786b', '#c2b8af', '#d3c5be'],
            ['#d69c38', '#b99c8c', '#a87f76'],
            ['#cc7143', '#be8e80', '#824e44'],
        ],
        3, 4, 'ToKc%{}?VX?uShIoJCI@M}-U$%sS',
    ],
    'single component' => [[['#c81e28']], 1, 1, '00M^#7'],
]);

it('blends transparent colors over white', function () {
    expect(Blurhash::encode([['#3a6ea500']], 1, 1))->toBe('00TSUA');
});

it('rejects components outside of 1 to 9', function (int $componentsX, int $componentsY) {
    Blurhash::encode([['#3a6ea5']], $componentsX, $componentsY);
})->with([[0, 3], [4, 10]])->throws(InvalidArgumentException::class);

it('rejects an image without pixels', function () {
    Blurhash::encode([], 4, 3);
})->throws(InvalidArgumentException::class);
