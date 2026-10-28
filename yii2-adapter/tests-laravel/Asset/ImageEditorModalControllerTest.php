<?php

declare(strict_types=1);

use CraftCms\Cms\Asset\Models\Asset;
use CraftCms\Cms\Asset\Models\Volume;
use CraftCms\Cms\Asset\Models\VolumeFolder;
use CraftCms\Cms\User\Elements\User;
use CraftCms\Yii2Adapter\Http\ImageEditorModalController;
use CraftCms\Yii2Adapter\Tests\DatabaseTestCase;

uses(DatabaseTestCase::class);

beforeEach(function(): void {
    $this->actingAs(User::findOne());

    config()->set('filesystems.disks.image-editor-modal-test', [
        'driver' => 'local',
        'root' => storage_path('framework/testing/image-editor-modal-test'),
    ]);

    $this->volume = Volume::factory()->create(['fs' => 'image-editor-modal-test']);
    $this->folder = VolumeFolder::factory()->create(['volumeId' => $this->volume->id]);
});

it('requires authentication', function(): void {
    auth()->logout();

    $this->postJson(action(ImageEditorModalController::class), [
        'assetId' => 1,
    ])->assertUnauthorized();
});

it('validates input', function(): void {
    $this->postJson(action(ImageEditorModalController::class))
        ->assertJsonValidationErrors(['assetId']);
});

it('returns the image editor markup', function(): void {
    $asset = Asset::factory()->createElement([
        'volumeId' => $this->volume->id,
        'folderId' => $this->folder->id,
        'filename' => 'editor-test.jpg',
        'kind' => 'image',
    ]);

    $this->postJson(action(ImageEditorModalController::class), [
        'assetId' => $asset->id,
    ])
        ->assertOk()
        ->assertJsonStructure(['html', 'focalPoint'])
        ->assertJsonPath('html', fn(string $html): bool => str_contains($html, 'Edit Image'));
});
