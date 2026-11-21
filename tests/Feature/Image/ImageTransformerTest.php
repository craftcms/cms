<?php

declare(strict_types=1);

use CraftCms\Cms\Asset\AssetIndexer;
use CraftCms\Cms\Asset\AssetTransformers;
use CraftCms\Cms\Asset\Data\AssetTransformer;
use CraftCms\Cms\Asset\Data\AssetTransformRequest;
use CraftCms\Cms\Asset\Elements\Asset;
use CraftCms\Cms\Asset\Models\Asset as AssetModel;
use CraftCms\Cms\Asset\Models\AssetIndexData;
use CraftCms\Cms\Asset\Models\Volume;
use CraftCms\Cms\Asset\Models\VolumeFolder as VolumeFolderModel;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Filesystem\Exceptions\FilesystemException;
use CraftCms\Cms\Http\Controllers\Assets\TransformController;
use CraftCms\Cms\Image\Data\ImageTransform;
use CraftCms\Cms\Image\Data\ImageTransformIndex;
use CraftCms\Cms\Image\Events\AssetTransformsInvalidating;
use CraftCms\Cms\Image\ImageTransformer;
use CraftCms\Cms\Image\ImageTransforms;
use CraftCms\Cms\Image\Jobs\GenerateImageTransform;
use CraftCms\Cms\Shared\Exceptions\NotSupportedException;
use Illuminate\Contracts\Filesystem\Filesystem;
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

    $this->volume = Volume::factory()->create([
        'disk' => 'test-disk',
        'hasUrls' => true,
    ]);
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

    $result = app(AssetTransformers::class)->withImmediateTransforms(
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
    $path = '_'.$this->volume->uid.'/'.$index->transformString.'/'.($format === 'webp' ? $asset->id.'/' : '').'transform-test.'.$expectedExtension;

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

it('keeps named transform handles through the Craft driver unless parameters override them', function (mixed $definition, string $transformString) {
    Queue::fake();
    app(ImageTransforms::class)->saveTransform(new ImageTransform([
        'name' => 'F1500',
        'handle' => 'f1500',
        'width' => 1500,
        'mode' => 'fit',
        'quality' => 100,
    ]));
    $asset = ($this->createImageAsset)();

    app(AssetTransformers::class)->transform($asset, $definition);

    expect(DB::table(Table::IMAGETRANSFORMINDEX)->where('assetId', $asset->id)->pluck('transformString')->all())
        ->toBe([$transformString]);
})->with([
    'handle' => ['f1500', '_f1500'],
    'transform key' => [['transform' => 'f1500'], '_f1500'],
    'overridden' => [['transform' => 'f1500', 'width' => 800], '_800xAUTO_fit_center-center_100_none'],
]);

it('invalidates eager-loaded indexes older than the asset file', function () {
    $asset = ($this->createImageAsset)();
    $transform = new ImageTransform([
        'width' => 100,
        'height' => 100,
        'mode' => 'crop',
    ]);

    $index = $this->transformer->getTransformIndex($asset, $transform);
    $asset->dateModified = now()->addMinute();

    $this->transformer->eagerLoadTransforms([$transform], [$asset]);

    expect(DB::table(Table::IMAGETRANSFORMINDEX)->where('id', $index->id)->exists())->toBeFalse();
});

it('stores dateIndexed as a DB-compatible UTC datetime string', function () {
    $asset = ($this->createImageAsset)();

    $index = new ImageTransformIndex([
        'assetId' => $asset->id,
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

it('uses Craft driver output disk settings', function () {
    config()->set('filesystems.disks.configured-transform-source', [
        'driver' => 'local',
        'root' => storage_path('framework/testing/image-transformer-test/configured-source'),
    ]);
    configureCraftTransformer(['outputDisk' => 'configured-transform-output']);
    config()->set('filesystems.disks.configured-transform-target', [
        'driver' => 'local',
        'root' => storage_path('framework/testing/image-transformer-test/configured-target'),
        'url' => 'https://transforms.example.test',
    ]);
    config()->set('filesystems.disks.configured-transform-output', [
        'driver' => 'scoped',
        'disk' => 'configured-transform-target',
        'prefix' => 'transforms',
    ]);
    Storage::disk('configured-transform-source')->deleteDirectory('');
    Storage::disk('configured-transform-target')->deleteDirectory('');
    $volume = Volume::factory()->create([
        'disk' => 'configured-transform-source',
        'hasUrls' => true,
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

    $result = app(AssetTransformers::class)->withImmediateTransforms(
        fn () => app(AssetTransformers::class)->transform($asset, ['width' => 100]),
    );

    expect($result->url)->toStartWith('https://transforms.example.test/transforms/')
        ->and(Storage::disk('configured-transform-target')->allFiles())->toHaveCount(1);
});

it('gives transforms direct URLs on their output disk only for public volumes', function (bool $volumeHasUrls, ?string $outputDisk, bool $direct) {
    $root = storage_path('framework/testing/image-transformer-test/url-cases');
    config()->set('filesystems.disks.url-source', ['driver' => 'local', 'root' => "{$root}/source"]);
    config()->set('filesystems.disks.url-output', ['driver' => 'local', 'root' => "{$root}/output", 'url' => 'https://transforms.example.test']);
    config()->set('filesystems.disks.no-url-output', ['driver' => 'local', 'root' => "{$root}/no-url-output"]);
    configureCraftTransformer(['outputDisk' => $outputDisk]);
    $volume = Volume::factory()->create(['disk' => 'url-source', 'hasUrls' => $volumeHasUrls]);
    $asset = ($this->createImageAsset)([
        'volumeId' => $volume->id,
        'folderId' => VolumeFolderModel::factory()->create(['volumeId' => $volume->id])->id,
    ]);
    $asset->getVolume()->sourceDisk()->put($asset->getPath(), file_get_contents(dirname(__DIR__, 2).'/_data/assets/files/background.jpg'));

    $url = app(AssetTransformers::class)->withImmediateTransforms(
        fn () => app(AssetTransformers::class)->transform($asset, ['width' => 100])->url,
    );

    expect(str_contains($url, 'generate-transform'))->toBe(! $direct);
})->with([
    'own disk without a url key, public' => [true, null, true],
    'own disk, private' => [false, null, false],
    'output disk with a url, public' => [true, 'url-output', true],
    'output disk without a url, public' => [true, 'no-url-output', true],
    'output disk with a url, private' => [false, 'url-output', false],
]);

it('stores temporary uploads’ transforms on their own disk, not the output disk', function () {
    $root = storage_path('framework/testing/image-transformer-test/temp-uploads');
    config()->set('filesystems.disks.temp-uploads', ['driver' => 'local', 'root' => "{$root}/temp"]);
    config()->set('filesystems.disks.public-output', ['driver' => 'local', 'root' => "{$root}/output", 'url' => 'https://cdn.example.test']);
    Storage::disk('temp-uploads')->deleteDirectory('');
    Storage::disk('public-output')->deleteDirectory('');
    Cms::config()->tempAssetUploadDisk = 'temp-uploads';
    configureCraftTransformer(['outputDisk' => 'public-output']);
    $asset = ($this->createImageAsset)(['filename' => 'passport-scan.jpg']);
    $asset->setVolumeId(null);
    expect($asset->getVolume()->isTemporary())->toBeTrue();
    $asset->getVolume()->sourceDisk()->put($asset->getPath(), file_get_contents(dirname(__DIR__, 2).'/_data/assets/files/background.jpg'));

    app(AssetTransformers::class)->withImmediateTransforms(
        fn () => app(AssetTransformers::class)->transform($asset, ['width' => 100]),
    );

    expect(Storage::disk('public-output')->allFiles())->toBe([])
        ->and(Storage::disk('temp-uploads')->allFiles())->toHaveCount(2);
});

it('stores transforms on a second craft transformer’s own output disk', function () {
    $root = storage_path('framework/testing/image-transformer-test/second-craft');
    config()->set('filesystems.disks.private-transforms', ['driver' => 'local', 'root' => "{$root}/output"]);
    Storage::disk('private-transforms')->deleteDirectory('');
    defineAssetTransformer('craft-private', ['driver' => 'craft', 'outputDisk' => 'private-transforms']);
    $volume = Volume::factory()->create(['disk' => 'test-disk', 'hasUrls' => true, 'assetTransformer' => 'craft-private']);
    $asset = ($this->createImageAsset)([
        'volumeId' => $volume->id,
        'folderId' => VolumeFolderModel::factory()->create(['volumeId' => $volume->id])->id,
    ]);
    $asset->getVolume()->sourceDisk()->put($asset->getPath(), file_get_contents(dirname(__DIR__, 2).'/_data/assets/files/background.jpg'));
    Queue::fake();

    $deferredUrl = app(AssetTransformers::class)->transform($asset, ['width' => 100])->url;
    $index = $this->transformer->getTransformIndexModelById((int) DB::table(Table::IMAGETRANSFORMINDEX)->where('assetId', $asset->id)->value('id'));
    $this->transformer->getTransformUrlForIndex($asset, $index, true);
    $transforms = fn (string $disk): array => array_values(array_filter(
        Storage::disk($disk)->allFiles(),
        fn (string $path): bool => str_starts_with($path, '_'),
    ));

    expect($deferredUrl)->toContain('generate-transform')
        ->and($transforms('private-transforms'))->toHaveCount(1)
        ->and($transforms('test-disk'))->toBe([]);

    $this->transformer->invalidateAssetTransforms($asset);

    expect($transforms('private-transforms'))->toBe([]);
});

it('does not reuse similar transform results across output locations', function () {
    foreach (['first-transform-target', 'second-transform-target'] as $disk) {
        config()->set("filesystems.disks.{$disk}", [
            'driver' => 'local',
            'root' => storage_path("framework/testing/image-transformer-test/{$disk}"),
        ]);
        Storage::disk($disk)->deleteDirectory('');
    }

    $asset = ($this->createImageAsset)();
    $asset->getVolume()->sourceDisk()->put(
        $asset->getPath(),
        file_get_contents(dirname(__DIR__, 2).'/_data/assets/files/background.jpg'),
    );
    app(AssetTransformers::class)->withImmediateTransforms(function () use ($asset): void {
        foreach (['first', 'second'] as $target) {
            configureCraftTransformer(['outputDisk' => "{$target}-transform-target"]);
            app(AssetTransformers::class)->transform($asset, ['width' => 100]);
        }
    });

    expect(Storage::disk('first-transform-target')->allFiles())->toHaveCount(1)
        ->and(Storage::disk('second-transform-target')->allFiles())->toHaveCount(1);
});

it('fails when the configured Craft driver output disk doesn’t name a disk', function (string $outputDisk) {
    putenv('CRAFT_TEST_TRANSFORM_OUTPUT_DISK=test-disk');
    config()->set('filesystems.disks.missing-transform-target-source', [
        'driver' => 'local',
        'root' => storage_path('framework/testing/image-transformer-test/missing-target-source'),
    ]);
    configureCraftTransformer(['outputDisk' => $outputDisk]);
    $volume = Volume::factory()->create([
        'disk' => 'missing-transform-target-source',
    ]);
    $asset = AssetModel::factory()->createElement(['volumeId' => $volume->id]);

    try {
        app(AssetTransformers::class)->transform($asset, ['width' => 100]);
    } finally {
        putenv('CRAFT_TEST_TRANSFORM_OUTPUT_DISK');
    }
})->throws(FilesystemException::class)->with([
    'missing disk' => 'missing-transform-target',
    'environment variable reference' => '$CRAFT_TEST_TRANSFORM_OUTPUT_DISK',
]);

it('cleans the Craft transform index when an Asset is invalidated', function () {
    $asset = ($this->createImageAsset)();
    $disk = $asset->getVolume()->sourceDisk();
    $disk->put(
        $asset->getPath(),
        file_get_contents(dirname(__DIR__, 2).'/_data/assets/files/background.jpg'),
    );
    app(AssetTransformers::class)->withImmediateTransforms(
        fn () => app(AssetTransformers::class)->transform($asset, ['width' => 100]),
    );
    $transformPath = collect($disk->allFiles())->sole(fn (string $path): bool => $path !== $asset->getPath());

    event(new AssetTransformsInvalidating($asset));

    expect($disk->exists($transformPath))->toBeFalse();
});

it('reports Craft cleanup failures with redacted context', function () {
    $asset = ($this->createImageAsset)();
    $index = $this->transformer->getTransformIndex($asset, new ImageTransform(['width' => 100]));
    Exceptions::fake();
    Storage::shouldReceive('disk')->with('test-disk')->andThrow(new RuntimeException('/secret/source/path.jpg'));

    $this->transformer->deleteImageTransformFile($asset, $index);

    Exceptions::assertReported(fn (RuntimeException $exception): bool => str_contains($exception->getMessage(), 'craft')
        && str_contains($exception->getMessage(), (string) $asset->id)
        && ! str_contains($exception->getMessage(), '/secret/source/path.jpg'));
});

it('stores transforms at the root of the volume’s own disk, outside its subpath, without an output disk', function () {
    $volume = Volume::factory()->create(['disk' => 'test-disk', 'subpath' => 'uploads', 'hasUrls' => true]);
    $asset = ($this->createImageAsset)([
        'volumeId' => $volume->id,
        'folderId' => VolumeFolderModel::factory()->create(['volumeId' => $volume->id])->id,
    ]);
    $asset->getVolume()->sourceDisk()->put($asset->getPath(), file_get_contents(dirname(__DIR__, 2).'/_data/assets/files/background.jpg'));

    $url = app(AssetTransformers::class)->withImmediateTransforms(
        fn () => app(AssetTransformers::class)->transform($asset, ['width' => 100])->url,
    );
    $transformPath = collect(Storage::disk('test-disk')->allFiles())
        ->sole(fn (string $path): bool => $path !== 'uploads/'.$asset->getPath());

    expect($transformPath)->toStartWith('_'.$volume->uid.'/')
        ->and($url)->toStartWith('https://example.test/image-transformer-test/_'.$volume->uid.'/');
});

it('excludes generated transform directories from asset indexing with the default transformer', function () {
    $asset = ($this->createImageAsset)();
    $disk = $asset->getVolume()->sourceDisk();
    $disk->put($asset->getPath(), file_get_contents(dirname(__DIR__, 2).'/_data/assets/files/background.jpg'));
    $disk->put('photos/original.txt', 'original');
    $disk->put('_visible.txt', 'visible');

    app(AssetTransformers::class)->withImmediateTransforms(
        fn () => app(AssetTransformers::class)->transform($asset, ['width' => 190, 'height' => 190]),
    );

    expect($disk->allFiles())->toHaveCount(4);

    $session = app(AssetIndexer::class)->startIndexingSession(
        volumes: [$asset->volumeId],
        cacheRemoteImages: false,
        listEmptyFolders: true,
    );

    expect(AssetIndexData::where('sessionId', $session->id)->pluck('uri')->all())
        ->toEqualCanonicalizing(['_visible.txt', 'photos', 'photos/original.txt', 'transform-test.jpg']);
});

it('reuses transforms when only immediate generation changes', function () {
    $asset = ($this->createImageAsset)();
    $output = useTransformOutputDisk($asset);
    $transform = fn () => app(AssetTransformers::class)->withImmediateTransforms(
        fn () => app(AssetTransformers::class)->transform($asset, ['width' => 100]),
    );

    $transform();
    configureCraftTransformer(['outputDisk' => 'transform-output', 'generateTransformsBeforePageLoad' => true]);
    $transform();

    expect($output->allFiles())->toHaveCount(1)
        ->and(DB::table(Table::IMAGETRANSFORMINDEX)->where('assetId', $asset->id)->count())->toBe(1);
});

it('does not reuse a transform result older than the source Asset', function () {
    $asset = ($this->createImageAsset)();
    $disk = $asset->getVolume()->sourceDisk();
    $disk->put(
        $asset->getPath(),
        file_get_contents(dirname(__DIR__, 2).'/_data/assets/files/background.jpg'),
    );
    app(AssetTransformers::class)->withImmediateTransforms(
        fn () => app(AssetTransformers::class)->transform($asset, ['width' => 100]),
    );
    $transformPath = collect($disk->allFiles())->sole(fn (string $path): bool => $path !== $asset->getPath());
    $originalTransform = $disk->get($transformPath);

    $disk->put(
        $asset->getPath(),
        file_get_contents(dirname(__DIR__, 2).'/_data/assets/files/image-rotated-180.jpg'),
    );
    $asset->dateModified = now()->addMinute();

    app(AssetTransformers::class)->withImmediateTransforms(
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

    $transformers = app(AssetTransformers::class);
    $firstUrl = $transformers->withImmediateTransforms(
        fn () => app(AssetTransformers::class)->transform($asset, ['width' => 100])->url,
    );
    $asset->dateUpdated = now()->addMinute();
    $secondUrl = $transformers->withImmediateTransforms(
        fn () => app(AssetTransformers::class)->transform($asset, ['width' => 100])->url,
    );

    expect($secondUrl)->not->toBe($firstUrl);
});

it('rejects unsupported source kinds before generating transforms', function () {
    $asset = ($this->createImageAsset)([
        'filename' => 'transform-test.txt',
        'kind' => 'text',
    ]);
    expect(fn () => app(AssetTransformers::class)->transformer('craft')->transform(new AssetTransformRequest(
        $asset,
        new AssetTransformer('craft'),
        [
            'width' => 100,
            'height' => 100,
            'mode' => 'crop',
        ],
    )))->toThrow(NotSupportedException::class);
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

    $this->travel(-31)->seconds();

    expect($this->transformer->getTransformIndex($asset, $transform)->inProgress)->toBeTrue();
})->with(['UTC', 'America/Los_Angeles', 'Asia/Tokyo']);

it('queues abandoned in-progress transforms for generation', function () {
    $this->freezeSecond();
    $asset = ($this->createImageAsset)();
    $index = $this->transformer->getTransformIndex($asset, new ImageTransform(['width' => 100]));
    $index->inProgress = true;
    $this->transformer->storeTransformIndexData($index);

    expect($this->transformer->getPendingTransformIndexIds())->not->toContain($index->id);

    $this->travel(31)->seconds();

    expect($this->transformer->getPendingTransformIndexIds())->toContain($index->id);
});

it('queues a transform that failed once again after its file is lost', function () {
    Queue::fake();
    $asset = ($this->createImageAsset)();
    $asset->getVolume()->sourceDisk()->put($asset->getPath(), file_get_contents(dirname(__DIR__, 2).'/_data/assets/files/background.jpg'));
    $index = $this->transformer->getTransformIndex($asset, new ImageTransform(['width' => 100]));
    $index->error = true;
    $this->transformer->storeTransformIndexData($index);

    $this->transformer->getTransformUrlForIndex($asset, $index, true);

    $index = $this->transformer->getTransformIndexModelById($index->id);
    $index->fileExists = false;
    $this->transformer->storeTransformIndexData($index);

    expect($this->transformer->getPendingTransformIndexIds())->toContain($index->id);
});

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

/**
 * Sends the test disk’s transforms to a separate, empty output disk, and stores the asset’s source file.
 */
function useTransformOutputDisk(Asset $asset): Filesystem
{
    config()->set('filesystems.disks.transform-output', [
        'driver' => 'local',
        'root' => storage_path('framework/testing/image-transformer-test/transform-output'),
    ]);
    configureCraftTransformer(['outputDisk' => 'transform-output']);
    $output = Storage::disk('transform-output');
    $output->deleteDirectory('');
    $asset->getVolume()->sourceDisk()->put(
        $asset->getPath(),
        file_get_contents(dirname(__DIR__, 2).'/_data/assets/files/background.jpg'),
    );

    return $output;
}
