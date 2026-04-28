<?php

declare(strict_types=1);

use CraftCms\Cms\Asset\Models\Asset as AssetModel;
use CraftCms\Cms\Asset\Models\Volume;
use CraftCms\Cms\Asset\Models\VolumeFolder as VolumeFolderModel;
use CraftCms\Cms\Http\Controllers\Assets\ImageEditorController;
use CraftCms\Cms\User\Elements\User;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;
use function Pest\Laravel\postJson;

beforeEach(function () {
    actingAs(User::findOne());

    config()->set('filesystems.disks.test-disk', [
        'driver' => 'local',
        'root' => storage_path('framework/testing/image-editor-test/test-disk'),
    ]);

    $this->volume = Volume::factory()->create(['fs' => 'disk:test-disk']);
    $this->folder = VolumeFolderModel::factory()->create(['volumeId' => $this->volume->id]);
});

describe('show', function () {
    it('requires authentication', function () {
        auth()->logout();

        postJson(action([ImageEditorController::class, 'show']), [
            'assetId' => 1,
        ])->assertUnauthorized();
    });

    it('validates input', function () {
        postJson(action([ImageEditorController::class, 'show']))
            ->assertJsonValidationErrors(['assetId']);
    });

    it('can show the image editor', function () {
        $asset = AssetModel::factory()->createElement([
            'volumeId' => $this->volume->id,
            'folderId' => $this->folder->id,
            'filename' => 'editor-test.jpg',
            'kind' => 'image',
        ]);

        postJson(action([ImageEditorController::class, 'show']), [
            'assetId' => $asset->id,
        ])
            ->assertOk()
            ->assertJsonStructure(['html']);
    });
});

describe('editImage', function () {
    it('requires authentication', function () {
        auth()->logout();

        get(action([ImageEditorController::class, 'editImage'], [
            'assetId' => 1,
            'size' => 500,
        ]))->assertRedirect();
    });
});

describe('save', function () {
    it('requires authentication', function () {
        auth()->logout();

        postJson(action([ImageEditorController::class, 'save']), [
            'assetId' => 1,
        ])->assertUnauthorized();
    });

    it('validates input', function () {
        postJson(action([ImageEditorController::class, 'save']))
            ->assertJsonValidationErrors(['assetId', 'viewportRotation', 'imageRotation', 'replace', 'cropData']);
    });
});

describe('updateFocalPoint', function () {
    it('requires authentication', function () {
        auth()->logout();

        postJson(action([ImageEditorController::class, 'updateFocalPoint']), [
            'assetUid' => 'test-uid',
        ])->assertUnauthorized();
    });

    it('validates input', function () {
        postJson(action([ImageEditorController::class, 'updateFocalPoint']))
            ->assertJsonValidationErrors(['assetUid', 'focal', 'focalEnabled']);
    });

    it('can update focal point', function () {
        $asset = AssetModel::factory()->createElement([
            'volumeId' => $this->volume->id,
            'folderId' => $this->folder->id,
            'filename' => 'focal-test.jpg',
            'kind' => 'image',
        ]);

        postJson(action([ImageEditorController::class, 'updateFocalPoint']), [
            'assetUid' => $asset->uid,
            'focal' => ['x' => 0.5, 'y' => 0.5],
            'focalEnabled' => true,
        ])->assertOk();
    });
});
