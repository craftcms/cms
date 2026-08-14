<?php

declare(strict_types=1);

use CraftCms\Cms\Asset\AssetTransforms;
use CraftCms\Cms\Asset\Contracts\AssetTransformDriver;
use CraftCms\Cms\Asset\Contracts\PreloadsAssetTransforms;
use CraftCms\Cms\Asset\Data\AssetTransformDriverDefinition;
use CraftCms\Cms\Asset\Data\AssetTransformRequest;
use CraftCms\Cms\Asset\Data\AssetTransformResult;
use CraftCms\Cms\Asset\Elements\Asset as AssetElement;
use CraftCms\Cms\Asset\Events\AssetUrlResolving;
use CraftCms\Cms\Asset\Exceptions\AssetTransformDriverNotFoundException;
use CraftCms\Cms\Asset\Exceptions\AssetTransformFailedException;
use CraftCms\Cms\Asset\Exceptions\InvalidAssetTransformException;
use CraftCms\Cms\Asset\Models\Asset;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Image\Data\ImageTransform;
use CraftCms\Cms\Image\ImageTransformer;
use CraftCms\Cms\Image\ImageTransforms;
use CraftCms\Cms\View\TemplateManager;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Exceptions;

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

it('routes explicit rendering and metadata through Image Renditions without mutating the Asset', function () {
    assetTransformsWith(new RenderingAssetTransformDriver);
    Cms::config()->defaultAssetTransformDriver('test');
    $asset = Asset::factory()->createElement([
        'filename' => 'source.pdf',
        'kind' => 'pdf',
        'width' => 1000,
        'height' => 500,
    ]);
    Event::listen(AssetUrlResolving::class, function (AssetUrlResolving $event) {
        if ($event->transform === null) {
            $event->url = '/source/source.pdf';
        }
    });

    $img = $asset->getImg(['width' => 640, 'height' => 360]);
    $document = new DOMDocument;
    $document->loadHTML((string) $img);
    $element = $document->getElementsByTagName('img')->item(0);

    expect($element->getAttribute('src'))->toBe('/renditions/640x360.webp')
        ->and($element->getAttribute('width'))->toBe('640')
        ->and($element->getAttribute('height'))->toBe('360')
        ->and($asset->getMimeType(['width' => 640]))->toBe('image/webp')
        ->and($asset->getWidth(['width' => 640]))->toBe(640)
        ->and($asset->getHeight(['height' => 360]))->toBe(360)
        ->and($asset->getUrl())->toBe('/source/source.pdf')
        ->and(method_exists($asset, 'setTransform'))->toBeFalse()
        ->and(method_exists($asset, 'copyWithTransform'))->toBeFalse();
});

it('routes srcset sizes through Image Renditions', function () {
    assetTransformsWith(new RenderingAssetTransformDriver);
    Cms::config()->defaultAssetTransformDriver('test');
    $asset = Asset::factory()->createElement([
        'width' => 800,
        'height' => 400,
    ]);

    expect($asset->getUrlsBySize(['320w', '2x'], ['width' => 400, 'height' => 200]))->toBe([
        '320w' => '/renditions/320x160.webp',
        '2x' => '/renditions/800x400.webp',
    ])->and($asset->getSrcset(['320w', '2x'], ['width' => 400, 'height' => 200]))
        ->toBe('/renditions/320x160.webp 320w, /renditions/800x400.webp 2x');
});

it('keeps source URL and metadata behavior without a transform', function () {
    $driver = new RenderingAssetTransformDriver;
    assetTransformsWith($driver);
    Cms::config()->defaultAssetTransformDriver('test');
    $asset = Asset::factory()->createElement([
        'filename' => 'source.jpg',
        'kind' => 'image',
        'width' => 800,
        'height' => 400,
    ]);
    Event::listen(AssetUrlResolving::class, function (AssetUrlResolving $event) {
        if ($event->transform === null) {
            $event->url = '/source/source.jpg';
        }
    });

    expect($asset->getUrl())->toBe('/source/source.jpg')
        ->and($asset->getMimeType())->toBe('image/jpeg')
        ->and($asset->getWidth())->toBe(800)
        ->and($asset->getHeight())->toBe(400)
        ->and($driver->request)->toBeNull();
});

it('accepts named Image transforms through the public interfaces', function () {
    assetTransformsWith(new RenderingAssetTransformDriver);
    Cms::config()->defaultAssetTransformDriver('test');
    app(ImageTransforms::class)->saveTransform(new ImageTransform([
        'name' => 'Hero',
        'handle' => 'hero',
        'width' => 1200,
        'height' => 600,
    ]));
    $asset = Asset::factory()->createElement();

    expect($asset->transform('hero')->url)->toBe('/renditions/1200x600.webp')
        ->and($asset->getUrl('hero'))->toBe('/renditions/1200x600.webp');
});

it('exposes typed failures for invalid named Image transforms', function () {
    $asset = Asset::factory()->createElement();

    expect(fn () => $asset->transform('missing'))
        ->toThrow(InvalidAssetTransformException::class);
});

it('keeps unavailable Image Rendition metadata nullable', function () {
    assetTransformsWith(new TestAssetTransformDriver);
    Cms::config()->defaultAssetTransformDriver('test');
    $asset = Asset::factory()->createElement();

    expect($asset->getMimeType(['width' => 320]))->toBe('image/webp')
        ->and($asset->getWidth(['width' => 320]))->toBeNull()
        ->and($asset->getHeight(['width' => 320]))->toBeNull()
        ->and((string) $asset->getImg(['width' => 320]))
        ->toBe('<img src="/renditions/hero.webp">');
});

it('exposes Image Renditions to Twig', function () {
    assetTransformsWith(new RenderingAssetTransformDriver);
    Cms::config()->defaultAssetTransformDriver('test');
    $asset = Asset::factory()->createElement();

    $output = app(TemplateManager::class)->renderString(
        '{% set rendition = asset.transform({width: 320, height: 180}) %}{{ rendition.url }}|{{ rendition.mimeType }}|{{ rendition.width }}x{{ rendition.height }}',
        ['asset' => $asset],
    );

    expect($output)->toBe('/renditions/320x180.webp|image/webp|320x180');
});

it('renders transformed conveniences and source behavior in Twig', function () {
    assetTransformsWith(new RenderingAssetTransformDriver);
    Cms::config()->defaultAssetTransformDriver('test');
    $asset = Asset::factory()->createElement([
        'filename' => 'source.jpg',
        'width' => 800,
        'height' => 400,
    ]);
    Event::listen(AssetUrlResolving::class, function (AssetUrlResolving $event) {
        if ($event->transform === null) {
            $event->url = '/source/source.jpg';
        }
    });

    $output = app(TemplateManager::class)->renderString(
        "{{ asset.getUrl({width: 320, height: 180}) }}|{{ asset.getImg({width: 320, height: 180}) }}|{{ asset.getSrcset(['160w'], {width: 320, height: 180}) }}|{{ asset.getUrlsBySize(['160w'], {width: 320, height: 180})['160w'] }}|{{ asset.getMimeType({width: 320}) }}|{{ asset.getWidth({width: 320}) }}x{{ asset.getHeight({height: 180}) }}|{{ asset.getUrl() }}|{{ asset.getMimeType() }}|{{ asset.getWidth() }}x{{ asset.getHeight() }}",
        ['asset' => $asset],
    );

    expect($output)->toBe('/renditions/320x180.webp|<img src="/renditions/320x180.webp" width="320" height="180">|/renditions/160x90.webp 160w|/renditions/160x90.webp|image/webp|320x180|/source/source.jpg|image/jpeg|800x400');
});

it('reports typed failures and returns null from URL conveniences', function () {
    Exceptions::fake();
    assetTransformsWith(new FailingAssetTransformDriver(new AssetTransformFailedException('failed')));
    Cms::config()->defaultAssetTransformDriver('test');
    $asset = Asset::factory()->createElement();

    expect($asset->getUrl(['width' => 320]))->toBeNull();
    Exceptions::assertReported(AssetTransformFailedException::class);
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

it('rejects incompatible shared operation declarations', function () {
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

    expect(fn () => $manager->getOperationRules())
        ->toThrow(InvalidAssetTransformException::class);
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

it('preloads immutable requests grouped by capable driver', function () {
    $first = new PreloadingAssetTransformDriver;
    $second = new PreloadingAssetTransformDriver;
    $incapable = new TestAssetTransformDriver;
    $manager = app(AssetTransforms::class)
        ->extend('first', fn () => $first)
        ->extend('second', fn () => $second)
        ->extend('incapable', fn () => $incapable);
    $assets = [
        Asset::factory()->createElement(),
        Asset::factory()->createElement(),
    ];

    $manager->preload($assets, [
        ['driver' => 'first', 'width' => '320'],
        ['driver' => 'second', 'width' => 480],
        ['driver' => 'incapable', 'width' => 640],
    ]);

    expect($first->requests)->toHaveCount(2)
        ->each->toBeInstanceOf(AssetTransformRequest::class)
        ->and(array_column($first->requests, 'asset'))->toBe($assets)
        ->and(array_column($first->requests, 'driver'))->toBe(['first', 'first'])
        ->and(array_column($first->requests, 'operations'))->toBe([
            ['width' => 320],
            ['width' => 320],
        ])
        ->and(array_column($second->requests, 'asset'))->toBe($assets)
        ->and(array_column($second->requests, 'driver'))->toBe(['second', 'second'])
        ->and(array_column($second->requests, 'operations'))->toBe([
            ['width' => 480],
            ['width' => 480],
        ])
        ->and($incapable->request)->toBeNull();
});

it('groups built-in preload requests by operations and deduplicates Assets', function () {
    $driver = new TestCraftPreloadingDriver;
    $assets = [
        Asset::factory()->createElement(),
        Asset::factory()->createElement(),
    ];

    $driver->preloadAssetTransforms([
        new AssetTransformRequest($assets[0], 'craft', ['width' => 320], []),
        new AssetTransformRequest($assets[1], 'craft', ['width' => 320], []),
        new AssetTransformRequest($assets[0], 'craft', ['width' => 320], []),
        new AssetTransformRequest($assets[1], 'craft', ['width' => 640], []),
    ]);

    expect($driver->preloads)->toHaveCount(2)
        ->and($driver->preloads[0]['transforms'][0]->width)->toBe(320)
        ->and($driver->preloads[0]['assets'])->toBe($assets)
        ->and($driver->preloads[1]['transforms'][0]->width)->toBe(640)
        ->and($driver->preloads[1]['assets'])->toBe([$assets[1]]);
});

it('normalizes srcset sizes from a named reference transform for preloading', function () {
    $driver = new PreloadingAssetTransformDriver;
    $manager = app(AssetTransforms::class)->extend('test', fn () => $driver);
    Cms::config()->defaultAssetTransformDriver('test');
    app(ImageTransforms::class)->saveTransform(new ImageTransform([
        'name' => 'Card',
        'handle' => 'card',
        'width' => 400,
        'height' => 200,
    ]));

    $manager->preload([Asset::factory()->createElement()], ['card', '320w', '2x']);

    expect(array_map(fn (AssetTransformRequest $request) => [
        'height' => $request->operations['height'],
        'width' => $request->operations['width'],
    ], $driver->requests))->toBe([
        ['height' => 200, 'width' => 400],
        ['height' => 160, 'width' => 320],
        ['height' => 400, 'width' => 800],
    ]);
});

it('preloads transforms requested by an Asset query', function () {
    $driver = new PreloadingAssetTransformDriver;
    $incapable = new TestAssetTransformDriver;
    app(AssetTransforms::class)
        ->extend('test', fn () => $driver)
        ->extend('incapable', fn () => $incapable);
    Cms::config()->defaultAssetTransformDriver('test');
    $assets = [
        Asset::factory()->createElement(),
        Asset::factory()->createElement(),
    ];

    AssetElement::find()
        ->id(array_column($assets, 'id'))
        ->withTransforms([
            ['width' => 320],
            ['driver' => 'incapable', 'width' => 640],
        ])
        ->all();

    expect($driver->requests)->toHaveCount(2)
        ->and(array_column($driver->requests, 'driver'))->toBe(['test', 'test'])
        ->and($incapable->request)->toBeNull();
});

it('fails preloading when a selected driver is invalid', function () {
    $driver = new PreloadingAssetTransformDriver;
    $manager = assetTransformsWith($driver);

    expect(fn () => $manager->preload(
        [Asset::factory()->createElement()],
        [['driver' => 'missing', 'width' => 320]],
    ))->toThrow(AssetTransformDriverNotFoundException::class)
        ->and($driver->requests)->toBeEmpty();
});

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

class PreloadingAssetTransformDriver extends TestAssetTransformDriver implements PreloadsAssetTransforms
{
    public array $requests = [];

    public function preloadAssetTransforms(array $requests): void
    {
        $this->requests = $requests;
    }
}

class TestCraftPreloadingDriver extends ImageTransformer
{
    public array $preloads = [];

    #[Override]
    public function eagerLoadTransforms(array $transforms, array $assets): void
    {
        $this->preloads[] = compact('transforms', 'assets');
    }
}

class RenderingAssetTransformDriver extends TestAssetTransformDriver
{
    #[Override]
    public function transform(AssetTransformRequest $request): AssetTransformResult
    {
        $this->request = $request;
        $width = $request->operations['width'] ?? 640;
        $height = $request->operations['height'] ?? (int) ($width / 2);

        return new AssetTransformResult(
            url: "/renditions/{$width}x{$height}.webp",
            mimeType: 'image/webp',
            width: $width,
            height: $height,
        );
    }
}
