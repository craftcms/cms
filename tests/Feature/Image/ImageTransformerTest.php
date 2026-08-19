<?php

declare(strict_types=1);

use CraftCms\Cms\Asset\Assets;
use CraftCms\Cms\Asset\AssetTransforms;
use CraftCms\Cms\Asset\Data\AssetTransformRequest;
use CraftCms\Cms\Asset\Exceptions\AssetTransformFailedException;
use CraftCms\Cms\Asset\Models\Asset as AssetModel;
use CraftCms\Cms\Asset\Models\Volume;
use CraftCms\Cms\Asset\Models\VolumeFolder as VolumeFolderModel;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Filesystem\Exceptions\FilesystemException;
use CraftCms\Cms\Image\Data\ImageTransform;
use CraftCms\Cms\Image\Data\ImageTransformIndex;
use CraftCms\Cms\Image\Events\AssetTransformsInvalidating;
use CraftCms\Cms\Image\Events\DeletingTransformedImage;
use CraftCms\Cms\Image\ImageTransformer;
use CraftCms\Cms\Image\Jobs\GenerateImageTransform;
use CraftCms\Cms\Shared\Exceptions\NotSupportedException;
use CraftCms\Cms\Support\Facades\Filesystems;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Exceptions;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    config()->set('filesystems.disks.test-disk', [
        'driver' => 'local',
        'root' => storage_path('framework/testing/image-transformer-test/test-disk'),
        'url' => 'https://example.test/image-transformer-test',
    ]);
    Storage::disk('test-disk')->deleteDirectory('');

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

it('runs the configured Craft driver without changing rendition identity', function () {
    $asset = ($this->createImageAsset)();
    $transform = new ImageTransform([
        'width' => 100,
        'height' => 100,
        'mode' => 'crop',
    ]);
    $index = $this->transformer->getTransformIndex($asset, $transform);
    $path = $asset->folderPath.$index->transformString.DIRECTORY_SEPARATOR.$index->filename;
    $asset->getVolume()->transformDisk()->put($path, 'transform-bytes');
    $index->fileExists = true;
    $this->transformer->storeTransformIndexData($index);
    Cms::config()->revAssetUrls(true);

    $result = app(AssetTransforms::class)->transform($asset, [
        'width' => 100,
        'height' => 100,
        'mode' => 'crop',
    ]);

    expect($result->url)->toStartWith('https://example.test/image-transformer-test/')
        ->and($result->url)->toContain('?v=')
        ->and($result->mimeType)->toBe('image/jpeg')
        ->and($result->width)->toBe(100)
        ->and($result->height)->toBe(100)
        ->and(DB::table(Table::IMAGETRANSFORMINDEX)->where('assetId', $asset->id)->pluck('id')->all())
        ->toBe([$index->id]);
});

it('uses current filesystem settings without changing transform index identity', function () {
    config()->set('filesystems.disks.explicit-default-source', [
        'driver' => 'local',
        'root' => storage_path('framework/testing/image-transformer-test/explicit-default-source'),
        'url' => 'https://source.example.test',
        'asset_transform' => [
            'driver' => 'craft',
            'settings' => [
                'filesystem' => null,
                'subpath' => '',
                'generateBeforePageLoad' => true,
            ],
        ],
    ]);
    config()->set('filesystems.disks.legacy-transform-target', [
        'driver' => 'local',
        'root' => storage_path('framework/testing/image-transformer-test/legacy-transform-target'),
        'url' => 'https://legacy.example.test',
    ]);
    $volume = Volume::factory()->create([
        'fs' => 'disk:explicit-default-source',
        'transformFs' => 'disk:legacy-transform-target',
    ]);
    $folder = VolumeFolderModel::factory()->create(['volumeId' => $volume->id]);
    $asset = AssetModel::factory()->createElement([
        'volumeId' => $volume->id,
        'folderId' => $folder->id,
        'filename' => 'explicit-default.jpg',
        'kind' => 'image',
        'width' => 1200,
        'height' => 800,
        'dateModified' => now()->subMinute(),
    ]);
    $asset->getVolume()->sourceDisk()->put(
        $asset->getPath(),
        file_get_contents(dirname(__DIR__, 2).'/_data/assets/files/background.jpg'),
    );
    $transform = new ImageTransform(['width' => 100]);
    $index = $this->transformer->getTransformIndex($asset, $transform);

    $result = app(AssetTransforms::class)->transform($asset, ['width' => 100]);

    expect($result->url)->toStartWith('https://source.example.test/')
        ->and(DB::table(Table::IMAGETRANSFORMINDEX)->where('assetId', $asset->id)->pluck('id')->all())
        ->toBe([$index->id]);
});

it('preserves lazy generation and queued recovery', function () {
    Queue::fake();
    $asset = ($this->createImageAsset)();

    $result = app(AssetTransforms::class)->transform($asset, ['width' => 100]);

    expect($result->url)->toContain('transformId=');
    Queue::assertPushed(GenerateImageTransform::class);
});

it('honors disabled source-format transformations', function (string $filename, string $setting) {
    Cms::config()->{$setting} = false;
    $asset = ($this->createImageAsset)(['filename' => $filename]);

    expect(fn () => app(AssetTransforms::class)->transform($asset, ['width' => 100]))
        ->toThrow(NotSupportedException::class);
})->with([
    'GIF' => ['transform-test.gif', 'transformGifs'],
    'SVG' => ['transform-test.svg', 'transformSvgs'],
]);

it('recreates the transform index when the asset has been modified since indexing', function () {
    $asset = ($this->createImageAsset)([
        'dateModified' => now()->subMinutes(2),
    ]);
    $asset->getVolume()->sourceDisk()->put(
        $asset->getPath(),
        file_get_contents(dirname(__DIR__, 2).'/_data/assets/files/background.jpg'),
    );

    $transform = new ImageTransform([
        'width' => 100,
        'height' => 100,
        'mode' => 'crop',
    ]);

    app(AssetTransforms::class)->transform($asset, [
        'width' => 100,
        'height' => 100,
        'mode' => 'crop',
    ]);
    $firstIndex = $this->transformer->getTransformIndex($asset, $transform);
    $asset->dateModified = now()->addMinute();
    app(AssetTransforms::class)->transform($asset, [
        'width' => 100,
        'height' => 100,
        'mode' => 'crop',
    ]);
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
            'dateModified' => now()->utc()->addMinute()->toDateTimeString(),
        ]]
    );

    expect(DB::table(Table::IMAGETRANSFORMINDEX)->where('id', $index->id)->exists())->toBeFalse();
});

it('stores dateIndexed as a DB-compatible UTC datetime string', function () {
    $asset = ($this->createImageAsset)();

    $index = new ImageTransformIndex([
        'assetId' => $asset->id,
        'transformer' => ImageTransformer::class,
        'filename' => 'transform-test.jpg',
        'transformString' => '_30x20_crop_center-center_none',
        'dateIndexed' => new DateTime('2026-05-17 15:32:16', new DateTimeZone('Europe/Vienna')),
    ]);

    $this->transformer->storeTransformIndexData($index);

    $storedDateIndexed = DB::table(Table::IMAGETRANSFORMINDEX)
        ->where('id', $index->id)
        ->value('dateIndexed');

    expect($storedDateIndexed)->toBe('2026-05-17 13:32:16')
        ->and($storedDateIndexed)->not->toContain('T')
        ->and($storedDateIndexed)->not->toContain('+');
});

it('uses the transform filesystem URL policy', function () {
    config()->set('filesystems.disks.transform-policy-source', [
        'driver' => 'local',
        'root' => storage_path('framework/testing/image-transformer-test/transform-policy-source'),
    ]);
    config()->set('filesystems.disks.transform-policy-target', [
        'driver' => 'local',
        'root' => storage_path('framework/testing/image-transformer-test/transform-policy-target'),
        'url' => 'https://transforms.example.test',
    ]);

    $volume = Volume::factory()->create([
        'fs' => 'disk:transform-policy-source',
        'transformFs' => 'disk:transform-policy-target',
    ]);
    $folder = VolumeFolderModel::factory()->create(['volumeId' => $volume->id]);
    $asset = AssetModel::factory()->createElement([
        'volumeId' => $volume->id,
        'folderId' => $folder->id,
        'filename' => 'transform-policy.jpg',
        'kind' => 'image',
        'width' => 1200,
        'height' => 800,
        'dateModified' => now()->subMinute(),
    ]);
    $transform = new ImageTransform([
        'width' => 100,
        'height' => 100,
        'mode' => 'crop',
    ]);
    $index = $this->transformer->getTransformIndex($asset, $transform);
    $path = $asset->folderPath.$index->transformString.DIRECTORY_SEPARATOR.$index->filename;

    $asset->getVolume()->transformDisk()->put($path, 'transform-bytes');
    $index->fileExists = true;
    $this->transformer->storeTransformIndexData($index);

    expect(app(AssetTransforms::class)->transform($asset, [
        'width' => 100,
        'height' => 100,
        'mode' => 'crop',
    ])->url)
        ->toStartWith('https://transforms.example.test/');
});

it('uses Craft driver output settings from the source filesystem', function () {
    Queue::fake();
    config()->set('filesystems.disks.configured-transform-source', [
        'driver' => 'local',
        'root' => storage_path('framework/testing/image-transformer-test/configured-source'),
        'asset_transform' => [
            'driver' => 'craft',
            'settings' => [
                'filesystem' => 'disk:configured-transform-target',
                'subpath' => 'renditions',
                'generateBeforePageLoad' => false,
            ],
        ],
    ]);
    config()->set('filesystems.disks.configured-transform-target', [
        'driver' => 'local',
        'root' => storage_path('framework/testing/image-transformer-test/configured-target'),
        'url' => 'https://renditions.example.test',
    ]);
    $volume = Volume::factory()->create(['fs' => 'disk:configured-transform-source']);
    $folder = VolumeFolderModel::factory()->create(['volumeId' => $volume->id]);
    $asset = AssetModel::factory()->createElement([
        'volumeId' => $volume->id,
        'folderId' => $folder->id,
        'filename' => 'configured-transform.jpg',
        'kind' => 'image',
        'width' => 1200,
        'height' => 800,
    ]);
    $asset->getVolume()->sourceDisk()->put(
        $asset->getPath(),
        file_get_contents(dirname(__DIR__, 2).'/_data/assets/files/background.jpg'),
    );

    app(AssetTransforms::class)->transform($asset, ['width' => 100]);
    $index = $this->transformer->getTransformIndex($asset, new ImageTransform(['width' => 100]));
    new GenerateImageTransform($index->id)->handle($this->transformer);
    $result = app(AssetTransforms::class)->transform($asset, ['width' => 100]);
    $path = 'renditions/'.$asset->folderPath.$index->transformString.DIRECTORY_SEPARATOR.$index->filename;

    expect($result->url)->toStartWith('https://renditions.example.test/renditions/')
        ->and(Storage::disk('configured-transform-target')->exists($path))->toBeTrue();
});

it('fails when the configured Craft driver output filesystem is missing', function () {
    config()->set('filesystems.disks.missing-transform-target-source', [
        'driver' => 'local',
        'root' => storage_path('framework/testing/image-transformer-test/missing-target-source'),
        'asset_transform' => [
            'driver' => 'craft',
            'settings' => ['filesystem' => 'disk:missing-transform-target'],
        ],
    ]);
    $volume = Volume::factory()->create(['fs' => 'disk:missing-transform-target-source']);
    $asset = AssetModel::factory()->createElement(['volumeId' => $volume->id]);

    app(AssetTransforms::class)->transform($asset, ['width' => 100]);
})->throws(FilesystemException::class);

it('uses transform-disk-relative paths while preserving the deletion event path', function () {
    $volume = Volume::factory()->create([
        'fs' => 'disk:test-disk',
        'transformSubpath' => 'transforms',
    ]);
    $folder = VolumeFolderModel::factory()->create(['volumeId' => $volume->id]);
    $asset = AssetModel::factory()->createElement([
        'volumeId' => $volume->id,
        'folderId' => $folder->id,
        'filename' => 'scoped-transform.jpg',
        'kind' => 'image',
    ]);
    $index = new ImageTransformIndex([
        'assetId' => $asset->id,
        'filename' => $asset->getFilename(),
        'transformString' => '_100x100_crop_center-center_none',
    ]);
    $path = $asset->folderPath.$index->transformString.DIRECTORY_SEPARATOR.$index->filename;
    Storage::disk('test-disk')->deleteDirectory('transforms');
    $asset->getVolume()->transformDisk()->put($path, 'transform-bytes');
    Event::fake([DeletingTransformedImage::class]);

    $this->transformer->deleteImageTransformFile($asset, $index);

    expect($asset->getVolume()->transformDisk()->exists($path))->toBeFalse();
    Event::assertDispatched(fn (DeletingTransformedImage $event): bool => $event->path === 'transforms'.DIRECTORY_SEPARATOR.$path);
});

it('cleans the Craft transform index when an Asset is invalidated', function () {
    $asset = ($this->createImageAsset)();
    $index = $this->transformer->getTransformIndex($asset, new ImageTransform(['width' => 100]));
    $path = $asset->folderPath.$index->transformString.DIRECTORY_SEPARATOR.$index->filename;
    $asset->getVolume()->transformDisk()->put($path, 'transform-bytes');

    event(new AssetTransformsInvalidating($asset));

    expect(DB::table(Table::IMAGETRANSFORMINDEX)->where('id', $index->id)->exists())->toBeFalse()
        ->and($asset->getVolume()->transformDisk()->exists($path))->toBeFalse();
});

it('reports Craft cleanup failures with redacted context', function () {
    $asset = ($this->createImageAsset)();
    $index = $this->transformer->getTransformIndex($asset, new ImageTransform(['width' => 100]));
    $asset->getVolume()->getFs();
    Exceptions::fake();
    Filesystems::shouldReceive('disk')->andThrow(new RuntimeException('/secret/source/path.jpg'));

    $this->transformer->deleteImageTransformFile($asset, $index);

    Exceptions::assertReported(fn (RuntimeException $exception): bool => str_contains($exception->getMessage(), 'craft')
        && str_contains($exception->getMessage(), (string) $asset->id)
        && ! str_contains($exception->getMessage(), '/secret/source/path.jpg'));
});

it('does not reuse a rendition older than the source Asset', function () {
    $asset = ($this->createImageAsset)();
    $transform = new ImageTransform(['width' => 100]);
    $index = $this->transformer->getTransformIndex($asset, $transform);
    $asset->dateModified = now();
    $path = $asset->folderPath.$index->transformString.DIRECTORY_SEPARATOR.$index->filename;
    $disk = $asset->getVolume()->transformDisk();
    $disk->put($path, 'stale-transform');
    touch($disk->path($path), now()->subMinute()->getTimestamp());
    Queue::fake();

    $result = app(AssetTransforms::class)->transform($asset, ['width' => 100]);

    expect($result->url)->toContain('transformId=')
        ->and(DB::table(Table::IMAGETRANSFORMINDEX)->where('id', $index->id)->value('fileExists'))->toBeFalsy();
    Queue::assertPushed(GenerateImageTransform::class);
});

it('includes the source revision in rendition URL identity', function () {
    $asset = ($this->createImageAsset)();
    $asset->dateUpdated = now();
    $transform = new ImageTransform(['width' => 100]);
    $index = $this->transformer->getTransformIndex($asset, $transform);
    $path = $asset->folderPath.$index->transformString.DIRECTORY_SEPARATOR.$index->filename;
    $asset->getVolume()->transformDisk()->put($path, 'transform');
    $index->fileExists = true;
    $this->transformer->storeTransformIndexData($index);
    Cms::config()->revAssetUrls(false);

    $result = app(AssetTransforms::class)->transform($asset, ['width' => 100]);

    expect($result->url)->toContain('?v='.$asset->dateUpdated->getTimestamp());
});

it('rejects unsupported source kinds before generating transforms', function () {
    $asset = ($this->createImageAsset)([
        'filename' => 'transform-test.txt',
        'kind' => 'text',
    ]);
    $assets = Mockery::mock(Assets::class);
    $assets->shouldNotReceive('getAssetById');
    app()->instance(Assets::class, $assets);

    expect(fn () => $this->transformer->transform(new AssetTransformRequest(
        $asset,
        'craft',
        [
            'width' => 100,
            'height' => 100,
            'mode' => 'crop',
        ],
        ['generateBeforePageLoad' => true],
    )))->toThrow(NotSupportedException::class);
});

it('rejects untyped Craft driver settings', function () {
    $asset = ($this->createImageAsset)();

    expect(fn () => $this->transformer->transform(new AssetTransformRequest(
        $asset,
        'craft',
        ['width' => 100],
        ['generateBeforePageLoad' => 'yes'],
    )))->toThrow(AssetTransformFailedException::class);
});
