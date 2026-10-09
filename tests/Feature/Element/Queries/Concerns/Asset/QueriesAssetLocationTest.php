<?php

use CraftCms\Cms\Asset\Models\Asset;
use CraftCms\Cms\Asset\Models\Volume;
use CraftCms\Cms\Asset\Models\VolumeFolder;

test('volume', function () {
    $withoutVolume = Asset::factory()->create();
    $withoutVolume->update(['volumeId' => null]);

    $withVolume = Asset::factory()->create();
    $withVolume->update(['volumeId' => ($volume = Volume::factory()->create())->id]);

    expect(assetQuery()->count())->toBe(2);
    expect(assetQuery()->volumeId($volume->id)->count())->toBe(1);
    expect(assetQuery()->volumeId(':empty:')->count())->toBe(1);
    expect(assetQuery()->volume($volume->handle)->count())->toBe(1);
    expect(assetQuery()->volume($volume->id)->count())->toBe(1);
});

test('folder', function () {
    $folder = VolumeFolder::factory()->create([
        'path' => 'foo/',
    ]);
    $subFolder = VolumeFolder::factory()->create([
        'parentId' => $folder->id,
        'volumeId' => $folder->volumeId,
        'path' => 'foo/bar/',
    ]);

    Asset::factory()->create(['folderId' => $folder->id]);
    Asset::factory()->create(['folderId' => $subFolder->id]);

    expect(assetQuery()->count())->toBe(2);
    expect(assetQuery()->folderId($folder->id)->count())->toBe(1);
    expect(assetQuery()->folderId($subFolder->id)->count())->toBe(1);
    expect(assetQuery()->folderId($folder->id)->includeSubfolders()->count())->toBe(2);

    expect(assetQuery()->folderPath('foo/')->count())->toBe(1);
    expect(assetQuery()->folderPath('foo/*')->count())->toBe(2);
    expect(assetQuery()->folderPath('foo/bar/')->count())->toBe(1);
    expect(assetQuery()->folderPath('not foo/bar/')->count())->toBe(1);
});
