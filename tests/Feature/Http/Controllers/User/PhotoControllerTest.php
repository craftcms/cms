<?php

use CraftCms\Cms\Asset\Elements\Asset;
use CraftCms\Cms\Asset\Models\Volume;
use CraftCms\Cms\Http\Controllers\Users\PhotoController;
use CraftCms\Cms\Support\Facades\ProjectConfig;
use CraftCms\Cms\User\Elements\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\post;
use function Pest\Laravel\postJson;

beforeEach(function () {
    actingAs(User::findOne());
});

it('requires login', function () {
    auth()->logout();

    postJson(action([PhotoController::class, 'renderInput']))->assertUnauthorized();
    postJson(action([PhotoController::class, 'upload']))->assertUnauthorized();
    postJson(action([PhotoController::class, 'destroy']))->assertUnauthorized();
});

test('userId is required', function () {
    postJson(action([PhotoController::class, 'renderInput']))->assertJsonValidationErrorFor('userId');
    postJson(action([PhotoController::class, 'upload']))->assertJsonValidationErrorFor('userId');
    postJson(action([PhotoController::class, 'destroy']))->assertJsonValidationErrorFor('userId');
});

test('renderInput', function () {
    postJson(action([PhotoController::class, 'renderInput'], [
        'userId' => auth()->id(),
    ]))->assertJsonStructure([
        'html',
        'photoId',
        'headerPhotoHtml',
    ]);
});

test('upload', function () {
    if (DB::isMysql()) {
        $this->markTestSkipped('Bulk ops cause issues with MySQL');
    }

    config()->set('filesystems.disks.test-disk', [
        'driver' => 'local',
        'root' => storage_path('framework/testing/user-photo-test/test-disk'),
    ]);

    $volume = Volume::factory()->create([
        'fs' => 'disk:test-disk',
    ]);

    ProjectConfig::set('users.photoVolumeUid', $volume->uid);

    post(action([PhotoController::class, 'upload']), [
        'userId' => auth()->id(),
        'photo' => UploadedFile::fake()->image('avatar.jpg'),
    ], ['Accept' => 'application/json'])->assertOk()->assertJsonStructure([
        'html',
        'photoId',
        'headerPhotoHtml',
    ]);

    expect(User::findOne(auth()->id())->getPhoto())->not->toBeNull();
});

test('destroy', function () {
    if (DB::isMysql()) {
        $this->markTestSkipped('Bulk ops cause issues with MySQL');
    }

    config()->set('filesystems.disks.test-disk', [
        'driver' => 'local',
        'root' => storage_path('framework/testing/user-photo-test/test-disk'),
    ]);

    $volume = Volume::factory()->create([
        'fs' => 'disk:test-disk',
    ]);

    ProjectConfig::set('users.photoVolumeUid', $volume->uid);

    post(action([PhotoController::class, 'upload']), [
        'userId' => auth()->id(),
        'photo' => UploadedFile::fake()->image('avatar.jpg'),
    ], ['Accept' => 'application/json']);

    $photoId = User::findOne(auth()->id())->photoId;

    postJson(action([PhotoController::class, 'destroy']), [
        'userId' => auth()->id(),
    ])->assertOk()->assertJson([
        'photoId' => null,
    ]);

    expect(User::findOne(auth()->id())->photoId)->toBeNull()
        ->and(Asset::find()->id($photoId)->status(null)->one())->toBeNull();
});
