<?php

declare(strict_types=1);

use CraftCms\Cms\Asset\AssetProcessors;
use CraftCms\Cms\Asset\Data\AssetProcessor;
use CraftCms\Cms\Asset\Data\AssetTransformRequest;
use CraftCms\Cms\Asset\Events\AssetProcessorDeleting;
use CraftCms\Cms\Asset\Events\AssetProcessorUpdating;
use CraftCms\Cms\Asset\Models\Asset as AssetModel;
use CraftCms\Cms\Asset\Models\Volume;
use CraftCms\Cms\Asset\Models\VolumeFolder as VolumeFolderModel;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Filesystem\Exceptions\FilesystemException;
use CraftCms\Cms\Image\Data\ImageTransform;
use CraftCms\Cms\Image\Data\ImageTransformIndex;
use CraftCms\Cms\Image\Events\AssetTransformsInvalidating;
use CraftCms\Cms\Image\ImageTransformer;
use CraftCms\Cms\Image\Jobs\GenerateImageTransform;
use CraftCms\Cms\Shared\Exceptions\NotSupportedException;
use CraftCms\Cms\Support\Facades\Filesystems;
use CraftCms\Cms\Support\Str;
use Illuminate\Support\Facades\DB;
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
    $asset->getVolume()->sourceDisk()->put(
        $asset->getPath(),
        file_get_contents(dirname(__DIR__, 2).'/_data/assets/files/background.jpg'),
    );
    Cms::config()->revAssetUrls(true);

    $result = app(AssetProcessors::class)->transform($asset, [
        'width' => 100,
        'height' => 100,
        'mode' => 'crop',
    ], true);

    expect($result->url)->toStartWith('https://example.test/image-transformer-test/')
        ->and($result->mimeType)->toBe('image/jpeg')
        ->and($result->width)->toBe(100)
        ->and($result->height)->toBe(100);
});

it('preserves lazy generation and queued recovery', function () {
    Queue::fake();
    $asset = ($this->createImageAsset)();

    $result = app(AssetProcessors::class)->transform($asset, ['width' => 100]);

    expect($result->url)->not->toBeEmpty();
    Queue::assertPushed(GenerateImageTransform::class);
});

it('honors disabled source-format transformations', function (string $filename, string $setting) {
    Cms::config()->{$setting} = false;
    $asset = ($this->createImageAsset)(['filename' => $filename]);

    expect(fn () => app(AssetProcessors::class)->transform($asset, ['width' => 100]))
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

    app(AssetProcessors::class)->transform($asset, [
        'width' => 100,
        'height' => 100,
        'mode' => 'crop',
    ]);
    $firstIndex = $this->transformer->getTransformIndex($asset, $transform);
    $asset->dateModified = now()->addMinute();
    app(AssetProcessors::class)->transform($asset, [
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

it('uses Craft driver output settings from the source filesystem', function () {
    config()->set('filesystems.disks.configured-transform-source', [
        'driver' => 'local',
        'root' => storage_path('framework/testing/image-transformer-test/configured-source'),
    ]);
    config()->set('filesystems.disks.configured-transform-target', [
        'driver' => 'local',
        'root' => storage_path('framework/testing/image-transformer-test/configured-target'),
        'url' => 'https://renditions.example.test',
    ]);
    Storage::disk('configured-transform-source')->deleteDirectory('');
    Storage::disk('configured-transform-target')->deleteDirectory('');
    app(AssetProcessors::class)->saveAssetProcessor(new AssetProcessor([
        'uid' => Str::uuid()->toString(),
        'name' => 'Configured transform',
        'handle' => 'configured-transform',
        'driver' => 'craft',
        'settings' => [
            'filesystem' => 'disk:configured-transform-target',
            'subpath' => 'renditions',
        ],
    ]), false);
    $volume = Volume::factory()->create([
        'fs' => 'disk:configured-transform-source',
        'assetProcessor' => 'configured-transform',
    ]);
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

    $result = app(AssetProcessors::class)->transform($asset, ['width' => 100], true);

    expect($result->url)->toStartWith('https://renditions.example.test/renditions/')
        ->and(Storage::disk('configured-transform-target')->allFiles())->toHaveCount(1);
});

it('does not reuse similar renditions across Craft transformer profiles', function () {
    foreach (['first-transform-target', 'second-transform-target'] as $disk) {
        config()->set("filesystems.disks.{$disk}", [
            'driver' => 'local',
            'root' => storage_path("framework/testing/image-transformer-test/{$disk}"),
        ]);
        Storage::disk($disk)->deleteDirectory('');
    }

    $profiles = collect(['first', 'second'])->mapWithKeys(function (string $handle): array {
        $profile = new AssetProcessor([
            'uid' => Str::uuid()->toString(),
            'name' => ucfirst($handle),
            'handle' => $handle,
            'driver' => 'craft',
            'settings' => ['filesystem' => "disk:{$handle}-transform-target"],
        ]);
        app(AssetProcessors::class)->saveAssetProcessor($profile, false);

        return [$handle => $profile];
    });
    $asset = ($this->createImageAsset)();
    $asset->getVolume()->sourceDisk()->put(
        $asset->getPath(),
        file_get_contents(dirname(__DIR__, 2).'/_data/assets/files/background.jpg'),
    );
    app(AssetProcessors::class)->transform($asset, [
        'processor' => $profiles['first']->handle,
        'width' => 100,
    ], true);
    app(AssetProcessors::class)->transform($asset, [
        'processor' => $profiles['second']->handle,
        'width' => 100,
    ], true);

    expect(Storage::disk('first-transform-target')->allFiles())->toHaveCount(1)
        ->and(Storage::disk('second-transform-target')->allFiles())->toHaveCount(1);
});

it('fails when the configured Craft driver output filesystem is missing', function () {
    config()->set('filesystems.disks.missing-transform-target-source', [
        'driver' => 'local',
        'root' => storage_path('framework/testing/image-transformer-test/missing-target-source'),
    ]);
    app(AssetProcessors::class)->saveAssetProcessor(new AssetProcessor([
        'uid' => Str::uuid()->toString(),
        'name' => 'Missing transform target',
        'handle' => 'missing-transform-target',
        'driver' => 'craft',
        'settings' => ['filesystem' => 'disk:missing-transform-target'],
    ]), false);
    $volume = Volume::factory()->create([
        'fs' => 'disk:missing-transform-target-source',
        'assetProcessor' => 'missing-transform-target',
    ]);
    $asset = AssetModel::factory()->createElement(['volumeId' => $volume->id]);

    app(AssetProcessors::class)->transform($asset, ['width' => 100]);
})->throws(FilesystemException::class);

it('cleans the Craft transform index when an Asset is invalidated', function () {
    $asset = ($this->createImageAsset)();
    $disk = $asset->getVolume()->sourceDisk();
    $disk->put(
        $asset->getPath(),
        file_get_contents(dirname(__DIR__, 2).'/_data/assets/files/background.jpg'),
    );
    app(AssetProcessors::class)->transform($asset, ['width' => 100], true);
    $rendition = collect($disk->allFiles())->sole(fn (string $path): bool => $path !== $asset->getPath());

    event(new AssetTransformsInvalidating($asset));

    expect($disk->exists($rendition))->toBeFalse();
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

it('removes processor indexes when rendition cleanup fails', function (Closure $invalidate) {
    $asset = ($this->createImageAsset)();
    $processor = new AssetProcessor([
        'uid' => Str::uuid()->toString(),
        'name' => 'Unavailable',
        'handle' => 'unavailable',
        'driver' => 'craft',
        'settings' => ['filesystem' => 'disk:unavailable'],
    ]);
    $index = new ImageTransformIndex([
        'assetId' => $asset->id,
        'transformer' => $processor->uid,
        'filename' => 'transform-test.jpg',
        'transformString' => '_100x100_crop_center-center_none',
        'dateIndexed' => now(),
    ]);
    $this->transformer->storeTransformIndexData($index);
    Exceptions::fake();
    Filesystems::shouldReceive('disk')->once()->andThrow(new RuntimeException('Unavailable output disk'));

    $invalidate($this->transformer, $processor);

    expect(DB::table(Table::IMAGETRANSFORMINDEX)->where('id', $index->id)->exists())->toBeFalse();
    Exceptions::assertReported(RuntimeException::class);
})->with([
    'update' => [function (ImageTransformer $transformer, AssetProcessor $processor): void {
        $newProcessor = clone $processor;
        $newProcessor->settings = [];
        $transformer->handleAssetProcessorUpdating(new AssetProcessorUpdating($processor, $newProcessor));
    }],
    'delete' => [function (ImageTransformer $transformer, AssetProcessor $processor): void {
        $transformer->handleAssetProcessorDeleting(new AssetProcessorDeleting($processor));
    }],
]);

it('does not reuse a rendition older than the source Asset', function () {
    $asset = ($this->createImageAsset)();
    $disk = $asset->getVolume()->sourceDisk();
    $disk->put(
        $asset->getPath(),
        file_get_contents(dirname(__DIR__, 2).'/_data/assets/files/background.jpg'),
    );
    app(AssetProcessors::class)->transform($asset, ['width' => 100], true);
    $rendition = collect($disk->allFiles())->sole(fn (string $path): bool => $path !== $asset->getPath());
    $originalRendition = $disk->get($rendition);

    $disk->put(
        $asset->getPath(),
        file_get_contents(dirname(__DIR__, 2).'/_data/assets/files/image-rotated-180.jpg'),
    );
    $asset->dateModified = now()->addMinute();

    app(AssetProcessors::class)->transform($asset, ['width' => 100], true);

    expect($disk->get($rendition))->not->toBe($originalRendition);
});

it('includes the source revision in rendition URL identity', function () {
    $asset = ($this->createImageAsset)();
    $asset->getVolume()->sourceDisk()->put(
        $asset->getPath(),
        file_get_contents(dirname(__DIR__, 2).'/_data/assets/files/background.jpg'),
    );
    Cms::config()->revAssetUrls(false);

    $firstUrl = app(AssetProcessors::class)->transform($asset, ['width' => 100], true)->url;
    $asset->dateUpdated = now()->addMinute();
    $secondUrl = app(AssetProcessors::class)->transform($asset, ['width' => 100], true)->url;

    expect($secondUrl)->not->toBe($firstUrl);
});

it('rejects unsupported source kinds before generating transforms', function () {
    $asset = ($this->createImageAsset)([
        'filename' => 'transform-test.txt',
        'kind' => 'text',
    ]);
    expect(fn () => $this->transformer->transform(new AssetTransformRequest(
        $asset,
        app(AssetProcessors::class)->resolve('craft'),
        [
            'width' => 100,
            'height' => 100,
            'mode' => 'crop',
        ],
        true,
    )))->toThrow(NotSupportedException::class);
});

it('rejects invalid Craft driver settings', function () {
    $asset = ($this->createImageAsset)();
    $assetProcessor = new AssetProcessor([
        'uid' => Str::uuid()->toString(),
        'name' => 'Invalid',
        'handle' => 'invalid',
        'driver' => 'craft',
        'settings' => ['filesystem' => []],
    ]);

    expect(fn () => $this->transformer->transform(new AssetTransformRequest(
        $asset,
        $assetProcessor,
        ['width' => 100],
        false,
    )))->toThrow(FilesystemException::class);
});
