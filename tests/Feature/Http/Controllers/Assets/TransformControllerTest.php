<?php

declare(strict_types=1);

use CraftCms\Cms\Asset\Models\Asset as AssetModel;
use CraftCms\Cms\Asset\Models\Volume;
use CraftCms\Cms\Asset\Models\VolumeFolder as VolumeFolderModel;
use CraftCms\Cms\Http\Controllers\Assets\TransformController;
use CraftCms\Cms\User\Elements\User;
use Illuminate\Support\Facades\Crypt;

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
    it('allows anonymous access', function () {
        $asset = AssetModel::factory()->create([
            'volumeId' => test()->volume->id,
            'folderId' => test()->folder->id,
            'filename' => 'transform-test.jpg',
            'kind' => 'image',
        ]);

        // Anonymous access should not return 401/403
        $response = postJson(action([TransformController::class, 'generate']), [
            'assetId' => $asset->id,
            'handle' => '_100x100_crop_center-center_none',
        ]);

        expect($response->status())->not->toBe(401)
            ->and($response->status())->not->toBe(403);
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

        // Anonymous access should not return 401/403
        $response = get(action([TransformController::class, 'generateFallback'], ['transform' => $transform]));

        expect($response->getStatusCode())->not->toBe(401)
            ->and($response->getStatusCode())->not->toBe(403);
    });

    it('returns 400 for invalid encrypted param', function () {
        get(action([TransformController::class, 'generateFallback'], ['transform' => 'invalid-data']))
            ->assertStatus(400);
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
            \Craft::$app->getPath()->getImageTransformsPath(),
            $transformString,
            sprintf('%s.jpg', $asset->id),
        ]);

        @mkdir(dirname($path), 0777, true);
        file_put_contents($path, 'transform-bytes');

        get(action([TransformController::class, 'generateFallback'], ['transform' => $transform]))
            ->assertOk();
    });
});
