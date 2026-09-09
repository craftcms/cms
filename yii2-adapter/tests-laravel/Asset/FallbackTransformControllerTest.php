<?php

declare(strict_types=1);

use CraftCms\Cms\Asset\Models\Asset;
use CraftCms\Cms\Asset\Models\Volume;
use CraftCms\Cms\Asset\Models\VolumeFolder;
use CraftCms\Cms\Support\Facades\Path;
use CraftCms\Cms\Support\File;
use CraftCms\Yii2Adapter\Http\FallbackTransformController;
use CraftCms\Yii2Adapter\Tests\DatabaseTestCase;
use Illuminate\Support\Facades\Crypt;

use function Illuminate\Filesystem\join_paths;

uses(DatabaseTestCase::class);

beforeEach(function(): void {
    config()->set('filesystems.disks.fallback-transform-test', [
        'driver' => 'local',
        'root' => storage_path('framework/testing/fallback-transform-test'),
    ]);

    $this->volume = Volume::factory()->create(['fs' => 'disk:fallback-transform-test']);
    $this->folder = VolumeFolder::factory()->create(['volumeId' => $this->volume->id]);
});

it('rejects an invalid transform parameter', function(): void {
    $this->get(action(FallbackTransformController::class, ['transform' => 'invalid-data']))
        ->assertStatus(400);
});

it('serves originals from the source filesystem', function(): void {
    $asset = Asset::factory()->createElement([
        'volumeId' => $this->volume->id,
        'folderId' => $this->folder->id,
        'filename' => 'source.jpg',
        'kind' => 'image',
    ]);
    $asset->getVolume()->sourceDisk()->put($asset->getPath(), 'original-bytes');

    $this->get(action(FallbackTransformController::class, [
        'transform' => Crypt::encrypt($asset->id . ',original'),
    ]))
        ->assertOk()
        ->assertStreamedContent('original-bytes');
});

it('serves existing fallback transform files', function(): void {
    $asset = Asset::factory()->createElement([
        'volumeId' => $this->volume->id,
        'folderId' => $this->folder->id,
        'filename' => 'source.jpg',
        'kind' => 'image',
    ]);
    $transformString = '_101x99_crop_center-center_none';
    $path = Path::imageTransforms(join_paths($transformString, "{$asset->id}.jpg"));
    File::ensureDirectoryExists(dirname($path));
    file_put_contents($path, 'transform-bytes');

    $this->get(action(FallbackTransformController::class, [
        'transform' => Crypt::encrypt("{$asset->id},{$transformString}"),
    ]))
        ->assertOk()
        ->assertStreamedContent('transform-bytes');
});
