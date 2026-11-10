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
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Uri;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\post;
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

    it('redirects to a temporary URL when the disk provides them', function () {
        config()->set('filesystems.disks.test-s3', [
            'driver' => 's3',
            'key' => 'test-key',
            'secret' => 'test-secret',
            'region' => 'us-east-1',
            'bucket' => 'test-bucket',
        ]);
        $volume = Volume::factory()->create(['fs' => 'test-s3', 'subpath' => 'volume-subpath']);
        $folder = VolumeFolderModel::factory()->create(['volumeId' => $volume->id]);
        $asset = AssetModel::factory()->createElement([
            'volumeId' => $volume->id,
            'folderId' => $folder->id,
            'filename' => 'résumé.pdf',
        ]);

        $response = post(action([ActionController::class, 'downloadAsset']), [
            'assetId' => $asset->id,
        ])->assertRedirect();

        expect($response->getStatusCode())->toBe(303)
            ->and($response->headers->get('Cache-Control'))->toContain('no-store');

        $location = Uri::of($response->headers->get('Location'));
        expect($location->host())->toBe('test-bucket.s3.amazonaws.com')
            ->and(rawurldecode($location->path()))->toBe('volume-subpath/'.$asset->getPath())
            ->and($location->query()->get('response-content-disposition'))
            ->toBe("attachment; filename=resume.pdf; filename*=utf-8''r%C3%A9sum%C3%A9.pdf")
            ->and($location->query()->get('X-Amz-Signature'))->not->toBeEmpty();
    });

    it('streams the file when the disk does not provide temporary URLs', function () {
        $asset = AssetModel::factory()->createElement([
            'volumeId' => $this->volume->id,
            'folderId' => $this->folder->id,
            'filename' => 'local.txt',
        ]);
        Storage::disk('test-disk')->put($asset->getPath(), 'local contents');

        $response = post(action([ActionController::class, 'downloadAsset']), [
            'assetId' => $asset->id,
        ])->assertOk()->assertDownload('local.txt');

        expect($response->streamedContent())->toBe('local contents');
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
