<?php

declare(strict_types=1);

use CraftCms\Cms\Asset\AssetTransformDrivers;
use CraftCms\Cms\Asset\AssetTransformers;
use CraftCms\Cms\Asset\Contracts\AssetTransformDriver;
use CraftCms\Cms\Asset\Contracts\PreloadsAssetTransforms;
use CraftCms\Cms\Asset\Data\AssetTransformDriverDefinition;
use CraftCms\Cms\Asset\Data\AssetTransformer;
use CraftCms\Cms\Asset\Data\AssetTransformRequest;
use CraftCms\Cms\Asset\Data\AssetTransformResult;
use CraftCms\Cms\Asset\Data\Volume as VolumeData;
use CraftCms\Cms\Asset\Exceptions\AssetTransformerNotFoundException;
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

it('resolves the Craft transformer without writing project config', function () {
    $projectConfig = app(ProjectConfig::class);
    $projectConfig->readOnly = true;

    $transformers = app(AssetTransformers::class);
    $transformer = $transformers->resolve('craft');
    $transformers->reset();

    expect($transformer->handle)->toBe('craft')
        ->and($transformers->resolve('craft')->uid)->toBe($transformer->uid)
        ->and($projectConfig->get(ProjectConfig::PATH_ASSET_TRANSFORMERS))->toBeNull();
});

it('executes the selected configured transformer', function () {
    $driver = registerTransformer('remote', ['token' => 'secret']);
    $asset = Asset::factory()->createElement();

    $result = app(AssetTransformers::class)->transform($asset, [
        'transformer' => 'remote',
        'format' => 'webp',
        'width' => '1200',
    ], true);

    expect($result->url)->toBe('/transforms/hero.webp')
        ->and($driver->request->asset)->toBe($asset)
        ->and($driver->request->transformer->handle)->toBe('remote')
        ->and($driver->request->transformer->settings)->toBe(['token' => 'secret'])
        ->and($driver->request->parameters)->toBe(['format' => 'webp', 'width' => '1200'])
        ->and($driver->request->immediately)->toBeTrue();
});

it('selects inline, volume, and default transformers in that order', function () {
    $explicit = registerTransformer('explicit');
    $volumeTransformer = registerTransformer('volume');
    $default = registerTransformer('default');
    Cms::config()->defaultAssetTransformer('default');
    config()->set('filesystems.disks.transformer-source', [
        'driver' => 'local',
        'root' => storage_path('framework/testing/transformer-source'),
    ]);
    $volume = Volume::factory()->create([
        'fs' => 'disk:transformer-source',
        'assetTransformer' => 'volume',
    ]);
    $asset = Asset::factory()->createElement(['volumeId' => $volume->id]);
    $defaultAsset = Asset::factory()->createElement();

    app(AssetTransformers::class)->transform($asset, ['width' => 100]);
    app(AssetTransformers::class)->transform($asset, ['transformer' => 'explicit', 'width' => 200]);
    app(AssetTransformers::class)->transform($defaultAsset, ['width' => 300]);

    expect($volumeTransformer->request->parameters['width'])->toBe(100)
        ->and($explicit->request->parameters['width'])->toBe(200)
        ->and($default->request->parameters['width'])->toBe(300);
});

it('uses the global immediate-generation policy by default', function () {
    $driver = registerTransformer('remote');
    Cms::config()
        ->defaultAssetTransformer('remote')
        ->generateTransformsBeforePageLoad();

    app(AssetTransformers::class)->transform(Asset::factory()->createElement(), ['width' => 100]);

    expect($driver->request->immediately)->toBeTrue();
});

it('protects transformers referenced by volumes', function () {
    config()->set('filesystems.disks.transformer-reference', [
        'driver' => 'local',
        'root' => storage_path('framework/testing/transformer-reference'),
    ]);
    $transformer = new AssetTransformer([
        'name' => 'Referenced',
        'handle' => 'referenced',
        'driver' => 'craft',
    ]);
    $service = app(AssetTransformers::class);
    $service->saveAssetTransformer($transformer);
    $volume = new VolumeData([
        'name' => 'Referenced Volume',
        'handle' => 'referencedVolume',
        'fsHandle' => 'disk:transformer-reference',
        'assetTransformer' => 'referenced',
    ]);
    app(Volumes::class)->saveVolume($volume);

    expect(fn () => $service->deleteAssetTransformer($transformer))
        ->toThrow(AssetTransformException::class);
});

it('rewrites volume references when a transformer handle changes', function () {
    config()->set('filesystems.disks.transformer-reference', [
        'driver' => 'local',
        'root' => storage_path('framework/testing/transformer-reference'),
    ]);
    $transformer = new AssetTransformer([
        'name' => 'Referenced',
        'handle' => 'referenced',
        'driver' => 'craft',
    ]);
    $service = app(AssetTransformers::class);
    $service->saveAssetTransformer($transformer);
    $volume = new VolumeData([
        'name' => 'Referenced Volume',
        'handle' => 'referencedVolume',
        'fsHandle' => 'disk:transformer-reference',
        'assetTransformer' => 'referenced',
    ]);
    app(Volumes::class)->saveVolume($volume);

    $transformer->handle = 'renamed';
    $service->saveAssetTransformer($transformer);
    app()->forgetInstance(Volumes::class);

    expect(app(Volumes::class)->getVolumeByHandle('referencedVolume')?->getAssetTransformerHandle(false))
        ->toBe('renamed');
});

it('uses transformer-specific named parameters', function () {
    $first = registerTransformer('first', parameterRules: ['blur' => ['integer']]);
    $second = registerTransformer('second', parameterRules: ['sharpen' => ['integer']]);
    $transform = new ImageTransform([
        'name' => 'Hero',
        'handle' => 'hero',
        'width' => 1200,
        'parameters' => [
            transformer('first')->uid => ['blur' => 8],
            transformer('second')->uid => ['sharpen' => 4],
        ],
    ]);
    app(ImageTransforms::class)->saveTransform($transform);
    $asset = Asset::factory()->createElement();

    app(AssetTransformers::class)->transform($asset, ['transform' => 'hero', 'transformer' => 'first']);
    app(AssetTransformers::class)->transform($asset, ['transform' => 'hero', 'transformer' => 'second']);

    expect($first->request->parameters)->toMatchArray(['width' => 1200, 'blur' => 8])
        ->and($first->request->parameters)->not->toHaveKey('sharpen')
        ->and($second->request->parameters)->toMatchArray(['width' => 1200, 'sharpen' => 4])
        ->and($second->request->parameters)->not->toHaveKey('blur');
});

it('passes undeclared parameters without validating them', function () {
    $driver = registerTransformer('remote', parameterRules: ['blur' => ['integer']]);

    app(AssetTransformers::class)->transform(Asset::factory()->createElement(), [
        'transformer' => 'remote',
        'unknown' => 'passed-through',
        'blur' => 5,
    ]);

    expect($driver->request->parameters)->toBe([
        'blur' => 5,
        'unknown' => 'passed-through',
    ]);
});

it('rejects invalid parameters and missing transformer handles', function () {
    registerTransformer('remote');
    $asset = Asset::factory()->createElement();

    expect(fn () => app(AssetTransformers::class)->transform($asset, [
        'transformer' => 'remote',
        'width' => 0,
    ]))->toThrow(InvalidAssetTransformException::class)
        ->and(fn () => app(AssetTransformers::class)->transform($asset, [
            'transformer' => 'missing',
        ]))->toThrow(AssetTransformerNotFoundException::class);
});

it('preloads requests grouped by driver', function () {
    $firstDriver = new TestPreloadingAssetTransformDriver;
    $secondDriver = new TestPreloadingAssetTransformDriver;
    registerTransformer('first', driver: $firstDriver);
    registerTransformer('second', driver: $secondDriver);
    $assets = collect(['first', 'second'])->map(function (string $handle) {
        config()->set("filesystems.disks.{$handle}-transformer-source", [
            'driver' => 'local',
            'root' => storage_path("framework/testing/{$handle}-transformer-source"),
        ]);
        $volume = Volume::factory()->create([
            'fs' => "disk:{$handle}-transformer-source",
            'assetTransformer' => $handle,
        ]);

        return Asset::factory()->createElement(['volumeId' => $volume->id]);
    })->all();

    app(AssetTransformers::class)->preload($assets, [['width' => 320]]);

    expect($firstDriver->requests)->toHaveCount(1)
        ->and($firstDriver->requests[0]->asset)->toBe($assets[0])
        ->and($firstDriver->requests[0]->transformer->handle)->toBe('first')
        ->and($secondDriver->requests)->toHaveCount(1)
        ->and($secondDriver->requests[0]->asset)->toBe($assets[1])
        ->and($secondDriver->requests[0]->transformer->handle)->toBe('second');
});

it('redacts transformer settings from debug output', function () {
    $request = new AssetTransformRequest(
        Asset::factory()->createElement(),
        transformer('remote', ['token' => 'secret-value']),
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
function transformer(string $handle, array $settings = []): AssetTransformer
{
    return app(AssetTransformers::class)->getAssetTransformerByHandle($handle)
        ?? new AssetTransformer([
            'uid' => Str::uuid()->toString(),
            'name' => ucfirst($handle),
            'handle' => $handle,
            'driver' => $handle,
            'settings' => $settings,
        ]);
}

/**
 * @param  array<string, mixed>  $settings
 * @param  array<string, non-empty-list<string|Stringable>>  $parameterRules
 */
function registerTransformer(
    string $handle,
    array $settings = [],
    array $parameterRules = [],
    ?TestAssetTransformDriver $driver = null,
): TestAssetTransformDriver {
    $driver ??= new TestAssetTransformDriver(new AssetTransformDriverDefinition(ucfirst($handle), $parameterRules));
    app(AssetTransformDrivers::class)->extend($handle, fn () => $driver);
    app(AssetTransformers::class)->saveAssetTransformer(new AssetTransformer([
        'uid' => Str::uuid()->toString(),
        'name' => ucfirst($handle),
        'handle' => $handle,
        'driver' => $handle,
        'settings' => $settings,
    ]), false);

    return $driver;
}

class TestAssetTransformDriver implements AssetTransformDriver
{
    public ?AssetTransformRequest $request = null;

    public function __construct(
        private readonly ?AssetTransformDriverDefinition $driverDefinition = null,
    ) {}

    public function definition(): AssetTransformDriverDefinition
    {
        return $this->driverDefinition ?? new AssetTransformDriverDefinition('Test');
    }

    public function transform(AssetTransformRequest $request): AssetTransformResult
    {
        $this->request = $request;

        return new AssetTransformResult('/transforms/hero.webp', 'image/webp');
    }
}

class TestPreloadingAssetTransformDriver extends TestAssetTransformDriver implements PreloadsAssetTransforms
{
    /** @var list<AssetTransformRequest> */
    public array $requests = [];

    public function preloadAssetTransforms(array $requests): void
    {
        $this->requests = $requests;
    }
}
