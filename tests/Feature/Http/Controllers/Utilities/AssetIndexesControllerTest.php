<?php

declare(strict_types=1);

use CraftCms\Cms\Asset\Elements\Asset;
use CraftCms\Cms\Asset\Models\Asset as AssetModel;
use CraftCms\Cms\Asset\Models\AssetIndexingSession;
use CraftCms\Cms\Asset\Models\Volume;
use CraftCms\Cms\Asset\Models\VolumeFolder;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Http\Controllers\Utilities\AssetIndexesController;
use CraftCms\Cms\Support\Facades\AssetIndexer;
use CraftCms\Cms\Support\Facades\Volumes;
use CraftCms\Cms\User\Elements\User;
use CraftCms\Cms\Utility\Utilities\AssetIndexes;
use Illuminate\Support\Facades\Storage;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\postJson;

beforeEach(function () {
    actingAs(User::findOne());
});

test('unauthorized users cannot access asset indexes utility', function () {
    Cms::config()->disabledUtilities = [AssetIndexes::id()];

    postJson(action([AssetIndexesController::class, 'startIndexing']), [
        'volumes' => [1],
    ])
        ->assertForbidden();
});

test('start indexing requires volumes parameter', function () {
    postJson(action([AssetIndexesController::class, 'startIndexing']), [])
        ->assertJsonValidationErrors(['volumes']);
});

test('start indexing with no allowed volumes returns error', function () {
    // Pass an invalid volume ID that won't be in the allowed list
    postJson(action([AssetIndexesController::class, 'startIndexing']), [
        'volumes' => [99999],
    ])->assertBadRequest()
        ->assertJson(['message' => 'No volumes specified.']);
});

test('stop indexing session requires sessionId parameter', function () {
    postJson(action([AssetIndexesController::class, 'stopIndexingSession']), [])
        ->assertJsonValidationErrors(['sessionId']);
});

test('stop indexing session returns stop response', function () {
    postJson(action([AssetIndexesController::class, 'stopIndexingSession']), [
        'sessionId' => 999,
    ])
        ->assertOk()
        ->assertJson(['stop' => 999]);
});

test('process indexing session requires sessionId parameter', function () {
    postJson(action([AssetIndexesController::class, 'processIndexingSession']), [])
        ->assertJsonValidationErrors(['sessionId']);
});

test('process indexing session with non-existent session returns stop', function () {
    postJson(action([AssetIndexesController::class, 'processIndexingSession']), [
        'sessionId' => 999,
    ])
        ->assertOk()
        ->assertJson(['stop' => 999]);
});

test('indexing session overview requires sessionId parameter', function () {
    postJson(action([AssetIndexesController::class, 'indexingSessionOverview']), [])
        ->assertJsonValidationErrors(['sessionId']);
});

test('indexing session overview with non-existent session returns error', function () {
    postJson(action([AssetIndexesController::class, 'indexingSessionOverview']), [
        'sessionId' => 999,
    ])->assertBadRequest()
        ->assertJson(['message' => 'Cannot find the indexing session, or there’s nothing to review.']);
});

test('finish indexing session requires sessionId parameter', function () {
    postJson(action([AssetIndexesController::class, 'finishIndexingSession']), [])
        ->assertJsonValidationErrors(['sessionId']);
});

test('finish indexing session returns stop response', function () {
    postJson(action([AssetIndexesController::class, 'finishIndexingSession']), [
        'sessionId' => 999,
    ])
        ->assertOk()
        ->assertJson(['stop' => 999]);
});

test('finish indexing session deletes the reviewed folders and assets', function (bool $listEmptyFolders) {
    config()->set('filesystems.disks.asset-indexes', [
        'driver' => 'local',
        'root' => storage_path('framework/testing/asset-indexes'),
    ]);
    Storage::fake('asset-indexes');
    Storage::disk('asset-indexes')->put('missing/.keep', '');
    Storage::disk('asset-indexes')->put('photo.jpg', 'photo');

    $volume = Volume::factory()->create(['fs' => 'asset-indexes']);
    $rootFolder = VolumeFolder::factory()->create(['volumeId' => $volume->id, 'path' => '']);
    $folder = VolumeFolder::factory()->create([
        'volumeId' => $volume->id,
        'parentId' => $rootFolder->id,
        'name' => 'missing',
        'path' => 'missing/',
    ]);
    $asset = AssetModel::factory()->createElement([
        'volumeId' => $volume->id,
        'folderId' => $rootFolder->id,
        'filename' => 'photo.jpg',
    ]);

    $session = AssetIndexer::createIndexingSession(
        [Volumes::getVolumeById($volume->id)],
        listEmptyFolders: $listEmptyFolders,
    );

    postJson(action([AssetIndexesController::class, 'finishIndexingSession']), [
        'sessionId' => $session->id,
        'deleteFolder' => [$folder->id],
        'deleteAsset' => [$asset->id],
    ])
        ->assertOk()
        ->assertJson(['stop' => $session->id]);

    expect(AssetIndexingSession::find($session->id))->toBeNull()
        ->and(VolumeFolder::find($folder->id))->toBeNull()
        ->and(Asset::find()->id($asset->id)->status(null)->exists())->toBeFalse()
        ->and(Storage::disk('asset-indexes')->exists('missing'))->toBe(! $listEmptyFolders);

    Storage::disk('asset-indexes')->assertExists('photo.jpg');
})->with([
    'listing empty folders' => true,
    'not listing empty folders' => false,
]);
