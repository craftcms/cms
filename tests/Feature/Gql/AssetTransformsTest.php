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
use CraftCms\Cms\Asset\Models\Asset;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Image\Data\ImageTransform;
use CraftCms\Cms\Image\ImageTransforms;
use CraftCms\Cms\Support\Str;

beforeEach(function () {
    $this->driver = new GqlAssetTransformDriver;
    $driver = $this->driver;
    app(AssetTransformDrivers::class)->extend('gql', fn () => $driver);
    app(AssetTransformers::class)->saveAssetTransformer(new AssetTransformer([
        'uid' => Str::uuid()->toString(),
        'name' => 'GraphQL',
        'handle' => 'gql',
        'driver' => 'gql',
    ]), false);
    Cms::config()->defaultAssetTransformer('gql');
});

it('keeps directive transforms local to their resolved Assets', function () {
    $asset = Asset::factory()->createElement([
        'width' => 800,
        'height' => 400,
    ]);
    gqlActivateFullAccessSchema();

    graphQL(<<<GQL
        {
            transformed: asset(id: {$asset->id}) @transform(width: 320) {
                width
                height
            }
            transformedList: assets(id: [{$asset->id}]) @transform(height: 180) {
                width
                height
            }
            source: asset(id: {$asset->id}) {
                width
                height
            }
        }
        GQL)
        ->assertOk()
        ->assertJsonPath('data.transformed.width', 320)
        ->assertJsonPath('data.transformed.height', 360)
        ->assertJsonPath('data.transformedList.0.width', 640)
        ->assertJsonPath('data.transformedList.0.height', 180)
        ->assertJsonPath('data.source.width', 800)
        ->assertJsonPath('data.source.height', 400);
});

it('resolves transform result fields for a capable non-image driver', function () {
    $asset = Asset::factory()->createElement([
        'filename' => 'document.pdf',
        'kind' => 'pdf',
    ]);
    gqlActivateFullAccessSchema();

    graphQL(<<<GQL
        {
            asset(id: {$asset->id}) {
                url(width: 320)
                custom: url(transformer: "gql", width: 640)
                width(width: 320)
                height(height: 180)
                format(format: "webp")
                mimeType @transform(width: 320)
            }
            transformed: asset(id: {$asset->id}) @transform(width: 200) {
                url
            }
        }
        GQL)
        ->assertOk()
        ->assertJsonPath('data.asset.url', '/gql-transform.webp')
        ->assertJsonPath('data.asset.custom', '/gql-transform.webp')
        ->assertJsonPath('data.asset.width', 320)
        ->assertJsonPath('data.asset.height', 180)
        ->assertJsonPath('data.asset.format', 'webp')
        ->assertJsonPath('data.asset.mimeType', 'image/webp')
        ->assertJsonPath('data.transformed.url', '/gql-transform.webp');
});

it('applies transformer and parameter overrides to named transforms', function () {
    $driver = new GqlAssetTransformDriver('/explicit-transform.webp');
    app(AssetTransformDrivers::class)->extend('explicit-gql', fn () => $driver);
    app(AssetTransformers::class)->saveAssetTransformer(new AssetTransformer([
        'uid' => Str::uuid()->toString(),
        'name' => 'Explicit GraphQL',
        'handle' => 'explicit-gql',
        'driver' => 'explicit-gql',
    ]), false);
    app(ImageTransforms::class)->saveTransform(new ImageTransform([
        'name' => 'Thumbnail',
        'handle' => 'thumbnail',
        'width' => 200,
    ]));
    $asset = Asset::factory()->createElement();
    gqlActivateFullAccessSchema();

    graphQL(<<<GQL
        {
            asset(id: {$asset->id}) {
                url(handle: "thumbnail", transformer: "explicit-gql", width: 480)
                width(handle: "thumbnail", transformer: "explicit-gql", width: 480)
            }
        }
        GQL)
        ->assertOk()
        ->assertJsonPath('data.asset.url', '/explicit-transform.webp')
        ->assertJsonPath('data.asset.width', 480);
});

it('keeps Asset field resolution order-independent', function () {
    $asset = Asset::factory()->createElement([
        'width' => 800,
        'height' => 400,
    ]);
    gqlActivateFullAccessSchema();

    graphQL(<<<GQL
        {
            asset(id: {$asset->id}) {
                sourceBefore: width
                transformed: width(width: 320)
                sourceAfter: width
            }
        }
        GQL)
        ->assertOk()
        ->assertJsonPath('data.asset.sourceBefore', 800)
        ->assertJsonPath('data.asset.transformed', 320)
        ->assertJsonPath('data.asset.sourceAfter', 800);
});

it('preloads GraphQL list transforms through the selected driver', function () {
    $asset = Asset::factory()->createElement();
    gqlActivateFullAccessSchema();

    graphQL(<<<GQL
        {
            assets(id: [{$asset->id}]) {
                url(width: 320)
            }
        }
        GQL)
        ->assertOk()
        ->assertJsonPath('data.assets.0.url', '/gql-transform.webp');

    expect($this->driver->preloaded)->toHaveCount(1)
        ->and($this->driver->preloaded[0]->parameters)->toBe(['width' => 320]);
});

class GqlAssetTransformDriver implements AssetTransformDriver, PreloadsAssetTransforms
{
    public array $preloaded = [];

    public ?AssetTransformRequest $request = null;

    public function __construct(private readonly string $url = '/gql-transform.webp') {}

    public function definition(): AssetTransformDriverDefinition
    {
        return new AssetTransformDriverDefinition('GraphQL', [
            'ratio' => ['numeric'],
        ]);
    }

    public function transform(AssetTransformRequest $request): AssetTransformResult
    {
        $this->request = $request;

        return new AssetTransformResult(
            url: $this->url,
            mimeType: 'image/webp',
            width: $request->parameters['width'] ?? 640,
            height: $request->parameters['height'] ?? 360,
        );
    }

    public function preloadAssetTransforms(array $requests): void
    {
        $this->preloaded = $requests;
    }
}
