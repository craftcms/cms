<?php

declare(strict_types=1);

use CraftCms\Cms\Asset\AssetProcessorDrivers;
use CraftCms\Cms\Asset\AssetProcessors;
use CraftCms\Cms\Asset\Contracts\AssetProcessorDriver;
use CraftCms\Cms\Asset\Contracts\PreloadsAssetTransforms;
use CraftCms\Cms\Asset\Data\AssetProcessor;
use CraftCms\Cms\Asset\Data\AssetProcessorDriverDefinition;
use CraftCms\Cms\Asset\Data\AssetTransformRequest;
use CraftCms\Cms\Asset\Data\AssetTransformResult;
use CraftCms\Cms\Asset\Data\Volume as VolumeData;
use CraftCms\Cms\Asset\Exceptions\AssetProcessorNotFoundException;
use CraftCms\Cms\Asset\Exceptions\AssetTransformException;
use CraftCms\Cms\Asset\Exceptions\InvalidAssetTransformException;
use CraftCms\Cms\Asset\Models\Asset;
use CraftCms\Cms\Asset\Models\Volume;
use CraftCms\Cms\Asset\Volumes;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Image\Data\ImageTransform;
use CraftCms\Cms\Image\ImageTransforms;
use CraftCms\Cms\ProjectConfig\ProjectConfig;
use CraftCms\Cms\Support\Str;

it('resolves the Craft processor without writing project config', function () {
    $projectConfig = app(ProjectConfig::class);
    $projectConfig->readOnly = true;

    $processors = app(AssetProcessors::class);
    $processor = $processors->resolve('craft');
    $processors->reset();

    expect($processor->handle)->toBe('craft')
        ->and($processors->resolve('craft')->uid)->toBe($processor->uid)
        ->and($projectConfig->get(ProjectConfig::PATH_ASSET_PROCESSORS))->toBeNull();
});

it('executes the selected configured processor', function () {
    $driver = registerProcessor('remote', ['token' => 'secret']);
    $asset = Asset::factory()->createElement();

    $result = app(AssetProcessors::class)->transform($asset, [
        'processor' => 'remote',
        'format' => 'webp',
        'width' => '1200',
    ], true);

    expect($result->url)->toBe('/renditions/hero.webp')
        ->and($driver->request->asset)->toBe($asset)
        ->and($driver->request->processor->handle)->toBe('remote')
        ->and($driver->request->processor->settings)->toBe(['token' => 'secret'])
        ->and($driver->request->operations)->toBe(['format' => 'webp', 'width' => '1200'])
        ->and($driver->request->immediately)->toBeTrue();
});

it('selects inline, volume, and default processors in that order', function () {
    $explicit = registerProcessor('explicit');
    $volumeProcessor = registerProcessor('volume');
    $default = registerProcessor('default');
    Cms::config()->defaultAssetProcessor('default');
    config()->set('filesystems.disks.processor-source', [
        'driver' => 'local',
        'root' => storage_path('framework/testing/processor-source'),
    ]);
    $volume = Volume::factory()->create([
        'fs' => 'disk:processor-source',
        'assetProcessor' => 'volume',
    ]);
    $asset = Asset::factory()->createElement(['volumeId' => $volume->id]);
    $defaultAsset = Asset::factory()->createElement();

    app(AssetProcessors::class)->transform($asset, ['width' => 100]);
    app(AssetProcessors::class)->transform($asset, ['processor' => 'explicit', 'width' => 200]);
    app(AssetProcessors::class)->transform($defaultAsset, ['width' => 300]);

    expect($volumeProcessor->request->operations['width'])->toBe(100)
        ->and($explicit->request->operations['width'])->toBe(200)
        ->and($default->request->operations['width'])->toBe(300);
});

it('uses the global immediate-generation policy by default', function () {
    $driver = registerProcessor('remote');
    Cms::config()
        ->defaultAssetProcessor('remote')
        ->generateTransformsBeforePageLoad();

    app(AssetProcessors::class)->transform(Asset::factory()->createElement(), ['width' => 100]);

    expect($driver->request->immediately)->toBeTrue();
});

it('protects processors referenced by volumes', function () {
    config()->set('filesystems.disks.processor-reference', [
        'driver' => 'local',
        'root' => storage_path('framework/testing/processor-reference'),
    ]);
    $processor = new AssetProcessor([
        'name' => 'Referenced',
        'handle' => 'referenced',
        'driver' => 'craft',
    ]);
    $service = app(AssetProcessors::class);
    $service->saveAssetProcessor($processor);
    $volume = new VolumeData([
        'name' => 'Referenced Volume',
        'handle' => 'referencedVolume',
        'fsHandle' => 'disk:processor-reference',
        'assetProcessor' => 'referenced',
    ]);
    app(Volumes::class)->saveVolume($volume);

    expect(fn () => $service->deleteAssetProcessor($processor))
        ->toThrow(AssetTransformException::class);
});

it('rewrites volume references when a processor handle changes', function () {
    config()->set('filesystems.disks.processor-reference', [
        'driver' => 'local',
        'root' => storage_path('framework/testing/processor-reference'),
    ]);
    $processor = new AssetProcessor([
        'name' => 'Referenced',
        'handle' => 'referenced',
        'driver' => 'craft',
    ]);
    $service = app(AssetProcessors::class);
    $service->saveAssetProcessor($processor);
    $volume = new VolumeData([
        'name' => 'Referenced Volume',
        'handle' => 'referencedVolume',
        'fsHandle' => 'disk:processor-reference',
        'assetProcessor' => 'referenced',
    ]);
    app(Volumes::class)->saveVolume($volume);

    $processor->handle = 'renamed';
    $service->saveAssetProcessor($processor);
    app()->forgetInstance(Volumes::class);

    expect(app(Volumes::class)->getVolumeByHandle('referencedVolume')?->getAssetProcessorHandle(false))
        ->toBe('renamed');
});

it('uses processor-specific named operations', function () {
    $first = registerProcessor('first', operations: ['blur' => ['integer']]);
    $second = registerProcessor('second', operations: ['sharpen' => ['integer']]);
    $transform = new ImageTransform([
        'name' => 'Hero',
        'handle' => 'hero',
        'width' => 1200,
        'operations' => [
            processor('first')->uid => ['blur' => 8],
            processor('second')->uid => ['sharpen' => 4],
        ],
    ]);
    app(ImageTransforms::class)->saveTransform($transform);
    $asset = Asset::factory()->createElement();

    app(AssetProcessors::class)->transform($asset, ['transform' => 'hero', 'processor' => 'first']);
    app(AssetProcessors::class)->transform($asset, ['transform' => 'hero', 'processor' => 'second']);

    expect($first->request->operations)->toMatchArray(['width' => 1200, 'blur' => 8])
        ->and($first->request->operations)->not->toHaveKey('sharpen')
        ->and($second->request->operations)->toMatchArray(['width' => 1200, 'sharpen' => 4])
        ->and($second->request->operations)->not->toHaveKey('blur');
});

it('ignores undeclared operations', function () {
    $driver = registerProcessor('remote', operations: ['blur' => ['integer']]);

    app(AssetProcessors::class)->transform(Asset::factory()->createElement(), [
        'processor' => 'remote',
        'blur' => 5,
        'unknown' => 'ignored',
    ]);

    expect($driver->request->operations)->toBe(['blur' => 5]);
});

it('rejects invalid operations and missing processor handles', function () {
    registerProcessor('remote');
    $asset = Asset::factory()->createElement();

    expect(fn () => app(AssetProcessors::class)->transform($asset, [
        'processor' => 'remote',
        'width' => 0,
    ]))->toThrow(InvalidAssetTransformException::class)
        ->and(fn () => app(AssetProcessors::class)->transform($asset, [
            'processor' => 'missing',
        ]))->toThrow(AssetProcessorNotFoundException::class);
});

it('preloads requests grouped by driver', function () {
    $firstDriver = new TestPreloadingAssetProcessorDriver;
    $secondDriver = new TestPreloadingAssetProcessorDriver;
    registerProcessor('first', driver: $firstDriver);
    registerProcessor('second', driver: $secondDriver);
    $assets = collect(['first', 'second'])->map(function (string $handle) {
        config()->set("filesystems.disks.{$handle}-processor-source", [
            'driver' => 'local',
            'root' => storage_path("framework/testing/{$handle}-processor-source"),
        ]);
        $volume = Volume::factory()->create([
            'fs' => "disk:{$handle}-processor-source",
            'assetProcessor' => $handle,
        ]);

        return Asset::factory()->createElement(['volumeId' => $volume->id]);
    })->all();

    app(AssetProcessors::class)->preload($assets, [['width' => 320]]);

    expect($firstDriver->requests)->toHaveCount(1)
        ->and($firstDriver->requests[0]->asset)->toBe($assets[0])
        ->and($firstDriver->requests[0]->processor->handle)->toBe('first')
        ->and($secondDriver->requests)->toHaveCount(1)
        ->and($secondDriver->requests[0]->asset)->toBe($assets[1])
        ->and($secondDriver->requests[0]->processor->handle)->toBe('second');
});

it('redacts processor settings from debug output', function () {
    $request = new AssetTransformRequest(
        Asset::factory()->createElement(),
        processor('remote', ['token' => 'secret-value']),
        [],
        false,
    );
    ob_start();
    var_dump($request);
    $output = ob_get_clean();

    expect($output)->toContain('[redacted]')
        ->not->toContain('secret-value');
});

/** @param array<string, mixed> $settings */
function processor(string $handle, array $settings = []): AssetProcessor
{
    return app(AssetProcessors::class)->getAssetProcessorByHandle($handle)
        ?? new AssetProcessor([
            'uid' => Str::uuid()->toString(),
            'name' => ucfirst($handle),
            'handle' => $handle,
            'driver' => $handle,
            'settings' => $settings,
        ]);
}

/**
 * @param  array<string, mixed>  $settings
 * @param  array<string, non-empty-list<string|Stringable>>  $operations
 */
function registerProcessor(
    string $handle,
    array $settings = [],
    array $operations = [],
    ?TestAssetProcessorDriver $driver = null,
): TestAssetProcessorDriver {
    $driver ??= new TestAssetProcessorDriver(new AssetProcessorDriverDefinition(ucfirst($handle), $operations));
    app(AssetProcessorDrivers::class)->extend($handle, fn () => $driver);
    app(AssetProcessors::class)->saveAssetProcessor(new AssetProcessor([
        'uid' => Str::uuid()->toString(),
        'name' => ucfirst($handle),
        'handle' => $handle,
        'driver' => $handle,
        'settings' => $settings,
    ]), false);

    return $driver;
}

class TestAssetProcessorDriver implements AssetProcessorDriver
{
    public ?AssetTransformRequest $request = null;

    public function __construct(
        private readonly ?AssetProcessorDriverDefinition $driverDefinition = null,
    ) {}

    public function definition(): AssetProcessorDriverDefinition
    {
        return $this->driverDefinition ?? new AssetProcessorDriverDefinition('Test');
    }

    public function transform(AssetTransformRequest $request): AssetTransformResult
    {
        $this->request = $request;

        return new AssetTransformResult('/renditions/hero.webp', 'image/webp');
    }
}

class TestPreloadingAssetProcessorDriver extends TestAssetProcessorDriver implements PreloadsAssetTransforms
{
    /** @var list<AssetTransformRequest> */
    public array $requests = [];

    public function preloadAssetTransforms(array $requests): void
    {
        $this->requests = $requests;
    }
}
