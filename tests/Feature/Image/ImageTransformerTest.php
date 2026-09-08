<?php

declare(strict_types=1);

use CraftCms\Cms\Asset\AssetTransformDrivers;
use CraftCms\Cms\Asset\AssetTransformers;
use CraftCms\Cms\Asset\Data\AssetTransformer;
use CraftCms\Cms\Asset\Data\AssetTransformRequest;
use CraftCms\Cms\Asset\Events\AssetTransformerDeleting;
use CraftCms\Cms\Asset\Events\AssetTransformerUpdating;
use CraftCms\Cms\Asset\Models\Asset as AssetModel;
use CraftCms\Cms\Asset\Models\Volume;
use CraftCms\Cms\Asset\Models\VolumeFolder as VolumeFolderModel;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Filesystem\Exceptions\FilesystemException;
use CraftCms\Cms\Http\Controllers\Assets\TransformController;
use CraftCms\Cms\Image\CraftAssetTransformDriver;
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

use function Pest\Laravel\getJson;

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

it('runs the configured Craft driver without changing transform identity', function () {
    $asset = ($this->createImageAsset)();
    $asset->getVolume()->sourceDisk()->put(
        $asset->getPath(),
        file_get_contents(dirname(__DIR__, 2).'/_data/assets/files/background.jpg'),
    );
    Cms::config()->revAssetUrls(true);

    $result = app(CraftAssetTransformDriver::class)->withImmediateTransforms(
        fn () => app(AssetTransformers::class)->transform($asset, [
            'width' => 100,
            'height' => 100,
            'mode' => 'crop',
        ]),
    );

    expect($result->url)->toStartWith('https://example.test/image-transformer-test/')
        ->and($result->mimeType)->toBe('image/jpeg')
        ->and($result->width)->toBe(100)
        ->and($result->height)->toBe(100);
});

it('preserves lazy generation and queued recovery', function (?string $format, string $generation) {
    Queue::fake();
    $asset = ($this->createImageAsset)();
    $disk = $asset->getVolume()->sourceDisk();
    $disk->put($asset->getPath(), file_get_contents(dirname(__DIR__, 2).'/_data/assets/files/background.jpg'));
    $transform = new ImageTransform(['width' => 100, 'format' => $format]);

    $result = app(AssetTransformers::class)->transform($asset, ['width' => 100, 'format' => $format]);
    $index = $this->transformer->getTransformIndex($asset, $transform);
    $expectedExtension = $format ?? 'jpg';
    $expectedMime = $format === 'webp' ? 'image/webp' : 'image/jpeg';
    $path = $this->volume->uid.'/'.$index->transformString.'/'.($format === 'webp' ? $asset->id.'/' : '').'transform-test.'.$expectedExtension;

    expect($result->url)->not->toBeEmpty();
    Queue::assertPushed(GenerateImageTransform::class, fn (GenerateImageTransform $job) => $job->transformId === $index->id);

    foreach ([false, true] as $regenerate) {
        if ($regenerate) {
            $disk->delete($path);
            $index->fileExists = false;
            $this->transformer->storeTransformIndexData($index);
        }

        if ($generation === 'job') {
            app()->call(new GenerateImageTransform($index->id)->handle(...));
        } elseif ($generation === 'http') {
            getJson(action([TransformController::class, 'generate'], ['transformId' => $index->id]))
                ->assertOk()
                ->assertJsonPath('url', fn (string $url) => str_contains($url, 'transform-test.'.$expectedExtension));
        } else {
            $this->transformer->eagerLoadTransforms([$transform], [$asset]);
            $eagerIndex = $this->transformer->getTransformIndex($asset, $transform);
            $this->transformer->getTransformUrlForIndex($asset, $eagerIndex, true);
        }

        expect($disk->exists($path))->toBeTrue();
        expect(getimagesize($disk->path($path))['mime'])->toBe($expectedMime);
        $reloaded = $this->transformer->getTransformIndexModelById($index->id);
        expect($reloaded->fileExists)->toBeTrue();
        expect($reloaded->transform->format)->toBe($format);
        expect($reloaded->filename)->toBe('transform-test.'.$expectedExtension);
        expect(DB::table(Table::IMAGETRANSFORMINDEX)->where('assetId', $asset->id)->count())->toBe(1);
    }
})->with([null, 'webp'])->with(['job', 'http', 'eager']);

it('honors disabled source-format transformations', function (string $filename, string $setting) {
    Cms::config()->{$setting} = false;
    $asset = ($this->createImageAsset)(['filename' => $filename]);

    expect(fn () => app(AssetTransformers::class)->transform($asset, ['width' => 100]))
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

    app(AssetTransformers::class)->transform($asset, [
        'width' => 100,
        'height' => 100,
        'mode' => 'crop',
    ]);
    $firstIndex = $this->transformer->getTransformIndex($asset, $transform);
    $asset->dateModified = now()->addMinute();
    app(AssetTransformers::class)->transform($asset, [
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
        'url' => 'https://transforms.example.test',
    ]);
    Storage::disk('configured-transform-source')->deleteDirectory('');
    Storage::disk('configured-transform-target')->deleteDirectory('');
    app(AssetTransformers::class)->saveAssetTransformer(new AssetTransformer([
        'uid' => Str::uuid()->toString(),
        'name' => 'Configured transform',
        'handle' => 'configured-transform',
        'driver' => 'craft',
        'settings' => [
            'filesystem' => 'disk:configured-transform-target',
            'subpath' => 'transforms',
        ],
    ]), false);
    $volume = Volume::factory()->create([
        'fs' => 'disk:configured-transform-source',
        'assetTransformer' => 'configured-transform',
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

    $result = app(CraftAssetTransformDriver::class)->withImmediateTransforms(
        fn () => app(AssetTransformers::class)->transform($asset, ['width' => 100]),
    );

    expect($result->url)->toStartWith('https://transforms.example.test/transforms/')
        ->and(Storage::disk('configured-transform-target')->allFiles())->toHaveCount(1);
});

it('does not reuse similar transform results across Craft transformer profiles', function () {
    foreach (['first-transform-target', 'second-transform-target'] as $disk) {
        config()->set("filesystems.disks.{$disk}", [
            'driver' => 'local',
            'root' => storage_path("framework/testing/image-transformer-test/{$disk}"),
        ]);
        Storage::disk($disk)->deleteDirectory('');
    }

    $profiles = collect(['first', 'second'])->mapWithKeys(function (string $handle): array {
        $profile = new AssetTransformer([
            'uid' => Str::uuid()->toString(),
            'name' => ucfirst($handle),
            'handle' => $handle,
            'driver' => 'craft',
            'settings' => ['filesystem' => "disk:{$handle}-transform-target"],
        ]);
        app(AssetTransformers::class)->saveAssetTransformer($profile, false);

        return [$handle => $profile];
    });
    $asset = ($this->createImageAsset)();
    $asset->getVolume()->sourceDisk()->put(
        $asset->getPath(),
        file_get_contents(dirname(__DIR__, 2).'/_data/assets/files/background.jpg'),
    );
    app(CraftAssetTransformDriver::class)->withImmediateTransforms(function () use ($asset, $profiles): void {
        app(AssetTransformers::class)->transform(
            $asset,
            ['width' => 100],
            transformer: $profiles['first']->handle,
        );
        app(AssetTransformers::class)->transform(
            $asset,
            ['width' => 100],
            transformer: $profiles['second']->handle,
        );
    });

    expect(Storage::disk('first-transform-target')->allFiles())->toHaveCount(1)
        ->and(Storage::disk('second-transform-target')->allFiles())->toHaveCount(1);
});

it('fails when the configured Craft driver output filesystem is missing', function () {
    config()->set('filesystems.disks.missing-transform-target-source', [
        'driver' => 'local',
        'root' => storage_path('framework/testing/image-transformer-test/missing-target-source'),
    ]);
    app(AssetTransformers::class)->saveAssetTransformer(new AssetTransformer([
        'uid' => Str::uuid()->toString(),
        'name' => 'Missing transform target',
        'handle' => 'missing-transform-target',
        'driver' => 'craft',
        'settings' => ['filesystem' => 'disk:missing-transform-target'],
    ]), false);
    $volume = Volume::factory()->create([
        'fs' => 'disk:missing-transform-target-source',
        'assetTransformer' => 'missing-transform-target',
    ]);
    $asset = AssetModel::factory()->createElement(['volumeId' => $volume->id]);

    app(AssetTransformers::class)->transform($asset, ['width' => 100]);
})->throws(FilesystemException::class);

it('cleans the Craft transform index when an Asset is invalidated', function () {
    $asset = ($this->createImageAsset)();
    $disk = $asset->getVolume()->sourceDisk();
    $disk->put(
        $asset->getPath(),
        file_get_contents(dirname(__DIR__, 2).'/_data/assets/files/background.jpg'),
    );
    app(CraftAssetTransformDriver::class)->withImmediateTransforms(
        fn () => app(AssetTransformers::class)->transform($asset, ['width' => 100]),
    );
    $transformPath = collect($disk->allFiles())->sole(fn (string $path): bool => $path !== $asset->getPath());

    event(new AssetTransformsInvalidating($asset));

    expect($disk->exists($transformPath))->toBeFalse();
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

it('removes transformer indexes when transform cleanup fails', function (Closure $invalidate) {
    $asset = ($this->createImageAsset)();
    $assetTransformer = new AssetTransformer([
        'uid' => Str::uuid()->toString(),
        'name' => 'Unavailable',
        'handle' => 'unavailable',
        'driver' => 'craft',
        'settings' => ['filesystem' => 'disk:unavailable'],
    ]);
    $index = new ImageTransformIndex([
        'assetId' => $asset->id,
        'transformer' => $assetTransformer->uid,
        'filename' => 'transform-test.jpg',
        'transformString' => '_100x100_crop_center-center_none',
        'dateIndexed' => now(),
    ]);
    $this->transformer->storeTransformIndexData($index);
    Exceptions::fake();
    Filesystems::shouldReceive('disk')->once()->andThrow(new RuntimeException('Unavailable output disk'));

    $invalidate($this->transformer, $assetTransformer);

    expect(DB::table(Table::IMAGETRANSFORMINDEX)->where('id', $index->id)->exists())->toBeFalse();
    Exceptions::assertReported(RuntimeException::class);
})->with([
    'update' => [function (ImageTransformer $imageTransformer, AssetTransformer $assetTransformer): void {
        $newTransformer = clone $assetTransformer;
        $newTransformer->settings = [];
        $imageTransformer->handleAssetTransformerUpdating(new AssetTransformerUpdating($assetTransformer, $newTransformer));
    }],
    'delete' => [function (ImageTransformer $imageTransformer, AssetTransformer $assetTransformer): void {
        $imageTransformer->handleAssetTransformerDeleting(new AssetTransformerDeleting($assetTransformer));
    }],
]);

it('does not reuse a transform result older than the source Asset', function () {
    $asset = ($this->createImageAsset)();
    $disk = $asset->getVolume()->sourceDisk();
    $disk->put(
        $asset->getPath(),
        file_get_contents(dirname(__DIR__, 2).'/_data/assets/files/background.jpg'),
    );
    app(CraftAssetTransformDriver::class)->withImmediateTransforms(
        fn () => app(AssetTransformers::class)->transform($asset, ['width' => 100]),
    );
    $transformPath = collect($disk->allFiles())->sole(fn (string $path): bool => $path !== $asset->getPath());
    $originalTransform = $disk->get($transformPath);

    $disk->put(
        $asset->getPath(),
        file_get_contents(dirname(__DIR__, 2).'/_data/assets/files/image-rotated-180.jpg'),
    );
    $asset->dateModified = now()->addMinute();

    app(CraftAssetTransformDriver::class)->withImmediateTransforms(
        fn () => app(AssetTransformers::class)->transform($asset, ['width' => 100]),
    );

    expect($disk->get($transformPath))->not->toBe($originalTransform);
});

it('includes the source revision in transform URL identity', function () {
    $asset = ($this->createImageAsset)();
    $asset->getVolume()->sourceDisk()->put(
        $asset->getPath(),
        file_get_contents(dirname(__DIR__, 2).'/_data/assets/files/background.jpg'),
    );
    Cms::config()->revAssetUrls(false);

    $driver = app(CraftAssetTransformDriver::class);
    $firstUrl = $driver->withImmediateTransforms(
        fn () => app(AssetTransformers::class)->transform($asset, ['width' => 100])->url,
    );
    $asset->dateUpdated = now()->addMinute();
    $secondUrl = $driver->withImmediateTransforms(
        fn () => app(AssetTransformers::class)->transform($asset, ['width' => 100])->url,
    );

    expect($secondUrl)->not->toBe($firstUrl);
});

it('rejects unsupported source kinds before generating transforms', function () {
    $asset = ($this->createImageAsset)([
        'filename' => 'transform-test.txt',
        'kind' => 'text',
    ]);
    expect(fn () => app(AssetTransformDrivers::class)->driver('craft')->transform(new AssetTransformRequest(
        $asset,
        app(AssetTransformers::class)->resolve('craft'),
        [
            'width' => 100,
            'height' => 100,
            'mode' => 'crop',
        ],
    )))->toThrow(NotSupportedException::class);
});

it('rejects invalid Craft driver settings', function () {
    $asset = ($this->createImageAsset)();
    $assetTransformer = new AssetTransformer([
        'uid' => Str::uuid()->toString(),
        'name' => 'Invalid',
        'handle' => 'invalid',
        'driver' => 'craft',
        'settings' => ['filesystem' => []],
    ]);

    expect(fn () => app(AssetTransformDrivers::class)->driver('craft')->transform(new AssetTransformRequest(
        $asset,
        $assetTransformer,
        ['width' => 100],
    )))->toThrow(FilesystemException::class);
});

it('keeps eager loaded index dates independent and follows the current timezone', function () {
    $asset = ($this->createImageAsset)();
    $transform = new ImageTransform(['width' => 100]);
    $original = $this->transformer->getTransformIndex($asset, $transform);
    $this->transformer->eagerLoadTransforms([$transform], [$asset]);

    $first = $this->transformer->getTransformIndex($asset, $transform);
    $first->dateIndexed->modify('+1 year');
    $first->dateCreated->modify('+1 year');
    $first->dateUpdated->modify('+1 year');
    $first->fileExists = true;
    $first->transform->width = 999;
    Cms::config()->timezone('Europe/Brussels');

    $second = $this->transformer->getTransformIndex($asset, $transform);
    $persisted = $this->transformer->getTransformIndexModelById($original->id);

    expect($second->id)->toBe($original->id)
        ->and($second->fileExists)->toBeFalse()
        ->and($second->transform->width)->toBe(100);

    foreach (['dateIndexed', 'dateCreated', 'dateUpdated'] as $attribute) {
        expect($second->$attribute->getTimestamp())->toBe($persisted->$attribute->getTimestamp())
            ->and($second->$attribute->getTimezone()->getName())->toBe('Europe/Brussels')
            ->and($second->$attribute)->not->toBe($first->$attribute);
    }
});

it('reevaluates stale generation state when retrieving an eager loaded index', function (string $timeZone) {
    Cms::config()->timezone($timeZone);
    Cms::setDefaultTimezone();
    $this->freezeSecond();
    $asset = ($this->createImageAsset)();
    $asset->dateModified = now()->subMinute();
    $transform = new ImageTransform(['width' => 100]);
    $original = $this->transformer->getTransformIndex($asset, $transform);
    $original->inProgress = true;
    $this->transformer->storeTransformIndexData($original);
    $this->transformer->eagerLoadTransforms([$transform], [$asset]);

    expect($this->transformer->getTransformIndex($asset, $transform)->inProgress)->toBeTrue();

    $this->travel(31)->seconds();

    expect($this->transformer->getTransformIndex($asset, $transform)->inProgress)->toBeFalse();

    $this->travelBack();

    expect($this->transformer->getTransformIndex($asset, $transform)->inProgress)->toBeTrue();
})->with(['UTC', 'America/Los_Angeles', 'Asia/Tokyo']);

it('stores transform creation and update times in UTC', function (string $timeZone) {
    Cms::config()->timezone($timeZone);
    Cms::setDefaultTimezone();
    $this->freezeSecond();
    $createdAt = now()->utc()->format('Y-m-d H:i:s');
    $asset = ($this->createImageAsset)();
    $transform = new ImageTransform(['width' => 100]);
    $index = $this->transformer->getTransformIndex($asset, $transform);

    $this->assertDatabaseHas(Table::IMAGETRANSFORMINDEX, [
        'id' => $index->id,
        'dateCreated' => $createdAt,
        'dateUpdated' => $createdAt,
    ]);

    $this->travel(10)->seconds();
    $updatedAt = now()->utc()->format('Y-m-d H:i:s');
    $index->inProgress = true;
    $this->transformer->storeTransformIndexData($index);

    $this->assertDatabaseHas(Table::IMAGETRANSFORMINDEX, [
        'id' => $index->id,
        'dateCreated' => $createdAt,
        'dateUpdated' => $updatedAt,
        'inProgress' => true,
    ]);

    expect($this->transformer->getTransformIndexModelById($index->id)->inProgress)->toBeTrue();
})->with(['America/Los_Angeles', 'Asia/Tokyo']);
