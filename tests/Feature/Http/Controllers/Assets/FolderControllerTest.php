<?php

declare(strict_types=1);

use CraftCms\Cms\Asset\Models\Volume;
use CraftCms\Cms\Asset\Models\VolumeFolder as VolumeFolderModel;
use CraftCms\Cms\Http\Controllers\Assets\FolderController;
use CraftCms\Cms\User\Elements\User;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\postJson;

beforeEach(function () {
    actingAs(User::findOne());

    config()->set('filesystems.disks.test-disk', [
        'driver' => 'local',
        'root' => storage_path('framework/testing/folder-controller-test/test-disk'),
    ]);

    $this->volume = Volume::factory()->create(['fs' => 'disk:test-disk']);
    $this->folder = VolumeFolderModel::factory()->create(['volumeId' => $this->volume->id]);
});

it('requires authentication', function () {
    auth()->logout();

    postJson(action([FolderController::class, 'create']), [
        'parentId' => $this->folder->id,
        'folderName' => 'New Folder',
    ])->assertUnauthorized();
});

it('can create a folder', function () {
    postJson(action([FolderController::class, 'create']), [
        'parentId' => $this->folder->id,
        'folderName' => 'New Folder',
    ])
        ->assertOk()
        ->assertJsonPath('folderName', 'New-Folder');
});

it('validates create folder input', function () {
    postJson(action([FolderController::class, 'create']))
        ->assertJsonValidationErrors(['parentId', 'folderName']);
});

it('can delete a folder', function () {
    $subfolder = VolumeFolderModel::factory()->create([
        'volumeId' => $this->volume->id,
        'parentId' => $this->folder->id,
        'path' => $this->folder->path.'subfolder/',
    ]);

    postJson(action([FolderController::class, 'delete']), [
        'folderId' => $subfolder->id,
    ])->assertOk();
});

it('validates delete folder input', function () {
    postJson(action([FolderController::class, 'delete']))
        ->assertJsonValidationErrors(['folderId']);
});

it('validates rename folder input', function () {
    postJson(action([FolderController::class, 'rename']))
        ->assertJsonValidationErrors(['folderId', 'newName']);
});

it('can rename a folder', function () {
    // Create the actual directory on disk so the rename operation succeeds
    $diskRoot = storage_path('framework/testing/folder-controller-test/test-disk');
    $subfolderPath = $diskRoot.'/subfolder';
    if (! is_dir($subfolderPath)) {
        mkdir($subfolderPath, 0755, true);
    }

    $subfolder = VolumeFolderModel::factory()->create([
        'volumeId' => $this->volume->id,
        'parentId' => $this->folder->id,
        'path' => 'subfolder/',
        'name' => 'subfolder',
    ]);

    postJson(action([FolderController::class, 'rename']), [
        'folderId' => $subfolder->id,
        'newName' => 'Renamed Folder',
    ])
        ->assertOk()
        ->assertJsonPath('newName', 'Renamed-Folder');
});

it('validates move folder input', function () {
    postJson(action([FolderController::class, 'move']))
        ->assertJsonValidationErrors(['folderId', 'parentId']);
});

it('handles folder move conflicts', function () {
    $subfolder = VolumeFolderModel::factory()->create([
        'volumeId' => $this->volume->id,
        'parentId' => $this->folder->id,
        'path' => $this->folder->path.'same-name/',
        'name' => 'same-name',
    ]);

    $destination = VolumeFolderModel::factory()->create([
        'volumeId' => $this->volume->id,
        'parentId' => $this->folder->id,
        'path' => $this->folder->path.'destination/',
        'name' => 'destination',
    ]);

    // Create a conflicting folder at the destination
    VolumeFolderModel::factory()->create([
        'volumeId' => $this->volume->id,
        'parentId' => $destination->id,
        'path' => $destination->path.'same-name/',
        'name' => 'same-name',
    ]);

    postJson(action([FolderController::class, 'move']), [
        'folderId' => $subfolder->id,
        'parentId' => $destination->id,
    ])
        ->assertOk()
        ->assertJsonStructure(['conflict']);
});
