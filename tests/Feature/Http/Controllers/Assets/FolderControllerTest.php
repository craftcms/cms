<?php

declare(strict_types=1);

use CraftCms\Cms\Asset\Models\Volume;
use CraftCms\Cms\Asset\Models\VolumeFolder as VolumeFolderModel;
use CraftCms\Cms\Http\Controllers\Assets\FolderController;
use CraftCms\Cms\User\Elements\User;
use Illuminate\Support\Facades\Storage;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\postJson;

beforeEach(function () {
    actingAs(User::findOne());

    config()->set('filesystems.disks.test-disk', [
        'driver' => 'local',
        'root' => storage_path('framework/testing/folder-controller-test/test-disk'),
    ]);

    $this->volume = Volume::factory()->create(['fs' => 'test-disk']);
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

it('returns bad request when creating under a missing parent folder', function () {
    postJson(action([FolderController::class, 'create']), [
        'parentId' => 999999,
        'folderName' => 'New Folder',
    ])->assertBadRequest();
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

it('returns bad request when deleting a missing folder', function () {
    postJson(action([FolderController::class, 'delete']), [
        'folderId' => 999999,
    ])->assertBadRequest();
});

it('validates rename folder input', function () {
    postJson(action([FolderController::class, 'rename']))
        ->assertJsonValidationErrors(['folderId', 'newName']);
});

it('returns bad request when renaming a missing folder', function () {
    postJson(action([FolderController::class, 'rename']), [
        'folderId' => 999999,
        'newName' => 'Renamed Folder',
    ])->assertBadRequest();
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
        ->assertJsonPath('newName', 'Renamed-Folder')
        ->assertJsonPath('folderUrl', fn (string $url): bool => str_ends_with($url, '/Renamed-Folder'));
});

it('validates move folder input', function () {
    postJson(action([FolderController::class, 'move']))
        ->assertJsonValidationErrors(['folderId', 'parentId']);
});

it('returns bad request when moving a missing folder', function (bool $missingSource) {
    $payload = $missingSource
        ? ['folderId' => 999999, 'parentId' => $this->folder->id]
        : ['folderId' => $this->folder->id, 'parentId' => 999999];

    postJson(action([FolderController::class, 'move']), $payload)->assertBadRequest();
})->with([
    'missing source' => true,
    'missing destination' => false,
]);

it('rejects moving a folder to an invalid relative location without deleting it', function (string $destination, array $resolution) {
    $source = VolumeFolderModel::factory()->create([
        'volumeId' => $this->volume->id,
        'parentId' => $this->folder->id,
        'path' => 'source/',
        'name' => 'source',
    ]);
    $child = VolumeFolderModel::factory()->create([
        'volumeId' => $this->volume->id,
        'parentId' => $source->id,
        'path' => 'source/child/',
        'name' => 'child',
    ]);
    $destinationId = match ($destination) {
        'current parent' => $this->folder->id,
        'itself' => $source->id,
        'descendant' => $child->id,
    };

    postJson(action([FolderController::class, 'move']), [
        'folderId' => $source->id,
        'parentId' => $destinationId,
        ...$resolution,
    ])->assertBadRequest();

    expect(VolumeFolderModel::query()->find($source->id))->not->toBeNull()
        ->and(VolumeFolderModel::query()->find($child->id))->not->toBeNull();
})->with([
    'current parent with replacement' => ['current parent', ['force' => true]],
    'current parent with merge' => ['current parent', ['merge' => true]],
    'itself' => ['itself', []],
    'descendant' => ['descendant', []],
]);

it('rejects replacing an ancestor folder without deleting its contents', function () {
    $ancestor = VolumeFolderModel::factory()->create([
        'volumeId' => $this->volume->id,
        'parentId' => $this->folder->id,
        'path' => 'photos/',
        'name' => 'photos',
    ]);
    $parent = VolumeFolderModel::factory()->create([
        'volumeId' => $this->volume->id,
        'parentId' => $ancestor->id,
        'path' => 'photos/archive/',
        'name' => 'archive',
    ]);
    $source = VolumeFolderModel::factory()->create([
        'volumeId' => $this->volume->id,
        'parentId' => $parent->id,
        'path' => 'photos/archive/photos/',
        'name' => 'photos',
    ]);
    $sourcePath = 'photos/archive/photos/source.txt';
    Storage::disk('test-disk')->put($sourcePath, 'source contents');

    postJson(action([FolderController::class, 'move']), [
        'folderId' => $source->id,
        'parentId' => $this->folder->id,
        'force' => true,
    ])->assertBadRequest();

    expect(VolumeFolderModel::query()->find($ancestor->id))->not->toBeNull()
        ->and(VolumeFolderModel::query()->find($source->id))->not->toBeNull()
        ->and(Storage::disk('test-disk')->get($sourcePath))->toBe('source contents');
});

it('can move a folder whose parent has a null path', function () {
    $sourceName = fake()->uuid();
    $destinationName = fake()->uuid();

    $source = VolumeFolderModel::factory()->create([
        'volumeId' => $this->volume->id,
        'parentId' => $this->folder->id,
        'path' => "$sourceName/",
        'name' => $sourceName,
    ]);

    $destination = VolumeFolderModel::factory()->create([
        'volumeId' => $this->volume->id,
        'parentId' => $this->folder->id,
        'path' => "$destinationName/",
        'name' => $destinationName,
    ]);

    expect($this->folder->path)->toBeNull();

    postJson(action([FolderController::class, 'move']), [
        'folderId' => $source->id,
        'parentId' => $destination->id,
    ])
        ->assertOk()
        ->assertJsonPath('newFolderUrl', fn (string $url): bool => str_ends_with($url, "/$destinationName/$sourceName"));

    expect(VolumeFolderModel::query()
        ->where('parentId', $destination->id)
        ->where('path', "$destinationName/$sourceName/")
        ->exists())->toBeTrue();
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
