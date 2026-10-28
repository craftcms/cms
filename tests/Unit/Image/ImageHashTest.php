<?php

declare(strict_types=1);

use CraftCms\Cms\Image\ImageHash;

it('hashes images the same way the browser does', function () {
    // The same image the browser sampler’s tests use, so the two stay in step
    $pixels = [];

    for ($y = 0; $y < 30; $y++) {
        for ($x = 0; $x < 40; $x++) {
            $pixels[] = [($x * 7 + $y * 13) % 256, ($x * $y) % 256, ($x + $y * 3) % 256, 1.0];
        }
    }

    expect(ImageHash::dhash($pixels, 40, 30))->toBe('fcfbf8e7ecdad4a3');
});

it('treats transparent pixels as white', function () {
    expect(ImageHash::dhash(array_fill(0, 90 * 80, [0, 0, 0, 0.0]), 90, 80))
        ->toBe(ImageHash::dhash(array_fill(0, 90 * 80, [255, 255, 255, 1.0]), 90, 80));
});

it('counts the bits two hashes differ in', function (string $a, string $b, int $distance) {
    expect(ImageHash::distance($a, $b))->toBe($distance);
})->with([
    'same' => ['fcfbf8e7ecdad4a3', 'fcfbf8e7ecdad4a3', 0],
    'one bit' => ['fcfbf8e7ecdad4a3', 'fcfbf8e7ecdad4a2', 1],
    'every bit' => ['0000000000000000', 'ffffffffffffffff', 64],
]);

it('recognizes hashes', function (mixed $value, bool $isHash) {
    expect(ImageHash::isHash($value))->toBe($isHash);
})->with([
    'hash' => ['fcfbf8e7ecdad4a3', true],
    'uppercase' => ['FCFBF8E7ECDAD4A3', false],
    'too short' => ['fcfbf8e7', false],
    'not hex' => ['fcfbf8e7ecdad4az', false],
    'not a string' => [42, false],
]);
