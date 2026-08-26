<?php

declare(strict_types=1);

use CraftCms\Cms\Asset\AssetTransformDrivers;
use CraftCms\Cms\Asset\AssetTransformers;
use CraftCms\Cms\Asset\Contracts\AssetTransformDriver;
use CraftCms\Cms\Asset\Data\AssetTransformDriverDefinition;
use CraftCms\Cms\Asset\Data\AssetTransformer;
use CraftCms\Cms\Asset\Data\AssetTransformRequest;
use CraftCms\Cms\Asset\Data\AssetTransformResult;
use CraftCms\Cms\Asset\Models\Asset as AssetModel;
use CraftCms\Cms\Asset\Models\Volume;
use CraftCms\Cms\Asset\Models\VolumeFolder as VolumeFolderModel;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Http\Controllers\Assets\TransformController;
use CraftCms\Cms\Image\Data\ImageTransform;
use CraftCms\Cms\Image\ImageTransforms;
use CraftCms\Cms\Support\Str;
use CraftCms\Cms\User\Elements\User;
use Illuminate\Support\Facades\Queue;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;
use function Pest\Laravel\postJson;

beforeEach(function () {
    config()->set('filesystems.disks.test-disk', [
        'driver' => 'local',
        'root' => storage_path('framework/testing/transform-controller-test/test-disk'),
    ]);

    $this->volume = Volume::factory()->create(['fs' => 'disk:test-disk']);
    $this->folder = VolumeFolderModel::factory()->create(['volumeId' => $this->volume->id]);
});

describe('generate', function () {
    it('serves Craft driver transform results from filesystems without URLs', function () {
        $asset = AssetModel::factory()->createElement([
            'volumeId' => test()->volume->id,
            'folderId' => test()->folder->id,
            'filename' => 'private-transform.jpg',
            'kind' => 'image',
            'width' => 1200,
            'height' => 800,
            'dateModified' => now()->subMinute(),
        ]);
        $asset->getVolume()->sourceDisk()->put(
            $asset->getPath(),
            file_get_contents(dirname(__DIR__, 4).'/_data/assets/files/background.jpg'),
        );

        $result = app(AssetTransformers::class)->transform($asset, ['width' => 100], true);

        get($result->url)
            ->assertOk()
            ->assertHeader('Content-Type', 'image/jpeg');
    });

    it('serves Craft driver transform results from a configured private output filesystem', function () {
        config()->set('filesystems.disks.configured-private-source', [
            'driver' => 'local',
            'root' => storage_path('framework/testing/transform-controller-test/configured-private-source'),
        ]);
        config()->set('filesystems.disks.configured-private-target', [
            'driver' => 'local',
            'root' => storage_path('framework/testing/transform-controller-test/configured-private-target'),
        ]);
        app(AssetTransformers::class)->saveAssetTransformer(new AssetTransformer([
            'uid' => Str::uuid()->toString(),
            'name' => 'Configured private',
            'handle' => 'configured-private',
            'driver' => 'craft',
            'settings' => [
                'filesystem' => 'disk:configured-private-target',
                'subpath' => 'transforms',
            ],
        ]), false);
        $volume = Volume::factory()->create([
            'fs' => 'disk:configured-private-source',
            'assetTransformer' => 'configured-private',
        ]);
        $folder = VolumeFolderModel::factory()->create(['volumeId' => $volume->id]);
        $asset = AssetModel::factory()->createElement([
            'volumeId' => $volume->id,
            'folderId' => $folder->id,
            'filename' => 'configured-private.jpg',
            'kind' => 'image',
            'width' => 1200,
            'height' => 800,
        ]);
        $asset->getVolume()->sourceDisk()->put(
            $asset->getPath(),
            file_get_contents(dirname(__DIR__, 4).'/_data/assets/files/background.jpg'),
        );
        Queue::fake();

        $result = app(AssetTransformers::class)->transform($asset, ['width' => 100]);

        get($result->url)
            ->assertOk()
            ->assertHeader('Content-Type', 'image/jpeg');
    });

    it('rejects invalid private transform tokens', function () {
        get(action([TransformController::class, 'generate'], ['transformToken' => 'invalid']))
            ->assertStatus(400);
    });

    it('generates Craft driver transform results from private sources', function () {
        $asset = AssetModel::factory()->createElement([
            'volumeId' => test()->volume->id,
            'folderId' => test()->folder->id,
            'filename' => 'private-source.jpg',
            'kind' => 'image',
            'width' => 1200,
            'height' => 800,
            'dateModified' => now()->subMinute(),
        ]);
        $asset->getVolume()->sourceDisk()->put(
            $asset->getPath(),
            file_get_contents(dirname(__DIR__, 4).'/_data/assets/files/background.jpg'),
        );
        $result = app(AssetTransformers::class)->transform($asset, ['width' => 100], true);

        get($result->url)->assertOk();
    });

    it('forbids anonymous access', function () {
        $asset = AssetModel::factory()->create([
            'volumeId' => test()->volume->id,
            'folderId' => test()->folder->id,
            'filename' => 'transform-test.jpg',
            'kind' => 'image',
        ]);

        postJson(action([TransformController::class, 'generate']), [
            'assetId' => $asset->id,
            'handle' => '_100x100_crop_center-center_none',
        ])->assertForbidden();
    });

    it('generates named transforms with the selected transformer', function () {
        $driver = new class implements AssetTransformDriver
        {
            public ?AssetTransformRequest $request = null;

            public function definition(): AssetTransformDriverDefinition
            {
                return new AssetTransformDriverDefinition('Controller test');
            }

            public function transform(AssetTransformRequest $request): AssetTransformResult
            {
                $this->request = $request;

                return new AssetTransformResult('/plugin/card.jpg', 'image/jpeg');
            }
        };
        app(AssetTransformDrivers::class)->extend('controller-test', fn () => $driver);
        app(AssetTransformers::class)->saveAssetTransformer(new AssetTransformer([
            'uid' => Str::uuid()->toString(),
            'name' => 'Controller test',
            'handle' => 'controller-test',
            'driver' => 'controller-test',
        ]), false);
        Cms::config()->defaultAssetTransformer('controller-test');
        app(ImageTransforms::class)->saveTransform(new ImageTransform([
            'name' => 'Card',
            'handle' => 'controllerCard',
            'width' => 320,
        ]));
        $asset = AssetModel::factory()->createElement([
            'volumeId' => $this->volume->id,
            'folderId' => $this->folder->id,
            'filename' => 'named-transform.jpg',
            'kind' => 'image',
        ]);
        actingAs(User::findOne());

        postJson(action([TransformController::class, 'generate']), [
            'assetId' => $asset->id,
            'handle' => 'controllerCard',
        ])->assertOk()->assertJson(['url' => '/plugin/card.jpg']);

        expect($driver->request?->transformer->handle)->toBe('controller-test');
    });

    it('returns error for missing asset id', function () {
        actingAs(User::findOne());

        postJson(action([TransformController::class, 'generate']), [
            'handle' => '_100x100_crop_center-center_none',
        ])->assertStatus(400);
    });

    it('returns error for missing handle', function () {
        actingAs(User::findOne());

        $asset = AssetModel::factory()->create([
            'volumeId' => $this->volume->id,
            'folderId' => $this->folder->id,
        ]);

        postJson(action([TransformController::class, 'generate']), [
            'assetId' => $asset->id,
        ])->assertStatus(400);
    });
});
