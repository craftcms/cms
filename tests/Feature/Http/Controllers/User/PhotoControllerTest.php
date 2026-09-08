<?php

declare(strict_types=1);

use CraftCms\Cms\Asset\AssetUploads;
use CraftCms\Cms\Asset\Elements\Asset;
use CraftCms\Cms\Asset\Models\Volume;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Edition;
use CraftCms\Cms\Filesystem\Models\UploadSession;
use CraftCms\Cms\Http\Controllers\Users\PhotoController;
use CraftCms\Cms\Support\Facades\ProjectConfig;
use CraftCms\Cms\User\Elements\User;
use CraftCms\Cms\User\Models\User as UserModel;
use CraftCms\Cms\User\UserPhotoUploads;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\TestResponse;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\postJson;

beforeEach(function () {
    actingAs(User::findOne());
    config()->set('filesystems.disks.photo-parts', ['driver' => 'local', 'root' => storage_path('framework/testing/photo-parts')]);
    Storage::fake('photo-parts');
    Cms::config()->tempAssetUploadFs = 'disk:photo-parts';
    Cms::config()->uploadChunkSize = 512;

    $this->uploadPhoto = function (UploadedFile $photo): TestResponse {
        $session = postJson(action([PhotoController::class, 'upload']), [
            'userId' => auth()->id(),
            'filename' => $photo->getClientOriginalName(),
            'size' => $photo->getSize(),
        ])->assertCreated()->json();

        foreach (str_split($photo->getContent(), $session['chunkSize']) as $index => $bytes) {
            $part = postJson($session['urls']['part'], ['part' => $index + 1])->assertOk()->json();
            $this->call('POST', $part['url'], server: ['CONTENT_TYPE' => 'application/octet-stream'], content: $bytes)->assertNoContent();
        }

        $response = postJson($session['urls']['complete']);
        if ($response->isSuccessful()) {
            postJson($session['urls']['complete'])->assertExactJson($response->json());
        }

        return $response;
    };
});

it('requires login', function () {
    auth()->logout();

    postJson(action([PhotoController::class, 'renderInput']))->assertUnauthorized();
    postJson(action([PhotoController::class, 'upload']))->assertUnauthorized();
    postJson(action([PhotoController::class, 'destroy']))->assertUnauthorized();
});

test('userId is required', function () {
    postJson(action([PhotoController::class, 'renderInput']))->assertJsonValidationErrorFor('userId');
    postJson(action([PhotoController::class, 'upload']), ['filename' => 'avatar.jpg', 'size' => 3])->assertJsonValidationErrorFor('userId');
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
        'filename' => 'avatar.jpg', 'size' => 3,
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

    $request($targetUser)->assertSuccessful();
})->with([
    'render input' => [fn (User $targetUser) => postJson(action([PhotoController::class, 'renderInput']), [
        'userId' => $targetUser->id,
    ])],
    'upload' => [fn (User $targetUser) => postJson(action([PhotoController::class, 'upload']), [
        'filename' => 'avatar.jpg', 'size' => 3,
        'userId' => $targetUser->id,
    ])],
    'destroy' => [fn (User $targetUser) => postJson(action([PhotoController::class, 'destroy']), [
        'userId' => $targetUser->id,
    ])],
]);

test('uploads and replaces a user photo through sessions', function () {
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

    ($this->uploadPhoto)(UploadedFile::fake()->image('avatar.jpg'))->assertOk()->assertJsonStructure([
        'html',
        'photoId',
        'headerPhotoHtml',
    ]);

    $photoId = User::findOne(auth()->id())->photoId;
    expect($photoId)->not->toBeNull();

    ($this->uploadPhoto)(UploadedFile::fake()->image('replacement.jpg', 20, 20))
        ->assertOk()->assertJsonPath('photoId', $photoId);

    expect(User::findOne(auth()->id())->photoId)->toBe($photoId)
        ->and(Asset::find()->volumeId($volume->id)->count())->toBe(1);
    Storage::disk('photo-parts')->assertDirectoryEmpty('upload-sessions');
});

test('upload rejects photos larger than the configured upload limit', function () {
    Cms::config()->maxUploadFileSize = 10;

    postJson(action([PhotoController::class, 'upload']), [
        'userId' => auth()->id(),
        'filename' => 'avatar.jpg', 'size' => 11,
    ])->assertUnprocessable();

    expect(UploadSession::count())->toBe(0);
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

    ($this->uploadPhoto)(UploadedFile::fake()->image('avatar.jpg'))->assertOk();

    $photoId = User::findOne(auth()->id())->photoId;

    postJson(action([PhotoController::class, 'destroy']), [
        'userId' => auth()->id(),
    ])->assertOk()->assertJson([
        'photoId' => null,
    ]);

    expect(User::findOne(auth()->id())->photoId)->toBeNull()
        ->and(Asset::find()->id($photoId)->status(null)->one())->toBeNull();
});

it('does not grant photo uploads through the asset guest authorizer', function () {
    auth()->logout();
    app(AssetUploads::class)->allowGuestUploadsUsing(fn () => true);

    postJson(action([PhotoController::class, 'upload']), [
        'userId' => 1, 'filename' => 'avatar.jpg', 'size' => 3,
    ])->assertUnauthorized();

    expect(UploadSession::count())->toBe(0);
});

it('binds photo sessions to the server-selected handler and user', function () {
    $session = postJson(action([PhotoController::class, 'upload']), [
        'userId' => (string) auth()->id(), 'filename' => 'avatar.jpg', 'size' => 3,
        'handler' => AssetUploads::class, 'folderId' => 999,
    ])->assertCreated()->json();

    $stored = UploadSession::findOrFail($session['id']);
    expect($stored->handler)->toBe(UserPhotoUploads::class)
        ->and($stored->parameters)->toBe(['userId' => auth()->id()]);

    Gate::partialMock()->shouldReceive('authorize')->andThrow(new AuthorizationException);

    postJson($session['urls']['part'], ['part' => 1])->assertForbidden();
    postJson($session['urls']['complete'], ['userId' => 999])->assertForbidden();
    $this->deleteJson($session['urls']['cancel'])->assertNoContent();

    expect(UploadSession::count())->toBe(0);
    Storage::disk('photo-parts')->assertDirectoryEmpty('upload-sessions');
});

it('rejects non-image photo names before accepting bytes', function () {
    postJson(action([PhotoController::class, 'upload']), [
        'userId' => auth()->id(), 'filename' => 'document.txt', 'size' => 3,
    ])->assertUnprocessable();

    expect(UploadSession::count())->toBe(0);
});

it('rejects invalid image contents without changing the users photo', function () {
    if (DB::isMysql()) {
        $this->markTestSkipped('Bulk ops cause issues with MySQL');
    }

    config()->set('filesystems.disks.invalid-photo', [
        'driver' => 'local',
        'root' => storage_path('framework/testing/invalid-photo'),
    ]);
    $volume = Volume::factory()->create(['fs' => 'disk:invalid-photo']);
    ProjectConfig::set('users.photoVolumeUid', $volume->uid);
    $photoId = User::findOne(auth()->id())->photoId;

    ($this->uploadPhoto)(UploadedFile::fake()->createWithContent('avatar.jpg', 'not an image'))
        ->assertUnprocessable();

    expect(User::findOne(auth()->id())->photoId)->toBe($photoId)
        ->and(Asset::find()->volumeId($volume->id)->count())->toBe(0);
});
