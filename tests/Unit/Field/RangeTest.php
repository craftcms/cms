<?php

declare(strict_types=1);

use CraftCms\Cms\Field\Range;

test('normalizeValue', function (mixed $value, int|float|null $expected) {
    expect(new Range()->normalizeValue($value, null))->toBe($expected);
})->with([
    'int' => [5, 5],
    'float' => [5.5, 5.5],
    'int string' => ['5', 5],
    'float string' => ['5.5', 5.5],
    'empty string' => ['', null],
    'null' => [null, null],
    'non-numeric string' => ['abc', null],
    'array without locale' => [['value' => '5'], null],
]);
