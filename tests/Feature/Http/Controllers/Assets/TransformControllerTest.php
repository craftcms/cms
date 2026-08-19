<?php

declare(strict_types=1);

use CraftCms\Cms\Asset\AssetTransforms;
use CraftCms\Cms\Asset\Data\AssetTransformRequest;
use CraftCms\Cms\Asset\Models\Asset as AssetModel;
use CraftCms\Cms\Asset\Models\Volume;
use CraftCms\Cms\Asset\Models\VolumeFolder as VolumeFolderModel;
use CraftCms\Cms\Http\Controllers\Assets\TransformController;
use CraftCms\Cms\Image\Data\ImageTransform;
use CraftCms\Cms\Image\ImageTransformer;
use CraftCms\Cms\Support\Facades\Path;
use CraftCms\Cms\User\Elements\User;
use Illuminate\Support\Facades\Crypt;
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
        $path = $asset->folderPath.$index->transformString.DIRECTORY_SEPARATOR.$index->filename;
        $asset->getVolume()->transformDisk()->put($path, 'transform-bytes');
        $index->fileExists = true;
        $transformer->storeTransformIndexData($index);

        get(action([TransformController::class, 'generate'], ['transformId' => $index->id]))
            ->assertForbidden();

        $result = app(AssetTransforms::class)->transform($asset, ['width' => 100]);

        get($result->url)
            ->assertOk()
            ->assertStreamedContent('transform-bytes');
    });

    it('serves Craft driver renditions from a configured private output filesystem', function () {
        config()->set('filesystems.disks.configured-private-source', [
            'driver' => 'local',
            'root' => storage_path('framework/testing/transform-controller-test/configured-private-source'),
            'asset_transform' => [
                'driver' => 'craft',
                'settings' => [
                    'filesystem' => 'disk:configured-private-target',
                    'subpath' => 'renditions',
                ],
            ],
        ]);
        config()->set('filesystems.disks.configured-private-target', [
            'driver' => 'local',
            'root' => storage_path('framework/testing/transform-controller-test/configured-private-target'),
        ]);
        $volume = Volume::factory()->create(['fs' => 'disk:configured-private-source']);
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

        $result = app(AssetTransforms::class)->transform($asset, ['width' => 100]);

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
            'craft',
            ['width' => 100],
            ['generateBeforePageLoad' => true],
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

describe('generateFallback', function () {
    it('allows anonymous access', function () {
        $asset = AssetModel::factory()->create([
            'volumeId' => test()->volume->id,
            'folderId' => test()->folder->id,
            'filename' => 'fallback-test.jpg',
            'kind' => 'image',
        ]);

        $transform = Crypt::encrypt($asset->id.',_100x100_crop_center-center_none');

        $response = get(action([TransformController::class, 'generateFallback'], ['transform' => $transform]));

        expect($response->getStatusCode())->not->toBe(401)
            ->and($response->getStatusCode())->not->toBe(403);
    });

    it('returns 400 for invalid encrypted param', function () {
        get(action([TransformController::class, 'generateFallback'], ['transform' => 'invalid-data']))
            ->assertStatus(400);
    });

    it('serves originals from within the volume subpath', function () {
        $volume = Volume::factory()->create([
            'fs' => 'disk:test-disk',
            'subpath' => 'assets',
        ]);
        $folder = VolumeFolderModel::factory()->create(['volumeId' => $volume->id]);
        $asset = AssetModel::factory()->createElement([
            'volumeId' => $volume->id,
            'folderId' => $folder->id,
            'filename' => 'subpath-original.jpg',
            'kind' => 'image',
        ]);
        $sourceDisk = $asset->getVolume()->sourceDisk();
        $sourceDisk->put($asset->getPath(), 'original-bytes');
        $transform = Crypt::encrypt($asset->id.',original');

        $response = get(action([TransformController::class, 'generateFallback'], ['transform' => $transform]))
            ->assertOk();

        expect($response->baseResponse->getFile()->getRealPath())
            ->toBe(realpath($sourceDisk->path($asset->getPath())));
    });

    it('serves fallback transform files for valid encrypted transforms', function () {
        $asset = AssetModel::factory()->create([
            'volumeId' => test()->volume->id,
            'folderId' => test()->folder->id,
            'filename' => 'fallback-test.jpg',
            'kind' => 'image',
        ]);

        $transformString = '_101x99_crop_center-center_none';
        $transform = Crypt::encrypt($asset->id.','.$transformString);
        $path = implode(DIRECTORY_SEPARATOR, [
            Path::imageTransforms(),
            $transformString,
            sprintf('%s.jpg', $asset->id),
        ]);

        @mkdir(dirname($path), 0777, true);
        file_put_contents($path, 'transform-bytes');

        get(action([TransformController::class, 'generateFallback'], ['transform' => $transform]))
            ->assertOk();
    });
});
