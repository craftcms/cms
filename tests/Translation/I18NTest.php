<?php

declare(strict_types=1);

use CraftCms\Cms\Support\Facades\I18N;

test('normalize language', function (string $exptected, string $language) {
    expect(I18N::normalizeLanguage($language))->toBe($exptected);
})->with([
    ['nl', 'nl'],
    ['en-US', 'en-US'],
    ['af', 'af'],
    ['af-NA', 'af-NA'],
    ['en-AG', 'en-ag'],
    ['en-AG', 'EN-AG'],
]);

test('normalization exception', function (string $language) {
    $this->expectException(InvalidArgumentException::class);

    I18N::normalizeLanguage($language);
})->with([
    'dutch',
    'notalang',
]);

test('normalize number', function (mixed $expected, mixed $number, ?string $localeId) {
    expect(I18N::normalizeNumber($number, $localeId))->toBe($expected);
})->with([
    ['2000000000', '20,0000,0000', null],
    ['20 0000 0000', '20 0000 0000', null],
    ['20.0000.0000', '20.0000.0000', null],
    [2000000000, 2000000000, null],
]);
