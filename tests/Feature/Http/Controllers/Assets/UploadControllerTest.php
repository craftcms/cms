<?php

declare(strict_types=1);

use CraftCms\Cms\Asset\Models\Volume;
use CraftCms\Cms\Asset\Models\VolumeFolder as VolumeFolderModel;
use CraftCms\Cms\Http\Controllers\Assets\UploadController;
use CraftCms\Cms\User\Elements\User;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\postJson;

beforeEach(function () {
    actingAs(User::findOne());

    config()->set('filesystems.disks.test-disk', [
        'driver' => 'local',
        'root' => storage_path('framework/testing/upload-controller-test/test-disk'),
    ]);

    $this->volume = Volume::factory()->create(['fs' => 'disk:test-disk']);
    $this->folder = VolumeFolderModel::factory()->create(['volumeId' => $this->volume->id]);
});

it('requires authentication', function () {
    auth()->logout();

    postJson(action([UploadController::class, 'upload']))
        ->assertUnauthorized();
});

it('requires a file or field for upload', function () {
    postJson(action([UploadController::class, 'upload']), [
        'folderId' => $this->folder->id,
    ])->assertStatus(400);
});

it('requires authentication for replace file', function () {
    auth()->logout();

    postJson(action([UploadController::class, 'replaceFile']))
        ->assertUnauthorized();
});

it('validates replace file parameters', function () {
    postJson(action([UploadController::class, 'replaceFile']))
        ->assertStatus(400);
});
