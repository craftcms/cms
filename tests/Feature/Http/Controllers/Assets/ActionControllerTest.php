<?php

declare(strict_types=1);

use CraftCms\Cms\Asset\Elements\Asset;
use CraftCms\Cms\Asset\Models\Asset as AssetModel;
use CraftCms\Cms\Asset\Models\Volume;
use CraftCms\Cms\Asset\Models\VolumeFolder as VolumeFolderModel;
use CraftCms\Cms\Http\Controllers\Assets\ActionController;
use CraftCms\Cms\User\Elements\User;
use CraftCms\Cms\User\Models\User as UserModel;
use CraftCms\Cms\User\UserPermissions;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\postJson;

beforeEach(function () {
    actingAs(User::findOne());

    config()->set('filesystems.disks.test-disk', [
        'driver' => 'local',
        'root' => storage_path('framework/testing/action-controller-test/test-disk'),
    ]);

    $this->volume = Volume::factory()->create(['fs' => 'test-disk']);
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

    it('replaces a conflicting asset when forced', function () {
        $user = UserModel::factory()->create(['admin' => false]);
        actingAs($user);

        $asset = AssetModel::factory()->createElement([
            'volumeId' => $this->volume->id,
            'folderId' => $this->folder->id,
            'filename' => 'conflict.jpg',
            'uploaderId' => $user->id,
        ]);

        $targetVolume = Volume::factory()->create(['fs' => 'test-disk']);
        $targetFolder = VolumeFolderModel::factory()->create(['volumeId' => $targetVolume->id]);
        $conflictingAsset = AssetModel::factory()->createElement([
            'volumeId' => $targetVolume->id,
            'folderId' => $targetFolder->id,
            'filename' => 'conflict.jpg',
            'uploaderId' => $user->id,
        ]);

        app(UserPermissions::class)->saveUserPermissions($user->id, [
            'accessCp',
            "viewAssets:{$this->volume->uid}",
            "saveAssets:{$this->volume->uid}",
            "deleteAssets:{$this->volume->uid}",
            "viewAssets:{$targetVolume->uid}",
            "saveAssets:{$targetVolume->uid}",
            "deleteAssets:{$targetVolume->uid}",
        ]);

        postJson(action([ActionController::class, 'moveAsset']), [
            'assetId' => $asset->id,
            'folderId' => $targetFolder->id,
            'force' => true,
        ])->assertOk()->assertJsonMissingPath('conflict');

        expect(Asset::find()->id($asset->id)->one()->folderId)->toBe($targetFolder->id)
            ->and(Asset::find()->id($conflictingAsset->id)->one())->toBeNull();
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

    it('rejects a non-integer asset id', function () {
        postJson(action([ActionController::class, 'showInFolder']), [
            'assetId' => ['filename' => 'findme.jpg'],
        ])->assertJsonValidationErrors(['assetId']);
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
