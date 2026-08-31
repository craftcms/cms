<?php

declare(strict_types=1);

use CraftCms\Cms\Asset\Models\Asset as AssetModel;
use CraftCms\Cms\Asset\Models\Volume;
use CraftCms\Cms\Asset\Models\VolumeFolder as VolumeFolderModel;
use CraftCms\Cms\Http\Controllers\Assets\PreviewController;
use CraftCms\Cms\User\Elements\User;
use Illuminate\Support\Facades\Queue;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\postJson;

beforeEach(function () {
    actingAs(User::findOne());

    config()->set('filesystems.disks.test-disk', [
        'driver' => 'local',
        'root' => storage_path('framework/testing/preview-controller-test/test-disk'),
    ]);

    $this->volume = Volume::factory()->create(['fs' => 'disk:test-disk']);
    $this->folder = VolumeFolderModel::factory()->create(['volumeId' => $this->volume->id]);
});

it('requires authentication', function () {
    auth()->logout();

    postJson(action([PreviewController::class, 'previewThumb']), [
        'assetId' => 1,
        'width' => 100,
        'height' => 100,
    ])->assertUnauthorized();
});

it('validates preview thumb input', function () {
    postJson(action([PreviewController::class, 'previewThumb']))
        ->assertJsonValidationErrors(['assetId', 'width', 'height']);
});

it('can preview a thumb', function () {
    Queue::fake();
    $asset = AssetModel::factory()->createElement([
        'volumeId' => $this->volume->id,
        'folderId' => $this->folder->id,
        'filename' => 'test.jpg',
        'kind' => 'image',
    ]);

    postJson(action([PreviewController::class, 'previewThumb']), [
        'assetId' => $asset->id,
        'width' => 100,
        'height' => 100,
    ])
        ->assertOk()
        ->assertJsonStructure(['img']);
});

it('validates preview file input', function () {
    postJson(action([PreviewController::class, 'previewFile']))
        ->assertJsonValidationErrors(['assetId', 'requestId']);
});

it('can preview a file', function () {
    // Create the file on disk so the preview handler can open it
    $diskRoot = storage_path('framework/testing/preview-controller-test/test-disk');
    if (! is_dir($diskRoot)) {
        mkdir($diskRoot, 0755, true);
    }
    file_put_contents($diskRoot.'/test.txt', 'Hello world');

    $asset = AssetModel::factory()->createElement([
        'volumeId' => $this->volume->id,
        'folderId' => $this->folder->id,
        'filename' => 'test.txt',
        'kind' => 'text',
    ]);

    postJson(action([PreviewController::class, 'previewFile']), [
        'assetId' => $asset->id,
        'requestId' => 'req-123',
    ])
        ->assertOk()
        ->assertJsonStructure(['previewHtml', 'requestId'])
        ->assertJsonPath('requestId', 'req-123');
});
