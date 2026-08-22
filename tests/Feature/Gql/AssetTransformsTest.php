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
use CraftCms\Cms\Gql\AssetTransformContext;
use CraftCms\Cms\Gql\Directives\Transform;
use CraftCms\Cms\Gql\GqlHelper;
use CraftCms\Cms\Support\Str;
use GraphQL\Type\Definition\ResolveInfo;

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
    $resolveInfo = new ReflectionClass(ResolveInfo::class)->newInstanceWithoutConstructor();
    $resolveInfo->fieldName = 'asset';

    $transformed = Transform::apply(null, $asset, ['width' => 320], $resolveInfo);
    $transformedList = Transform::apply(null, collect([$asset]), ['ratio' => 1.5], $resolveInfo);

    expect($transformed)->not->toBe($asset)
        ->and(app(AssetTransformContext::class)->get($transformed)->definition)->toBe(['width' => 320])
        ->and($transformedList[0])->not->toBe($asset)
        ->and(app(AssetTransformContext::class)->get($transformedList[0])->definition)->toBe(['ratio' => 1.5])
        ->and(app(AssetTransformContext::class)->get($asset))->toBeNull()
        ->and($asset->getWidth())->toBe(800);
});

it('resolves rendition fields for a capable non-image driver', function () {
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
        ->assertJsonPath('data.asset.url', '/gql-rendition.webp')
        ->assertJsonPath('data.asset.custom', '/gql-rendition.webp')
        ->assertJsonPath('data.asset.width', 320)
        ->assertJsonPath('data.asset.height', 180)
        ->assertJsonPath('data.asset.format', 'webp')
        ->assertJsonPath('data.asset.mimeType', 'image/webp')
        ->assertJsonPath('data.transformed.url', '/gql-rendition.webp');
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
        ->assertJsonPath('data.assets.0.url', '/gql-rendition.webp');

    expect($this->driver->preloaded)->toHaveCount(1)
        ->and($this->driver->preloaded[0]->operations)->toBe(['width' => 320]);
});

it('passes non-null immediately arguments to the selected driver', function () {
    $asset = Asset::factory()->createElement();
    gqlActivateFullAccessSchema();
    graphQL(<<<GQL
        {
            asset(id: {$asset->id}) {
                url(width: 320, immediately: false)
            }
        }
        GQL)
        ->assertOk()
        ->assertJsonPath('data.asset.url', '/gql-rendition.webp');

    expect(GqlHelper::prepareTransformArguments(['immediately' => false]))->toBe([])
        ->and($this->driver->request?->immediately)->toBeFalse();
});

class GqlAssetTransformDriver implements AssetTransformDriver, PreloadsAssetTransforms
{
    public array $preloaded = [];

    public ?AssetTransformRequest $request = null;

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
            url: '/gql-rendition.webp',
            mimeType: 'image/webp',
            width: $request->operations['width'] ?? 640,
            height: $request->operations['height'] ?? 360,
        );
    }

    public function preloadAssetTransforms(array $requests): void
    {
        $this->preloaded = $requests;
    }
}
