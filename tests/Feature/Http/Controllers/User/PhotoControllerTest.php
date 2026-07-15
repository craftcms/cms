<?php

use CraftCms\Cms\Asset\Elements\Asset;
use CraftCms\Cms\Asset\Models\Volume;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Edition;
use CraftCms\Cms\Http\Controllers\Users\PhotoController;
use CraftCms\Cms\Support\Facades\ProjectConfig;
use CraftCms\Cms\User\Elements\User;
use CraftCms\Cms\User\Models\User as UserModel;
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

test('users without editUsers cannot manage another users photo', function (Closure $request) {
    Edition::set(Edition::Pro);

    $currentUser = UserModel::factory()
        ->withPermissions(['accessCp'])
        ->createElement();
    $targetUser = UserModel::factory()->createElement();

    actingAs($currentUser);

    $request($targetUser)->assertForbidden();
})->with([
    'render input' => [fn (User $targetUser) => postJson(action([PhotoController::class, 'renderInput']), [
        'userId' => $targetUser->id,
    ])],
    'upload' => [fn (User $targetUser) => postJson(action([PhotoController::class, 'upload']), [
        'userId' => $targetUser->id,
    ])],
    'destroy' => [fn (User $targetUser) => postJson(action([PhotoController::class, 'destroy']), [
        'userId' => $targetUser->id,
    ])],
]);

test('users with editUsers can manage another users photo', function (Closure $request) {
    Edition::set(Edition::Pro);

    $currentUser = UserModel::factory()
        ->withPermissions(['accessCp', 'viewUsers', 'editUsers'])
        ->createElement();
    $targetUser = UserModel::factory()->createElement();

    actingAs($currentUser);

    $request($targetUser)->assertOk();
})->with([
    'render input' => [fn (User $targetUser) => postJson(action([PhotoController::class, 'renderInput']), [
        'userId' => $targetUser->id,
    ])],
    'upload' => [fn (User $targetUser) => postJson(action([PhotoController::class, 'upload']), [
        'userId' => $targetUser->id,
    ])],
    'destroy' => [fn (User $targetUser) => postJson(action([PhotoController::class, 'destroy']), [
        'userId' => $targetUser->id,
    ])],
]);

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

test('upload rejects photos larger than the configured upload limit', function () {
    Cms::config()->maxUploadFileSize = 10;

    postJson(action([PhotoController::class, 'upload']), [
        'userId' => auth()->id(),
        'photo' => UploadedFile::fake()->createWithContent('avatar.jpg', str_repeat('a', 11)),
    ])->assertJsonValidationErrorFor('photo');
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
