<?php

declare(strict_types=1);

use CraftCms\Cms\Asset\AssetProcessors;
use CraftCms\Cms\Asset\AssetTransformDrivers;
use CraftCms\Cms\Asset\Contracts\AssetTransformDriver;
use CraftCms\Cms\Asset\Data\AssetProcessor;
use CraftCms\Cms\Asset\Data\AssetTransformDriverDefinition;
use CraftCms\Cms\Asset\Data\AssetTransformRequest;
use CraftCms\Cms\Asset\Data\AssetTransformResult;
use CraftCms\Cms\Asset\Models\Asset as AssetModel;
use CraftCms\Cms\Asset\Models\Volume;
use CraftCms\Cms\Asset\Models\VolumeFolder as VolumeFolderModel;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Http\Controllers\Assets\TransformController;
use CraftCms\Cms\Image\Data\ImageTransform;
use CraftCms\Cms\Image\ImageTransformer;
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
    it('serves Craft driver renditions from filesystems without URLs', function () {
        $asset = AssetModel::factory()->createElement([
            'volumeId' => test()->volume->id,
            'folderId' => test()->folder->id,
            'filename' => 'private-transform.jpg',
            'kind' => 'image',
            'width' => 1200,
            'height' => 800,
            'dateModified' => now()->subMinute(),
        ]);
        $transformer = app(ImageTransformer::class);
        $transform = new ImageTransform(['width' => 100]);
        $index = $transformer->getTransformIndex($asset, $transform);
        $path = $asset->getVolume()->uid.DIRECTORY_SEPARATOR.$asset->folderPath.$index->transformString.DIRECTORY_SEPARATOR.$index->filename;
        $asset->getVolume()->sourceDisk()->put($path, 'transform-bytes');
        $index->fileExists = true;
        $transformer->storeTransformIndexData($index);

        get(action([TransformController::class, 'generate'], ['transformId' => $index->id]))
            ->assertForbidden();

        $result = app(AssetProcessors::class)->transform($asset, ['width' => 100]);

        get($result->url)
            ->assertOk()
            ->assertStreamedContent('transform-bytes');
    });

    it('serves Craft driver renditions from a configured private output filesystem', function () {
        config()->set('filesystems.disks.configured-private-source', [
            'driver' => 'local',
            'root' => storage_path('framework/testing/transform-controller-test/configured-private-source'),
        ]);
        config()->set('filesystems.disks.configured-private-target', [
            'driver' => 'local',
            'root' => storage_path('framework/testing/transform-controller-test/configured-private-target'),
        ]);
        app(AssetProcessors::class)->saveAssetProcessor(new AssetProcessor([
            'uid' => Str::uuid()->toString(),
            'name' => 'Configured private',
            'handle' => 'configured-private',
            'driver' => 'craft',
            'settings' => [
                'filesystem' => 'disk:configured-private-target',
                'subpath' => 'renditions',
            ],
        ]), false);
        $volume = Volume::factory()->create([
            'fs' => 'disk:configured-private-source',
            'assetProcessor' => 'configured-private',
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

        $result = app(AssetProcessors::class)->transform($asset, ['width' => 100]);

        get($result->url)
            ->assertOk()
            ->assertHeader('Content-Type', 'image/jpeg');
    });

    it('rejects invalid private transform tokens', function () {
        get(action([TransformController::class, 'generate'], ['transformToken' => 'invalid']))
            ->assertStatus(400);
    });

    it('generates Craft driver renditions from private sources', function () {
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
        $result = app(ImageTransformer::class)->transform(new AssetTransformRequest(
            $asset,
            app(AssetProcessors::class)->resolve('craft'),
            ['width' => 100],
            true,
        ));

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

    it('generates named transforms with the selected processor', function () {
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
        app(AssetProcessors::class)->saveAssetProcessor(new AssetProcessor([
            'uid' => Str::uuid()->toString(),
            'name' => 'Controller test',
            'handle' => 'controller-test',
            'driver' => 'controller-test',
        ]), false);
        Cms::config()->defaultAssetProcessor('controller-test');
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

        expect($driver->request?->processor->handle)->toBe('controller-test');
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
