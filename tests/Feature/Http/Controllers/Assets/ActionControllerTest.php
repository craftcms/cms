<?php

declare(strict_types=1);

use CraftCms\Cms\Asset\Models\Asset as AssetModel;
use CraftCms\Cms\Asset\Models\Volume;
use CraftCms\Cms\Asset\Models\VolumeFolder as VolumeFolderModel;
use CraftCms\Cms\Http\Controllers\Assets\ActionController;
use CraftCms\Cms\User\Elements\User;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\postJson;

beforeEach(function () {
    actingAs(User::findOne());

    config()->set('filesystems.disks.test-disk', [
        'driver' => 'local',
        'root' => storage_path('framework/testing/action-controller-test/test-disk'),
    ]);

    $this->volume = Volume::factory()->create(['fs' => 'disk:test-disk']);
    $this->folder = VolumeFolderModel::factory()->create(['volumeId' => $this->volume->id]);
});

describe('deleteAsset', function () {
    it('requires authentication', function () {
        auth()->logout();

        postJson(action([ActionController::class, 'deleteAsset']), [
            'assetId' => 1,
        ])->assertUnauthorized();
    });

    it('can delete an asset', function () {
        $asset = AssetModel::factory()->createElement([
            'volumeId' => $this->volume->id,
            'folderId' => $this->folder->id,
        ]);

        postJson(action([ActionController::class, 'deleteAsset']), [
            'assetId' => $asset->id,
        ])->assertOk();
    });

    it('returns 400 for missing asset id', function () {
        postJson(action([ActionController::class, 'deleteAsset']))->assertBadRequest();
    });
});

describe('moveAsset', function () {
    it('requires authentication', function () {
        auth()->logout();

        postJson(action([ActionController::class, 'moveAsset']), [
            'assetId' => 1,
        ])->assertUnauthorized();
    });

    it('validates move asset input', function () {
        postJson(action([ActionController::class, 'moveAsset']))
            ->assertJsonValidationErrors(['assetId']);
    });
});

describe('downloadAsset', function () {
    it('requires authentication', function () {
        auth()->logout();

        postJson(action([ActionController::class, 'downloadAsset']), [
            'assetId' => 1,
        ])->assertUnauthorized();
    });

    it('validates download asset input', function () {
        postJson(action([ActionController::class, 'downloadAsset']))
            ->assertJsonValidationErrors(['assetId']);
    });
});

describe('showInFolder', function () {
    it('requires authentication', function () {
        auth()->logout();

        postJson(action([ActionController::class, 'showInFolder']), [
            'assetId' => 1,
        ])->assertUnauthorized();
    });

    it('validates show in folder input', function () {
        postJson(action([ActionController::class, 'showInFolder']))
            ->assertJsonValidationErrors(['assetId']);
    });

    it('can show asset in folder', function () {
        $asset = AssetModel::factory()->createElement([
            'volumeId' => $this->volume->id,
            'folderId' => $this->folder->id,
            'filename' => 'findme.jpg',
        ]);

        postJson(action([ActionController::class, 'showInFolder']), [
            'assetId' => $asset->id,
        ])
            ->assertOk()
            ->assertJsonStructure(['filename', 'sourcePath'])
            ->assertJsonPath('filename', 'findme.jpg');
    });
});

describe('moveInfo', function () {
    it('requires authentication', function () {
        auth()->logout();

        postJson(action([ActionController::class, 'moveInfo']))
            ->assertUnauthorized();
    });

    it('can get move info', function () {
        $asset = AssetModel::factory()->create([
            'volumeId' => $this->volume->id,
            'folderId' => $this->folder->id,
        ]);

        postJson(action([ActionController::class, 'moveInfo']), [
            'assetIds' => [$asset->id],
        ])
            ->assertOk()
            ->assertJsonStructure(['count', 'totalSize']);
    });

    it('returns count and total size for folder ids', function () {
        AssetModel::factory()->count(3)->create([
            'volumeId' => $this->volume->id,
            'folderId' => $this->folder->id,
        ]);

        postJson(action([ActionController::class, 'moveInfo']), [
            'folderIds' => [$this->folder->id],
        ])
            ->assertOk()
            ->assertJsonPath('count', 3);
    });
});
