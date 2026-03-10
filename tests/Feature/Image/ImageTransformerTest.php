<?php

declare(strict_types=1);

use CraftCms\Cms\Asset\Models\Asset as AssetModel;
use CraftCms\Cms\Asset\Models\Volume;
use CraftCms\Cms\Asset\Models\VolumeFolder as VolumeFolderModel;
use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Image\Data\ImageTransform;
use CraftCms\Cms\Image\ImageTransformer;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    config()->set('filesystems.disks.test-disk', [
        'driver' => 'local',
        'root' => storage_path('framework/testing/image-transformer-test/test-disk'),
        'url' => 'https://example.test/image-transformer-test',
    ]);

    $this->volume = Volume::factory()->create(['fs' => 'disk:test-disk']);
    $this->folder = VolumeFolderModel::factory()->create(['volumeId' => $this->volume->id]);
    $this->transformer = new ImageTransformer;
    $this->createImageAsset = fn (array $attributes = []) => AssetModel::factory()->createElement([
        'volumeId' => $this->volume->id,
        'folderId' => $this->folder->id,
        'filename' => 'transform-test.jpg',
        'kind' => 'image',
        'width' => 1200,
        'height' => 800,
        'dateModified' => now()->subMinute(),
        ...$attributes,
    ]);
});

it('reuses the existing transform index when the asset has not changed', function () {
    $asset = ($this->createImageAsset)();

    $transform = new ImageTransform([
        'width' => 100,
        'height' => 100,
        'mode' => 'crop',
    ]);

    $firstIndex = $this->transformer->getTransformIndex($asset, $transform);
    $secondIndex = $this->transformer->getTransformIndex($asset, $transform);

    expect($secondIndex->id)->toBe($firstIndex->id)
        ->and(DB::table(Table::IMAGETRANSFORMINDEX)
            ->where('assetId', $asset->id)
            ->count())
        ->toBe(1);
});

it('recreates the transform index when the asset has been modified since indexing', function () {
    $asset = ($this->createImageAsset)([
        'dateModified' => now()->subMinutes(2),
    ]);

    $transform = new ImageTransform([
        'width' => 100,
        'height' => 100,
        'mode' => 'crop',
    ]);

    $firstIndex = $this->transformer->getTransformIndex($asset, $transform);
    $asset->dateModified = now()->addMinute();
    $secondIndex = $this->transformer->getTransformIndex($asset, $transform);

    expect($secondIndex->id)->not->toBe($firstIndex->id)
        ->and(DB::table(Table::IMAGETRANSFORMINDEX)
            ->where('assetId', $asset->id)
            ->count())
        ->toBe(1);
});

it('recreates the transform index when a named transform changed after indexing', function () {
    $asset = ($this->createImageAsset)();

    $transform = new ImageTransform([
        'id' => 100,
        'name' => 'Thumb',
        'handle' => 'thumb',
        'width' => 100,
        'height' => 100,
        'mode' => 'crop',
        'parameterChangeTime' => now()->subMinute(),
    ]);

    $firstIndex = $this->transformer->getTransformIndex($asset, $transform);
    $transform->parameterChangeTime = now()->addMinute();
    $secondIndex = $this->transformer->getTransformIndex($asset, $transform);

    expect($secondIndex->id)->not->toBe($firstIndex->id)
        ->and(DB::table(Table::IMAGETRANSFORMINDEX)
            ->where('assetId', $asset->id)
            ->where('transformString', '_thumb')
            ->count())
        ->toBe(1);
});

it('invalidates stale eager-loaded indexes when dateModified is a string', function () {
    $asset = ($this->createImageAsset)();
    $transform = new ImageTransform([
        'width' => 100,
        'height' => 100,
        'mode' => 'crop',
    ]);

    $index = $this->transformer->getTransformIndex($asset, $transform);

    $this->transformer->eagerLoadTransforms(
        [$transform],
        [[
            'id' => $asset->id,
            'dateModified' => now()->addMinute()->toDateTimeString(),
        ]]
    );

    expect(DB::table(Table::IMAGETRANSFORMINDEX)->where('id', $index->id)->exists())->toBeFalse();
});
