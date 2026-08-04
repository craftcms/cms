<?php

use CraftCms\Cms\Asset\Models\Asset;

test('sizes', function (string $property, mixed $parameter, int $expectedCount) {
    foreach ([10, 20, 30] as $value) {
        Asset::factory()->create([$property => $value]);
    }

    expect(assetQuery()->$property($parameter)->count())->toBe($expectedCount);
})->with([
    'width',
    'height',
    'size',
])->with([
    ['10', 1],
    [10, 1],
    ['>10', 2],
    ['>=10', 3],
    [['or', '< 20', '> 20'], 2],
    [['< 20', '> 20'], 2], // OR is default
    [['and', '< 20', '> 20'], 0],
]);
