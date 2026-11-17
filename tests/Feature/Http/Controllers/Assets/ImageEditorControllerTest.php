<?php

declare(strict_types=1);

use CraftCms\Cms\Asset\Elements\Asset;
use CraftCms\Cms\Asset\Models\Asset as AssetModel;
use CraftCms\Cms\Asset\Models\Volume;
use CraftCms\Cms\Asset\Models\VolumeFolder as VolumeFolderModel;
use CraftCms\Cms\Http\Controllers\Assets\ImageEditorController;
use CraftCms\Cms\User\Elements\User;
use Illuminate\Support\Facades\Storage;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;
use function Pest\Laravel\postJson;

beforeEach(function () {
    actingAs(User::findOne());

    config()->set('filesystems.disks.test-disk', [
        'driver' => 'local',
        'root' => storage_path('framework/testing/image-editor-test/test-disk'),
    ]);

    $this->volume = Volume::factory()->create(['fs' => 'test-disk']);
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
            ->assertJson([
                'assetId' => $asset->id,
                'filename' => 'editor-test.jpg',
            ])
            ->assertJsonStructure(['focalPoint', 'imageWidth', 'imageHeight', 'imageEditorRatios', 'allowDegreeFractions', 'orientation']);
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

    it('clamps crops to the image bounds', function () {
        // 460×460, shown at 100×100 in the editor
        $asset = AssetModel::factory()->createElement([
            'volumeId' => $this->volume->id,
            'folderId' => $this->folder->id,
            'filename' => 'crop-test.png',
            'kind' => 'image',
            'width' => 460,
            'height' => 460,
        ]);
        $image = imagecreatetruecolor(460, 460);
        ob_start();
        imagepng($image);
        Storage::disk('test-disk')->put($asset->getPath(), ob_get_clean());

        $newAssetId = postJson(action([ImageEditorController::class, 'save']), [
            'assetId' => $asset->id,
            'viewportRotation' => 0,
            'imageRotation' => 0,
            'replace' => false,
            // The left half of the image, with the crop box 0.5px past its left edge (2.3px at full size)
            'cropData' => ['offsetX' => -25.5, 'offsetY' => 0, 'width' => 50, 'height' => 100],
            'imageDimensions' => ['width' => 100, 'height' => 100],
        ])->assertOk()->json('newAssetId');

        // The crop keeps its right edge (49.5px on screen = 227.7px at full size) and starts at the image's left edge
        $newAsset = Asset::find()->id($newAssetId)->one();

        expect($newAsset->width)->toBe(227)
            ->and($newAsset->height)->toBe(460);
    })->issue(20601);
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
