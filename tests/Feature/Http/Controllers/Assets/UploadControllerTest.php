<?php

declare(strict_types=1);

use CraftCms\Cms\Asset\Models\Volume;
use CraftCms\Cms\Asset\Models\VolumeFolder as VolumeFolderModel;
use CraftCms\Cms\Entry\Models\Entry as EntryModel;
use CraftCms\Cms\Field\Assets as AssetsField;
use CraftCms\Cms\Http\Controllers\Assets\UploadController;
use CraftCms\Cms\User\Elements\User;
use Illuminate\Http\UploadedFile;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\post;
use function Pest\Laravel\postJson;

beforeEach(function () {
    actingAs(User::findOne());

    config()->set('filesystems.disks.test-disk', [
        'driver' => 'local',
        'root' => storage_path('framework/testing/upload-controller-test/test-disk'),
    ]);

    $this->volume = Volume::factory()->create(['fs' => 'disk:test-disk']);
    $this->folder = VolumeFolderModel::factory()->create(['volumeId' => $this->volume->id]);
});

it('requires authentication', function () {
    auth()->logout();

    postJson(action([UploadController::class, 'upload']))
        ->assertUnauthorized();
});

it('requires a file or field for upload', function () {
    postJson(action([UploadController::class, 'upload']), [
        'folderId' => $this->folder->id,
    ])->assertBadRequest();
});

it('uploads to a dynamic field location using posted element context', function () {
    $result = EntryModel::factory()
        ->withField('coverImage', AssetsField::class, [
            'defaultUploadLocationSource' => "volume:{$this->volume->uid}",
            'defaultUploadLocationSubpath' => '{uid}',
        ])
        ->createElementWithFields(['title' => 'Hub Resource']);

    $entry = $result->element;
    $field = $result->fields->get('coverImage');

    post(action([UploadController::class, 'upload']), [
        'fieldId' => (string) $field->id,
        'elementId' => (string) $entry->id,
        'siteId' => (string) $entry->siteId,
        'assets-upload' => UploadedFile::fake()->image('cover.jpg'),
    ], [
        'Accept' => 'application/json',
    ])
        ->assertOk()
        ->assertJson([
            'filename' => 'cover.jpg',
        ]);

    expect($this->folder->newQuery()
        ->where('volumeId', $this->volume->id)
        ->where('path', "{$entry->uid}/")
        ->exists())->toBeTrue();
});

it('requires authentication for replace file', function () {
    auth()->logout();

    postJson(action([UploadController::class, 'replaceFile']))
        ->assertUnauthorized();
});

it('validates replace file parameters', function () {
    postJson(action([UploadController::class, 'replaceFile']))->assertBadRequest();
});

it('casts posted asset IDs before looking them up', function (Closure $requestData) {
    post(action([UploadController::class, 'replaceFile']), $requestData(), [
        'Accept' => 'application/json',
    ])->assertNotFound();
})->with([
    'target asset' => [fn () => [
        'assetId' => '999999',
        'replaceFile' => UploadedFile::fake()->image('replacement.jpg'),
    ]],
    'source asset' => [fn () => [
        'sourceAssetId' => '999999',
        'targetFilename' => 'replacement.jpg',
    ]],
]);
