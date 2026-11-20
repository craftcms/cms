<?php

declare(strict_types=1);

use CraftCms\Cms\Asset\AssetTransformers;
use CraftCms\Cms\Asset\Contracts\AssetTransformDriver;
use CraftCms\Cms\Asset\Contracts\PreloadsAssetTransforms;
use CraftCms\Cms\Asset\Data\AssetTransformDriverDefinition;
use CraftCms\Cms\Asset\Data\AssetTransformer;
use CraftCms\Cms\Asset\Data\AssetTransformRequest;
use CraftCms\Cms\Asset\Data\AssetTransformResult;
use CraftCms\Cms\Asset\Elements\Asset as AssetElement;
use CraftCms\Cms\Asset\Exceptions\AssetTransformDriverNotFoundException;
use CraftCms\Cms\Asset\Exceptions\InvalidAssetTransformException;
use CraftCms\Cms\Asset\Models\Asset;
use CraftCms\Cms\Asset\Models\Volume;
use CraftCms\Cms\Asset\Models\VolumeFolder;
use CraftCms\Cms\Asset\Volumes;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Image\CraftAssetTransformDriver;
use CraftCms\Cms\Image\Data\ImageTransform;
use CraftCms\Cms\Image\ImageTransformer;
use CraftCms\Cms\Image\ImageTransforms;
use CraftCms\Cms\Shared\Exceptions\NotSupportedException;
use CraftCms\Cms\Support\Facades\Deprecator;
use CraftCms\Cms\User\Elements\User as UserElement;
use CraftCms\Cms\User\Models\User;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Facades\Exceptions;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

it('uses the volume’s transformer, then the configured default, then craft', function () {
    $volumeDriver = registerDriver('volume-driver');
    $defaultDriver = registerDriver('default-driver');
    putenv('CRAFT_TEST_ASSET_TRANSFORMER=volume-driver');
    $_SERVER['CRAFT_TEST_ASSET_TRANSFORMER'] = 'volume-driver';
    $transformers = app(AssetTransformers::class);

    try {
        $named = assetOnVolume('named-source', ['assetTransformer' => 'volume-driver']);
        $envSet = assetOnVolume('env-set-source', ['assetTransformer' => '$CRAFT_TEST_ASSET_TRANSFORMER']);
        $envUnset = assetOnVolume('env-unset-source', ['assetTransformer' => '$CRAFT_TEST_UNSET_ASSET_TRANSFORMER']);
        $unset = assetOnVolume('unset-source');

        expect($transformers->forVolume($unset->getVolume())->name)->toBe('craft');

        useAssetTransformer('default-driver');
        $transformers->transform($named, ['width' => 100]);

        expect($volumeDriver->request->asset)->toBe($named)
            ->and($volumeDriver->request->parameters['width'])->toBe(100)
            ->and($transformers->forVolume($envSet->getVolume())->name)->toBe('volume-driver')
            ->and($transformers->forVolume($envUnset->getVolume())->name)->toBe('default-driver')
            ->and($transformers->forVolume($unset->getVolume())->name)->toBe('default-driver')
            ->and($defaultDriver->request)->toBeNull();
    } finally {
        putenv('CRAFT_TEST_ASSET_TRANSFORMER');
        unset($_SERVER['CRAFT_TEST_ASSET_TRANSFORMER']);
    }
});

it('builds each transformer from its own entry, so two can share a driver', function () {
    $received = [];
    app(AssetTransformers::class)->extend('remote', function ($app, array $config) use (&$received): TestAssetTransformDriver {
        $received[] = $config;

        return new TestAssetTransformDriver;
    });
    defineAssetTransformer('remote', ['domain' => 'first.example.test']);
    defineAssetTransformer('remote-v2', ['driver' => 'remote', 'domain' => 'second.example.test']);
    $first = assetOnVolume('first-source', ['assetTransformer' => 'remote']);
    $second = assetOnVolume('second-source', ['assetTransformer' => 'remote-v2']);
    $transformers = app(AssetTransformers::class);

    expect($transformers->transformer('remote'))->not->toBe($transformers->transformer('remote-v2'))
        ->and($received)->toBe([
            ['driver' => 'remote', 'domain' => 'first.example.test'],
            ['driver' => 'remote', 'domain' => 'second.example.test'],
        ])
        ->and($transformers->forVolume($first->getVolume())->name)->toBe('remote')
        ->and($transformers->forVolume($second->getVolume())->name)->toBe('remote-v2');
});

it('builds the craft driver from its settings', function () {
    configureCraftTransformer(['outputDisk' => 'transforms', 'generateTransformsBeforePageLoad' => true]);
    $driver = app(AssetTransformers::class)->transformer('craft');

    expect($driver)->toBeInstanceOf(CraftAssetTransformDriver::class)
        ->and($driver->outputDisk)->toBe('transforms')
        ->and($driver->generateBeforePageLoad)->toBeTrue();
});

it('reserves the craft driver name', function () {
    expect(fn () => app(AssetTransformers::class)->extend('craft', fn () => new TestAssetTransformDriver))
        ->toThrow(InvalidArgumentException::class);
});

it('throws for invalid transformer config with debug mode on', function (string $value) {
    config()->set('app.debug', true);
    defineAssetTransformer('unregistered', ['driver' => 'missing']);
    defineAssetTransformer('case-variant', ['driver' => 'CRAFT']);
    $volume = assetOnVolume('broken-source', ['handle' => 'broken', 'assetTransformer' => $value])->getVolume();
    Cms::config()->assetTransformers->default = $value;
    $default = assetOnVolume('default-source')->getVolume();

    expect(fn () => app(AssetTransformers::class)->forVolume($volume))
        ->toThrow(AssetTransformDriverNotFoundException::class, '[broken] volume')
        ->and(fn () => app(AssetTransformers::class)->forVolume($default))
        ->toThrow(AssetTransformDriverNotFoundException::class, 'assetTransformers default');
})->with([
    'an undefined transformer' => 'missing',
    'a transformer with an unregistered driver' => 'unregistered',
    'a transformer whose driver is a case variant of craft' => 'case-variant',
    'a name with no letters' => '-',
]);

it('logs invalid transformer config once and falls back to craft with debug mode off', function () {
    config()->set('app.debug', false);
    $volume = assetOnVolume('broken-source', ['handle' => 'broken', 'assetTransformer' => 'missing'])->getVolume();
    Log::shouldReceive('warning')
        ->once()
        ->withArgs(fn (string $message): bool => str_contains($message, '[missing]') && str_contains($message, '[broken] volume'));
    $transformers = app(AssetTransformers::class);

    expect($transformers->forVolume($volume))->toEqual(new AssetTransformer('craft'))
        ->and($transformers->forVolume($volume))->toEqual(new AssetTransformer('craft'));
});

it('reports a disk that still sets asset_transformer', function () {
    registerDriver('imgix');
    config()->set('filesystems.disks.legacy', ['driver' => 'local', 'root' => storage_path('framework/testing/legacy'), 'asset_transformer' => 'imgix']);
    config()->set('filesystems.disks.legacy-child', ['driver' => 'scoped', 'disk' => 'legacy', 'prefix' => 'child']);
    $volume = Volume::factory()->create(['fs' => 'legacy-child']);
    $asset = Asset::factory()->createElement(['volumeId' => $volume->id]);
    $transformers = app(AssetTransformers::class);

    config()->set('app.debug', true);
    expect(fn () => $transformers->forVolume($asset->getVolume()))
        ->toThrow(InvalidAssetTransformException::class, 'The [legacy] disk sets asset_transformer');

    config()->set('app.debug', false);
    Log::shouldReceive('error')->once()->withArgs(fn (string $message): bool => str_contains($message, '[legacy] disk'));
    expect($transformers->forVolume($asset->getVolume())->name)->toBe('craft')
        ->and($transformers->forVolume($asset->getVolume())->name)->toBe('craft');
});

it('keeps getUrl transform arguments with a deprecation warning', function () {
    $driver = registerDriver('remote');
    useAssetTransformer('remote');
    $asset = Asset::factory()->createElement();

    $url = $asset->getUrl(['width' => 320]);
    $deprecation = collect(Deprecator::getRequestLogs())->firstWhere('key', 'Asset::getUrl($transform)');

    expect($url)->toBe('/transforms/hero.webp')
        ->and($driver->request->transformer->name)->toBe('remote')
        ->and($driver->request->parameters)->toBe(['width' => 320])
        ->and($deprecation?->message)->toBe('Passing transform arguments to `Asset::getUrl()` is deprecated. Use `Asset::transform()->url` instead.');
});

it('returns the source values for assets the transformer can’t transform', function () {
    Exceptions::fake();
    config()->set('filesystems.disks.uploads', [
        'driver' => 'local',
        'root' => storage_path('framework/testing/asset-transformers'),
        'url' => 'https://example.test/uploads',
    ]);
    $volume = Volume::factory()->create(['fs' => 'uploads', 'hasUrls' => true]);
    $asset = Asset::factory()->createElement([
        'volumeId' => $volume->id,
        'folderId' => VolumeFolder::factory()->create(['volumeId' => $volume->id])->id,
        'filename' => 'clip.mp4',
        'kind' => 'video',
        'width' => 1920,
        'height' => 1080,
    ]);
    $transform = ['width' => 320, 'format' => 'webp'];

    expect($asset->getUrl($transform))->toBe('https://example.test/uploads/clip.mp4')
        ->and($asset->getWidth($transform))->toBe(1920)
        ->and($asset->getHeight($transform))->toBe(1080)
        ->and($asset->getFormat($transform))->toBe('mp4')
        ->and($asset->getMimeType($transform))->toBe($asset->getMimeType())
        ->and(fn () => $asset->transform($transform))->toThrow(NotSupportedException::class);

    Exceptions::assertNothingReported();
});

it('lets the Craft driver control immediate generation', function () {
    $immediateValues = [];
    $imageTransformer = Mockery::mock(ImageTransformer::class);
    $imageTransformer->shouldReceive('getTransformUrl')
        ->times(5)
        ->andReturnUsing(function (mixed $asset, mixed $transform, bool $immediately) use (&$immediateValues): string {
            $immediateValues[] = $immediately;

            return '/transforms/hero.webp';
        });
    $asset = Asset::factory()->createElement([
        'filename' => 'source.jpg',
        'width' => 800,
        'height' => 400,
    ]);
    $deferred = new CraftAssetTransformDriver($imageTransformer);
    $immediate = new CraftAssetTransformDriver($imageTransformer, generateBeforePageLoad: true);
    $request = fn (?bool $immediately = null) => new AssetTransformRequest(
        $asset,
        new AssetTransformer('craft'),
        ['width' => 320],
        immediately: $immediately,
    );

    $deferred->transform($request());
    $immediate->transform($request());
    $immediate->transform($request(false));
    $deferred->transform($request(true));
    $deferred->transform($request());

    expect($immediateValues)->toBe([false, true, false, true, false]);
});

it('passes immediate generation requests to drivers', function () {
    $driver = registerDriver('remote');
    useAssetTransformer('remote');
    $asset = Asset::factory()->createElement();
    $immediately = function (callable $transform) use ($driver): ?bool {
        $transform();

        return $driver->request->immediately;
    };

    expect($immediately(fn () => $asset->transform(['width' => 320])))->toBeNull()
        ->and($immediately(fn () => app(AssetTransformers::class)->withImmediateTransforms(
            fn () => $asset->transform(['width' => 320]),
        )))->toBeTrue()
        ->and($immediately(fn () => Context::scope(
            fn () => $asset->transform(['width' => 320]),
            hidden: [AssetTransformers::IMMEDIATE_TRANSFORMS_CONTEXT => false],
        )))->toBeFalse();
});

it('uses driver-specific named parameters', function () {
    $first = registerDriver('first', parameterRules: ['blur' => ['integer']]);
    $second = registerDriver('second', parameterRules: ['sharpen' => ['integer']]);
    $transform = new ImageTransform([
        'name' => 'Hero',
        'handle' => 'hero',
        'width' => 1200,
        'parameters' => [
            'first' => ['blur' => 8],
            'second' => ['sharpen' => 4],
        ],
    ]);
    app(ImageTransforms::class)->saveTransform($transform);
    $firstAsset = assetOnVolume('first-source', ['assetTransformer' => 'first']);
    $secondAsset = assetOnVolume('second-source', ['assetTransformer' => 'second']);

    app(AssetTransformers::class)->transform($firstAsset, ['transform' => 'hero']);
    app(AssetTransformers::class)->transform($secondAsset, ['transform' => 'hero']);

    expect($first->request->parameters)->toMatchArray(['width' => 1200, 'blur' => 8])
        ->and($first->request->parameters)->not->toHaveKey('sharpen')
        ->and($second->request->parameters)->toMatchArray(['width' => 1200, 'sharpen' => 4])
        ->and($second->request->parameters)->not->toHaveKey('blur');
});

it('passes undeclared parameters without validating them', function () {
    $driver = registerDriver('remote', parameterRules: ['blur' => ['integer']]);
    useAssetTransformer('remote');

    app(AssetTransformers::class)->transform(Asset::factory()->createElement(), [
        'unknown' => 'passed-through',
        'blur' => 5,
    ]);

    expect($driver->request->parameters)->toBe([
        'blur' => 5,
        'unknown' => 'passed-through',
    ]);
});

it('uses driver rules in place of conflicting core rules', function () {
    $driver = registerDriver('remote', parameterRules: [
        'quality' => [Rule::in([...range(1, 100), 'high', 'medium-high', 'medium-low', 'low'])],
    ]);
    useAssetTransformer('remote');
    $asset = Asset::factory()->createElement();

    app(AssetTransformers::class)->transform($asset, ['quality' => 'high']);

    expect($driver->request->parameters['quality'])->toBe('high')
        ->and(fn () => app(AssetTransformers::class)->transform(
            $asset,
            ['quality' => 'maximum'],
        ))->toThrow(InvalidAssetTransformException::class);
});

it('passes complex custom parameters through without serializing or reusing them', function () {
    $driver = registerDriver('remote');
    useAssetTransformer('remote');
    $asset = Asset::factory()->createElement();
    $transformers = app(AssetTransformers::class);
    $first = fn () => 'first';
    $second = fn () => 'second';

    $transformers->transform($asset, ['callback' => $first]);
    expect($driver->request->parameters['callback'])->toBe($first);

    $transformers->transform($asset, ['callback' => $second]);
    expect($driver->request->parameters['callback'])->toBe($second);
});

it('reuses validated Craft transform definitions across assets', function () {
    $transformers = app(AssetTransformers::class);
    $assets = [Asset::factory()->createElement(), Asset::factory()->createElement()];
    $driver = Mockery::mock(CraftAssetTransformDriver::class);
    $driver->shouldReceive('definition')->once()->andReturn(new AssetTransformDriverDefinition);
    $driver->shouldReceive('transform')->twice()->andReturn(new AssetTransformResult('/thumbnail.jpg', 'image/jpeg'));
    $transformers->extend('mock-craft', fn () => $driver);
    useAssetTransformer('mock-craft');

    $transformers->transform($assets[0], ['width' => 24, 'height' => 24, 'mode' => 'crop']);
    $transformers->transform($assets[1], ['mode' => 'crop', 'height' => 24, 'width' => 24]);
});

it('validates each normalized definition once across preloading and rendering', function () {
    $driver = new TestPreloadingAssetTransformDriver;
    registerDriver('remote', driver: $driver);
    useAssetTransformer('remote');
    $assets = [Asset::factory()->createElement(), Asset::factory()->createElement()];
    $transformers = app(AssetTransformers::class);
    $validator = Validator::getFacadeRoot();
    Validator::shouldReceive('make')->twice()->andReturnUsing($validator->make(...));

    $transformers->preload($assets, [['width' => 100], '2x']);

    foreach ($assets as $asset) {
        $transformers->transform($asset, ['width' => 100]);
        $transformers->transform($asset, ['width' => 200]);
    }

    expect($driver->requests)->toHaveCount(4)
        ->and($driver->request->parameters)->toBe(['width' => 200]);
});

it('revalidates definitions after replacing a driver', function () {
    registerDriver('remote');
    useAssetTransformer('remote');
    $asset = Asset::factory()->createElement();
    $transformers = app(AssetTransformers::class);
    $validator = Validator::getFacadeRoot();
    Validator::shouldReceive('make')->times(2)->andReturnUsing($validator->make(...));

    $transformers->transform($asset, ['width' => 100]);
    $transformers->transform($asset, ['width' => 100]);

    $transformers->forgetTransformers()->extend('remote', fn () => new TestAssetTransformDriver(
        new AssetTransformDriverDefinition(parameterRules: ['width' => ['integer', 'max:50']]),
    ));

    expect(fn () => $transformers->transform($asset, ['width' => 100]))
        ->toThrow(InvalidAssetTransformException::class);
});

it('preserves parameter rules declared by a custom Craft driver definition', function () {
    $driver = new class(new ImageTransformer) extends CraftAssetTransformDriver
    {
        public function definition(): AssetTransformDriverDefinition
        {
            return new AssetTransformDriverDefinition(parameterRules: [
                'width' => [Rule::in(['auto'])],
            ]);
        }
    };
    $transformers = app(AssetTransformers::class);
    $transformers->extend('custom-craft', fn () => $driver);
    defineAssetTransformer('custom-craft');

    expect($transformers->validateParameters('custom-craft', ['width' => 'auto']))
        ->toBe(['width' => 'auto'])
        ->and(fn () => $transformers->validateParameters('custom-craft', ['width' => 24]))
        ->toThrow(InvalidAssetTransformException::class);
});

it('rejects invalid parameters', function () {
    registerDriver('remote');
    useAssetTransformer('remote');

    expect(fn () => app(AssetTransformers::class)->transform(
        Asset::factory()->createElement(),
        ['width' => 0],
    ))->toThrow(InvalidAssetTransformException::class);
});

it('preloads requests grouped by driver', function (bool $perAsset) {
    $firstDriver = new TestPreloadingAssetTransformDriver;
    $secondDriver = new TestPreloadingAssetTransformDriver;
    registerDriver('first', driver: $firstDriver);
    registerDriver('second', driver: $secondDriver);
    $assets = collect(['first', 'second'])->map(fn (string $driver) => assetOnVolume("{$driver}-transformer-source", [
        'assetTransformer' => $driver,
    ], [
        'filename' => 'source.jpg',
        'kind' => 'image',
        'width' => $driver === 'first' ? 320 : 640,
        'height' => 200,
    ]))->all();

    app(AssetTransformers::class)->preload($assets, $perAsset
        ? fn (AssetElement $asset): array => [['width' => $asset->getWidth()]]
        : [['width' => 320]]);

    expect($firstDriver->requests)->toHaveCount(1)
        ->and($firstDriver->requests[0]->asset)->toBe($assets[0])
        ->and($firstDriver->requests[0]->transformer->name)->toBe('first')
        ->and($firstDriver->requests[0]->parameters)->toBe(['width' => 320])
        ->and($secondDriver->requests)->toHaveCount(1)
        ->and($secondDriver->requests[0]->asset)->toBe($assets[1])
        ->and($secondDriver->requests[0]->transformer->name)->toBe('second')
        ->and($secondDriver->requests[0]->parameters)->toBe(['width' => $perAsset ? 640 : 320]);
})->with(['shared definitions' => false, 'asset-specific definitions' => true]);

it('preloads withTransforms criteria for eager-loaded assets', function () {
    $driver = new TestPreloadingAssetTransformDriver;
    registerDriver('remote', driver: $driver);
    useAssetTransformer('remote');
    $photos = collect(range(1, 2))->map(fn () => Asset::factory()->createElement([
        'kind' => 'image',
        'width' => 320,
        'height' => 200,
    ]));
    $userIds = $photos->map(fn (AssetElement $photo) => User::factory()->create(['photoId' => $photo->id])->id);

    UserElement::find()
        ->id($userIds->all())
        ->with([['photo', ['withTransforms' => [['width' => 100]]]]])
        ->all();

    expect(collect($driver->requests)->map(fn (AssetTransformRequest $request) => $request->asset->id)->all())
        ->toEqualCanonicalizing($photos->pluck('id')->all())
        ->and($driver->requests[0]->parameters)->toBe(['width' => 100]);
});

/**
 * @param  array<string, mixed>  $volumeAttributes
 * @param  array<string, mixed>  $assetAttributes
 */
function assetOnVolume(string $disk, array $volumeAttributes = [], array $assetAttributes = []): AssetElement
{
    config()->set("filesystems.disks.{$disk}", [
        'driver' => 'local',
        'root' => storage_path("framework/testing/{$disk}"),
    ]);
    $volume = Volume::factory()->create(['fs' => $disk, ...$volumeAttributes]);
    app(Volumes::class)->reset();

    return Asset::factory()->createElement(['volumeId' => $volume->id, ...$assetAttributes]);
}

/** @param array<string, non-empty-list<string|Stringable>> $parameterRules */
function registerDriver(
    string $name,
    array $parameterRules = [],
    ?TestAssetTransformDriver $driver = null,
): TestAssetTransformDriver {
    $driver ??= new TestAssetTransformDriver(new AssetTransformDriverDefinition($parameterRules));
    app(AssetTransformers::class)->extend($name, fn () => $driver);
    defineAssetTransformer($name);

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
        return $this->driverDefinition ?? new AssetTransformDriverDefinition;
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
