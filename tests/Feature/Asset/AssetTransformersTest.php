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
use CraftCms\Cms\Image\CraftAssetTransformDriver;
use CraftCms\Cms\Image\Data\ImageTransform;
use CraftCms\Cms\Image\ImageTransformer;
use CraftCms\Cms\Image\ImageTransforms;
use CraftCms\Cms\Support\Facades\Deprecator;
use CraftCms\Cms\User\Elements\User as UserElement;
use CraftCms\Cms\User\Models\User;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

it('uses the craft driver with its defaults for a disk without an asset_transformer key', function () {
    $transformer = app(AssetTransformers::class)->forDisk('local');

    expect($transformer->driver)->toBe('craft')
        ->and($transformer->settings)->toBe([])
        ->and(app(AssetTransformers::class)->forDisk(null)->driver)->toBe('craft')
        ->and(app(AssetTransformers::class)->forDisk('not-a-disk')->driver)->toBe('craft');
});

it('reads a driver name or an array with inline settings from the disk', function () {
    registerDriver('imgix');
    config()->set('filesystems.disks.named', ['driver' => 'local', 'asset_transformer' => 'imgix']);
    config()->set('filesystems.disks.inline', ['driver' => 'local', 'asset_transformer' => [
        'driver' => 'craft',
        'output_disk' => 'transforms',
        'subpath' => '_transforms',
    ]]);
    config()->set('filesystems.disks', [
        ...config('filesystems.disks'),
        'dotted.disk' => ['driver' => 'local', 'asset_transformer' => 'imgix'],
    ]);
    $transformers = app(AssetTransformers::class);

    expect($transformers->forDisk('named'))->toEqual(new AssetTransformer('imgix'))
        ->and($transformers->forDisk('inline'))->toEqual(new AssetTransformer('craft', [
            'output_disk' => 'transforms',
            'subpath' => '_transforms',
        ]))
        ->and($transformers->forDisk('dotted.disk')->driver)->toBe('imgix');
});

it('lets a scoped disk without a key follow the disk it wraps', function () {
    registerDriver('imgix');
    registerDriver('other');
    config()->set('filesystems.disks.parent', ['driver' => 'local', 'asset_transformer' => ['driver' => 'imgix', 'zone' => 'a']]);
    config()->set('filesystems.disks.child', ['driver' => 'scoped', 'disk' => 'parent', 'prefix' => 'blog']);
    config()->set('filesystems.disks.grandchild', ['driver' => 'scoped', 'disk' => 'child', 'prefix' => 'posts']);
    config()->set('filesystems.disks.inline-parent', ['driver' => 'scoped', 'prefix' => 'x', 'disk' => [
        'driver' => 'local',
        'asset_transformer' => 'other',
    ]]);
    config()->set('filesystems.disks.own-key', [
        'driver' => 'scoped',
        'disk' => 'parent',
        'prefix' => 'own',
        'asset_transformer' => 'craft',
    ]);
    config()->set('filesystems.disks.loop-a', ['driver' => 'scoped', 'disk' => 'loop-b', 'prefix' => 'a']);
    config()->set('filesystems.disks.loop-b', ['driver' => 'scoped', 'disk' => 'loop-a', 'prefix' => 'b']);
    $transformers = app(AssetTransformers::class);

    expect($transformers->forDisk('child'))->toEqual(new AssetTransformer('imgix', ['zone' => 'a']))
        ->and($transformers->forDisk('grandchild')->driver)->toBe('imgix')
        ->and($transformers->forDisk('inline-parent')->driver)->toBe('other')
        ->and($transformers->forDisk('own-key'))->toEqual(new AssetTransformer('craft'))
        ->and($transformers->forDisk('loop-a')->driver)->toBe('craft');
});

it('picks the transformer argument, then the asset’s disk, then craft', function () {
    $diskDriver = registerDriver('disk-driver');
    $explicit = registerDriver('explicit');
    $asset = assetOnDisk('transformer-source', ['asset_transformer' => ['driver' => 'disk-driver', 'zone' => 'disk']]);

    app(AssetTransformers::class)->transform($asset, ['width' => 100]);
    app(AssetTransformers::class)->transform($asset, ['width' => 200], transformer: 'explicit');

    expect($diskDriver->request->parameters['width'])->toBe(100)
        ->and($diskDriver->request->asset)->toBe($asset)
        ->and($diskDriver->request->transformer->settings)->toBe(['zone' => 'disk'])
        ->and($explicit->request->parameters['width'])->toBe(200);
});

it('keeps the disk’s settings only when the argument names the disk’s own driver', function () {
    $remote = registerDriver('remote');
    $otherDriver = registerDriver('other');
    $asset = assetOnDisk('transformer-source', ['asset_transformer' => ['driver' => 'remote', 'zone' => 'disk']]);
    $transformers = app(AssetTransformers::class);

    $transformers->transform($asset, [], transformer: 'remote');
    $sameDriver = $remote->request->transformer;
    $transformers->transform($asset, [], transformer: 'other');

    expect($sameDriver->settings)->toBe(['zone' => 'disk'])
        ->and($otherDriver->request->transformer)->toEqual(new AssetTransformer('other'));
});

it('throws for invalid transformer config with debug mode on', function (mixed $value, string $exception) {
    config()->set('app.debug', true);
    config()->set('filesystems.disks.broken', ['driver' => 'local', 'asset_transformer' => $value]);

    expect(fn () => app(AssetTransformers::class)->forDisk('broken'))
        ->toThrow($exception, '[broken] disk');
})->with([
    'unregistered driver' => ['missing', AssetTransformDriverNotFoundException::class],
    'a case variant of craft' => ['CRAFT', AssetTransformDriverNotFoundException::class],
    'a name with no letters' => ['-', AssetTransformDriverNotFoundException::class],
    'array without a driver' => [['subpath' => 'x'], InvalidAssetTransformException::class],
    'empty string' => ['', InvalidAssetTransformException::class],
]);

it('logs invalid transformer config once and falls back to craft with debug mode off', function () {
    config()->set('app.debug', false);
    config()->set('filesystems.disks.broken', ['driver' => 'local', 'asset_transformer' => 'missing']);
    Log::shouldReceive('warning')
        ->once()
        ->withArgs(fn (string $message): bool => str_contains($message, '[missing]') && str_contains($message, '[broken] disk'));
    $transformers = app(AssetTransformers::class);

    $first = $transformers->forDisk('broken');
    $second = $transformers->forDisk('broken');

    expect($first)->toEqual(new AssetTransformer('craft'))
        ->and($second)->toEqual(new AssetTransformer('craft'));
});

it('applies the same rule to an unregistered transformer argument', function () {
    $asset = Asset::factory()->createElement();

    expect(fn () => app(AssetTransformers::class)->transform($asset, [], transformer: 'missing'))
        ->toThrow(AssetTransformDriverNotFoundException::class, 'transformer argument');
});

it('keeps getUrl transform arguments with a deprecation warning', function () {
    $driver = registerDriver('remote');
    config()->set('filesystems.disks.local.asset_transformer', 'remote');
    $asset = Asset::factory()->createElement();

    $url = $asset->getUrl(['width' => 320]);
    $deprecation = collect(Deprecator::getRequestLogs())->firstWhere('key', 'Asset::getUrl($transform)');

    expect($url)->toBe('/transforms/hero.webp')
        ->and($driver->request->transformer->driver)->toBe('remote')
        ->and($driver->request->parameters)->toBe(['width' => 320])
        ->and($deprecation?->message)->toBe('Passing transform arguments to `Asset::getUrl()` is deprecated. Use `Asset::transform()->url` instead.');
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
    $driver = new CraftAssetTransformDriver($imageTransformer);
    $asset = Asset::factory()->createElement([
        'filename' => 'source.jpg',
        'width' => 800,
        'height' => 400,
    ]);
    $deferred = new AssetTransformer('craft', ['generate_transforms_before_page_load' => false]);
    $immediate = new AssetTransformer('craft', ['generate_transforms_before_page_load' => true]);
    $request = fn (AssetTransformer $transformer) => new AssetTransformRequest(
        $asset,
        $transformer,
        ['width' => 320],
    );

    $driver->transform($request($deferred));
    $driver->transform($request($immediate));
    Context::scope(
        fn () => $driver->transform($request($immediate)),
        hidden: [CraftAssetTransformDriver::IMMEDIATE_TRANSFORMS_CONTEXT => false],
    );
    $driver->withImmediateTransforms(fn () => $driver->transform($request($deferred)));
    $driver->transform($request($deferred));

    expect($immediateValues)->toBe([false, true, false, true, false]);
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
    $asset = Asset::factory()->createElement();

    app(AssetTransformers::class)->transform($asset, ['transform' => 'hero'], transformer: 'first');
    app(AssetTransformers::class)->transform($asset, ['transform' => 'hero'], transformer: 'second');

    expect($first->request->parameters)->toMatchArray(['width' => 1200, 'blur' => 8])
        ->and($first->request->parameters)->not->toHaveKey('sharpen')
        ->and($second->request->parameters)->toMatchArray(['width' => 1200, 'sharpen' => 4])
        ->and($second->request->parameters)->not->toHaveKey('blur');
});

it('passes undeclared parameters without validating them', function () {
    $driver = registerDriver('remote', parameterRules: ['blur' => ['integer']]);

    app(AssetTransformers::class)->transform(Asset::factory()->createElement(), [
        'unknown' => 'passed-through',
        'blur' => 5,
    ], transformer: 'remote');

    expect($driver->request->parameters)->toBe([
        'blur' => 5,
        'unknown' => 'passed-through',
    ]);
});

it('uses driver rules in place of conflicting core rules', function () {
    $driver = registerDriver('remote', parameterRules: [
        'quality' => [Rule::in([...range(1, 100), 'high', 'medium-high', 'medium-low', 'low'])],
    ]);
    $asset = Asset::factory()->createElement();

    app(AssetTransformers::class)->transform($asset, ['quality' => 'high'], transformer: 'remote');

    expect($driver->request->parameters['quality'])->toBe('high')
        ->and(fn () => app(AssetTransformers::class)->transform(
            $asset,
            ['quality' => 'maximum'],
            transformer: 'remote',
        ))->toThrow(InvalidAssetTransformException::class);
});

it('passes complex custom parameters through without serializing or reusing them', function () {
    $driver = registerDriver('remote');
    $asset = Asset::factory()->createElement();
    $transformers = app(AssetTransformers::class);
    $first = fn () => 'first';
    $second = fn () => 'second';

    $transformers->transform($asset, ['callback' => $first], transformer: 'remote');
    expect($driver->request->parameters['callback'])->toBe($first);

    $transformers->transform($asset, ['callback' => $second], transformer: 'remote');
    expect($driver->request->parameters['callback'])->toBe($second);
});

it('reuses validated Craft transform definitions across assets', function () {
    $transformers = app(AssetTransformers::class);
    $assets = [Asset::factory()->createElement(), Asset::factory()->createElement()];
    $driver = Mockery::mock(CraftAssetTransformDriver::class);
    $driver->shouldReceive('definition')->once()->andReturn(new AssetTransformDriverDefinition);
    $driver->shouldReceive('transform')->twice()->andReturn(new AssetTransformResult('/thumbnail.jpg', 'image/jpeg'));
    $transformers->extend('craft', fn () => $driver);

    $transformers->transform($assets[0], ['width' => 24, 'height' => 24, 'mode' => 'crop']);
    $transformers->transform($assets[1], ['mode' => 'crop', 'height' => 24, 'width' => 24]);
});

it('validates each normalized definition once across preloading and rendering', function () {
    $driver = new TestPreloadingAssetTransformDriver;
    registerDriver('remote', driver: $driver);
    config()->set('filesystems.disks.local.asset_transformer', 'remote');
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

it('revalidates definitions after resetting or replacing a driver', function () {
    registerDriver('remote');
    $asset = Asset::factory()->createElement();
    $transformers = app(AssetTransformers::class);
    $validator = Validator::getFacadeRoot();
    Validator::shouldReceive('make')->times(3)->andReturnUsing($validator->make(...));

    $transformers->transform($asset, ['width' => 100], transformer: 'remote');
    $transformers->reset();
    $transformers->transform($asset, ['width' => 100], transformer: 'remote');

    $transformers->forgetDrivers()->extend('remote', fn () => new TestAssetTransformDriver(
        new AssetTransformDriverDefinition(parameterRules: ['width' => ['integer', 'max:50']]),
    ));

    expect(fn () => $transformers->transform($asset, ['width' => 100], transformer: 'remote'))
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

    expect($transformers->validateParameters('custom-craft', ['width' => 'auto']))
        ->toBe(['width' => 'auto'])
        ->and(fn () => $transformers->validateParameters('custom-craft', ['width' => 24]))
        ->toThrow(InvalidAssetTransformException::class);
});

it('rejects invalid parameters', function () {
    registerDriver('remote');

    expect(fn () => app(AssetTransformers::class)->transform(
        Asset::factory()->createElement(),
        ['width' => 0],
        transformer: 'remote',
    ))->toThrow(InvalidAssetTransformException::class);
});

it('preloads requests grouped by driver', function (bool $perAsset) {
    $firstDriver = new TestPreloadingAssetTransformDriver;
    $secondDriver = new TestPreloadingAssetTransformDriver;
    registerDriver('first', driver: $firstDriver);
    registerDriver('second', driver: $secondDriver);
    $assets = collect(['first', 'second'])->map(fn (string $driver) => assetOnDisk("{$driver}-transformer-source", [
        'asset_transformer' => $driver,
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
        ->and($firstDriver->requests[0]->transformer->driver)->toBe('first')
        ->and($firstDriver->requests[0]->parameters)->toBe(['width' => 320])
        ->and($secondDriver->requests)->toHaveCount(1)
        ->and($secondDriver->requests[0]->asset)->toBe($assets[1])
        ->and($secondDriver->requests[0]->transformer->driver)->toBe('second')
        ->and($secondDriver->requests[0]->parameters)->toBe(['width' => $perAsset ? 640 : 320]);
})->with(['shared definitions' => false, 'asset-specific definitions' => true]);

it('preloads withTransforms criteria for eager-loaded assets', function () {
    $driver = new TestPreloadingAssetTransformDriver;
    registerDriver('remote', driver: $driver);
    config()->set('filesystems.disks.local.asset_transformer', 'remote');
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

it('redacts transformer settings from debug output', function () {
    $request = new AssetTransformRequest(
        Asset::factory()->createElement(),
        new AssetTransformer('remote', ['token' => 'secret-value']),
        [],
    );
    ob_start();
    var_dump($request);
    $output = ob_get_clean();

    expect($output)->toContain('[redacted]')
        ->not->toContain('secret-value');
});

/**
 * @param  array<string, mixed>  $diskConfig
 * @param  array<string, mixed>  $assetAttributes
 */
function assetOnDisk(string $disk, array $diskConfig = [], array $assetAttributes = []): AssetElement
{
    config()->set("filesystems.disks.{$disk}", [
        'driver' => 'local',
        'root' => storage_path("framework/testing/{$disk}"),
        ...$diskConfig,
    ]);
    $volume = Volume::factory()->create(['fs' => $disk]);

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
