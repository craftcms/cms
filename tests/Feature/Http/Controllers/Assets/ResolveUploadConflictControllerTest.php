<?php

declare(strict_types=1);

use CraftCms\Cms\Asset\Elements\Asset;
use CraftCms\Cms\Asset\Models\Asset as AssetModel;
use CraftCms\Cms\Asset\Models\Volume;
use CraftCms\Cms\Asset\Models\VolumeFolder;
use CraftCms\Cms\Http\Controllers\Assets\ResolveUploadConflictController;
use CraftCms\Cms\User\Elements\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\Access\Response;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\postJson;

beforeEach(function () {
    actingAs(User::findOne());
    config()->set('filesystems.disks.upload-conflicts', [
        'driver' => 'local',
        'root' => storage_path('framework/testing/upload-conflicts'),
    ]);
    Storage::fake('upload-conflicts');
    $volume = Volume::factory()->create(['fs' => 'disk:upload-conflicts']);
    $this->folder = VolumeFolder::factory()->create(['volumeId' => $volume->id, 'path' => '']);
    $this->source = AssetModel::factory()->createElement([
        'volumeId' => $volume->id,
        'folderId' => $this->folder->id,
        'filename' => 'document_1.txt',
        'kind' => 'text',
        'size' => 11,
    ]);
    Storage::disk('upload-conflicts')->put('document_1.txt', 'replacement');
    Storage::disk('upload-conflicts')->put('document.txt', 'original');
});

it('requires authentication', function () {
    auth()->logout();

    postJson(action(ResolveUploadConflictController::class))->assertUnauthorized();
});

it('requires an existing source asset and a replacement target', function (array $parameters) {
    postJson(action(ResolveUploadConflictController::class), $parameters)->assertUnprocessable();

    expect(Storage::disk('upload-conflicts')->get('document.txt'))->toBe('original');
})->with([
    'missing source' => [['targetFilename' => 'document.txt']],
    'missing target' => [['sourceAssetId' => '1']],
]);

it('resolves a conflict using an already uploaded asset', function (string $target) {
    $parameters = ['sourceAssetId' => (string) $this->source->id];
    $expectedId = $this->source->id;

    if ($target !== 'unindexed file') {
        $asset = AssetModel::factory()->createElement([
            'volumeId' => $this->source->volumeId,
            'folderId' => $this->folder->id,
            'filename' => 'document.txt',
            'kind' => 'text',
        ]);
        $expectedId = $asset->id;
    }

    if ($target === 'asset ID') {
        $parameters['assetId'] = (string) $expectedId;
    } else {
        $parameters['targetFilename'] = 'document.txt';
    }

    postJson(action(ResolveUploadConflictController::class), $parameters)
        ->assertOk()->assertJsonPath('assetId', $expectedId)->assertJsonPath('filename', 'document.txt');

    expect(Storage::disk('upload-conflicts')->get('document.txt'))->toBe('replacement')
        ->and(Asset::find()->folderId($this->folder->id)->count())->toBe(1);
    Storage::disk('upload-conflicts')->assertMissing('document_1.txt');
})->with(['asset ID', 'asset filename', 'unindexed file']);

it('checks replacement permissions for both assets', function (string $deniedAsset) {
    $target = AssetModel::factory()->createElement([
        'volumeId' => $this->source->volumeId,
        'folderId' => $this->folder->id,
        'filename' => 'document.txt',
        'kind' => 'text',
    ]);
    $deniedId = $deniedAsset === 'source' ? $this->source->id : $target->id;
    Gate::partialMock()->shouldReceive('authorize')->andReturnUsing(function (string $ability, mixed $asset = null) use ($deniedId): Response {
        if ($ability === 'replaceFile' && $asset->id === $deniedId) {
            throw new AuthorizationException;
        }

        return Response::allow();
    });

    postJson(action(ResolveUploadConflictController::class), [
        'sourceAssetId' => $this->source->id,
        'assetId' => $target->id,
    ])->assertForbidden();

    expect(Storage::disk('upload-conflicts')->get('document.txt'))->toBe('original')
        ->and(Storage::disk('upload-conflicts')->get('document_1.txt'))->toBe('replacement')
        ->and(Asset::find()->folderId($this->folder->id)->count())->toBe(2);
})->with(['source', 'target']);
