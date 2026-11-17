<?php

use CraftCms\Cms\Asset\Models\Asset;
use CraftCms\Cms\User\Models\User;

test('uploader', function () {
    $asset1 = Asset::factory()->create([
        'uploaderId' => User::factory()->create()->id,
    ]);

    Asset::factory()->create([
        'uploaderId' => User::factory()->create()->id,
    ]);

    expect(assetQuery()->count())->toBe(2);
    expect(assetQuery()->uploader($asset1->uploaderId)->count())->toBe(1);
    expect(assetQuery()->uploader($asset1->uploader)->count())->toBe(1);
});

test('filename', function (mixed $param, int $expectedCount) {
    Asset::factory()->create([
        'filename' => 'some-filename.jpg',
    ]);

    Asset::factory()->create([
        'filename' => 'another-filename.jpg',
    ]);

    expect(assetQuery()->filename($param)->count())->toBe($expectedCount);
})->with([
    [null, 2],
    ['some-filename.jpg', 1],
    ['*.jpg', 2],
    ['*filename*', 2],
    ['not *filename*', 0],
]);

test('kind', function (mixed $param, int $expectedCount) {
    Asset::factory()->create(['kind' => 'image', 'filename' => 'file.jpg']);
    Asset::factory()->create(['kind' => 'unknown', 'filename' => 'file.jpg']);
    Asset::factory()->create(['kind' => 'audio', 'filename' => 'file.mp3']);
    Asset::factory()->create(['kind' => 'unknown', 'filename' => 'file.xyz']);

    expect(assetQuery()->kind($param)->count())->toBe($expectedCount);
})->with([
    ['image', 2],
    ['image, audio', 3],
    ['not image', 2],
    [['not', 'image', 'audio'], 1],
    ['and image, audio', 0],
]);
