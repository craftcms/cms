<?php

declare(strict_types=1);

use CraftCms\Cms\Asset\AssetTransforms;
use CraftCms\Cms\Asset\Contracts\AssetTransformDriver;
use CraftCms\Cms\Asset\Data\AssetTransformDriverDefinition;
use CraftCms\Cms\Asset\Data\AssetTransformRequest;
use CraftCms\Cms\Asset\Data\AssetTransformResult;
use CraftCms\Cms\Asset\Exceptions\AssetTransformDriverNotFoundException;
use CraftCms\Cms\Asset\Exceptions\AssetTransformFailedException;
use CraftCms\Cms\Asset\Exceptions\InvalidAssetTransformException;
use CraftCms\Cms\Asset\Models\Asset;
use CraftCms\Cms\Cms;

it('executes a registered driver with normalized operations', function () {
    $driver = new TestAssetTransformDriver;
    $manager = assetTransformsWith($driver);
    $asset = Asset::factory()->createElement();

    $result = $manager->transform($asset, [
        'driver' => 'test',
        'format' => 'webp',
        'width' => '1200',
    ]);

    expect($result->url)->toBe('/renditions/hero.webp')
        ->and($driver->request->asset)->toBe($asset)
        ->and($driver->request->driver)->toBe('test')
        ->and($driver->request->operations)->toBe([
            'format' => 'webp',
            'width' => 1200,
        ]);
});

it('transforms non-image Assets', function () {
    $driver = new TestAssetTransformDriver;
    $manager = assetTransformsWith($driver);
    $asset = Asset::factory()->createElement([
        'filename' => 'source.pdf',
        'kind' => 'pdf',
    ]);

    $manager->transform($asset, ['driver' => 'test']);

    expect($driver->request->asset)->toBe($asset);
});

it('delegates from the Asset', function () {
    assetTransformsWith(new TestAssetTransformDriver);
    $asset = Asset::factory()->createElement();

    $result = $asset->transform(['driver' => 'test', 'width' => 1200]);

    expect($result->url)->toBe('/renditions/hero.webp');
});

it('uses the configured default driver', function () {
    $driver = new TestAssetTransformDriver;
    $manager = assetTransformsWith($driver);
    Cms::config()->defaultAssetTransformDriver('test');

    $manager->transform(Asset::factory()->createElement(), ['width' => 1200]);

    expect($driver->request->driver)->toBe('test');
});

it('normalizes nullable scalar operations contributed by a driver', function () {
    $definition = new AssetTransformDriverDefinition('Test', [
        'blur' => ['integer', 'min:1'],
        'enabled' => ['boolean'],
        'ratio' => ['numeric'],
        'watermark' => ['string'],
        'optional' => ['string'],
    ]);
    $driver = new TestAssetTransformDriver($definition);
    $manager = assetTransformsWith($driver);

    $manager->transform(Asset::factory()->createElement(), [
        'driver' => 'test',
        'blur' => '12',
        'enabled' => 'yes',
        'optional' => null,
        'ratio' => '1.5',
        'watermark' => 42,
    ]);

    expect($driver->request->operations)->toBe([
        'blur' => 12,
        'enabled' => true,
        'optional' => null,
        'ratio' => 1.5,
        'watermark' => '42',
    ]);
});

it('prevents drivers from redeclaring Craft operations', function () {
    $definition = new AssetTransformDriverDefinition('Test', [
        'width' => ['string'],
    ]);
    $driver = new TestAssetTransformDriver($definition);
    $manager = assetTransformsWith($driver);

    expect(fn () => $manager->transform(Asset::factory()->createElement(), [
        'driver' => 'test',
        'width' => 1200,
    ]))->toThrow(InvalidAssetTransformException::class);
});

it('keeps custom operation declarations local to each driver', function () {
    $integerDriver = new TestAssetTransformDriver(
        new AssetTransformDriverDefinition('Integer', [
            'blur' => ['integer'],
        ]),
    );
    $stringDriver = new TestAssetTransformDriver(
        new AssetTransformDriverDefinition('String', [
            'blur' => ['string'],
        ]),
    );
    $manager = app(AssetTransforms::class)
        ->extend('integer', fn () => $integerDriver)
        ->extend('string', fn () => $stringDriver);
    $asset = Asset::factory()->createElement();

    $manager->transform($asset, ['driver' => 'integer', 'blur' => 12]);

    $manager->transform($asset, ['driver' => 'string', 'blur' => 12]);

    expect($integerDriver->request->operations['blur'])->toBe(12)
        ->and($stringDriver->request->operations['blur'])->toBe('12');
});

it('rejects invalid operation values', function (array $operations) {
    $manager = assetTransformsWith(new TestAssetTransformDriver);

    expect(fn () => $manager->transform(Asset::factory()->createElement(), ['driver' => 'test', ...$operations]))
        ->toThrow(InvalidAssetTransformException::class);
})->with([
    'minimum' => [['width' => 0]],
    'allowed values' => [['format' => 'invalid']],
]);

it('does not fall through from an invalid explicit selection', function (mixed $selection) {
    $manager = assetTransformsWith(new TestAssetTransformDriver);
    Cms::config()->defaultAssetTransformDriver('test');

    expect(fn () => $manager->transform(Asset::factory()->createElement(), [
        'driver' => $selection,
    ]))->toThrow(AssetTransformDriverNotFoundException::class);
})->with([
    'null' => null,
    'unregistered' => 'missing',
]);

it('propagates driver failures unchanged', function () {
    $failure = new AssetTransformFailedException('failed');
    $manager = assetTransformsWith(new FailingAssetTransformDriver($failure));

    expect(fn () => $manager->transform(Asset::factory()->createElement(), ['driver' => 'test']))
        ->toThrow($failure);
});

it('redacts filesystem settings from debug output', function () {
    $request = new AssetTransformRequest(
        Asset::factory()->createElement(),
        'test',
        [],
        ['token' => 'secret-value'],
    );
    ob_start();
    var_dump($request);
    $output = ob_get_clean();

    expect($output)->toContain('[redacted]')
        ->not->toContain('secret-value');
});

function assetTransformsWith(AssetTransformDriver $driver): AssetTransforms
{
    return app(AssetTransforms::class)->extend('test', fn () => $driver);
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

        return new AssetTransformResult(
            url: '/renditions/hero.webp',
            mimeType: 'image/webp',
        );
    }
}

class FailingAssetTransformDriver extends TestAssetTransformDriver
{
    public function __construct(
        private readonly Throwable $failure,
    ) {
        parent::__construct();
    }

    #[Override]
    public function transform(AssetTransformRequest $request): AssetTransformResult
    {
        throw $this->failure;
    }
}
