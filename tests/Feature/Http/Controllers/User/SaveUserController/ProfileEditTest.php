<?php

declare(strict_types=1);

use CraftCms\Cms\Asset\Elements\Asset;
use CraftCms\Cms\Asset\Folders;
use CraftCms\Cms\Asset\Models\Volume;
use CraftCms\Cms\Database\Factories\AssetFactory;
use CraftCms\Cms\Database\Factories\UserFactory;
use CraftCms\Cms\Edition;
use CraftCms\Cms\Filesystem\Exceptions\InvalidSubpathException;
use CraftCms\Cms\Http\Controllers\Elements\ElementSelectorModalController;
use CraftCms\Cms\Http\Controllers\Users\SaveUserController;
use CraftCms\Cms\Support\Facades\ProjectConfig;
use CraftCms\Cms\Support\Facades\Volumes;
use CraftCms\Cms\User\Elements\User;
use CraftCms\Cms\User\Users;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia;

use function CraftCms\Cms\cp_url;
use function CraftCms\Cms\t;
use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;
use function Pest\Laravel\post;
use function Pest\Laravel\postJson;

beforeEach(function () {
    Edition::set(Edition::Team);
});

it('succeeds when user edits their own profile without changing email or password', function () {
    $user = UserFactory::new()->createElement();

    actingAs($user)->post(action(SaveUserController::class), [
        'userId' => $user->id,
        'firstName' => 'Updated',
        'lastName' => 'User',
    ])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    $updatedUser = User::find()->id($user->id)->one();
    expect($updatedUser)->not->toBeNull();
    expect($updatedUser->firstName)->toBe('Updated');
    expect($updatedUser->lastName)->toBe('User');
});

describe('photo selection', function () {
    beforeEach(function () {
        $this->user = User::findOne();
        config()->set('filesystems.disks.profile-photos', [
            'driver' => 'local',
            'root' => storage_path('framework/testing/profile-photos'),
        ]);
        $this->disk = Storage::fake('profile-photos');
        $this->volume = Volume::factory()->create(['fs' => 'disk:profile-photos']);
        ProjectConfig::set('users.photoVolumeUid', $this->volume->uid);
        ProjectConfig::set('users.photoSubpath', 'profiles/{id}');
        $this->folder = app(Users::class)->userPhotoFolder($this->user);
        $this->photo = AssetFactory::new()->createElement([
            'volumeId' => $this->volume->id,
            'folderId' => $this->folder->id,
            'filename' => 'avatar.png',
        ]);
        $this->bytes = UploadedFile::fake()->image('avatar.png', 20, 20)->getContent();
        $this->disk->put($this->photo->getPath(), $this->bytes);
        actingAs($this->user);
    });

    it('saves an asset selected as the user photo without changing the source', function () {
        post(action(SaveUserController::class), [
            'userId' => $this->user->id,
            'photo' => [$this->photo->id],
        ])->assertRedirect()->assertSessionHasNoErrors();

        $saved = User::findOne($this->user->id)->getPhoto();
        expect($saved)->not->toBeNull()
            ->and($saved->id)->not->toBe($this->photo->id)
            ->and($saved->folderId)->toBe($this->folder->id)
            ->and($this->disk->get($this->photo->getPath()))->toBe($this->bytes);

        post(action(SaveUserController::class), [
            'userId' => $this->user->id,
            'photo' => [$saved->id],
        ])->assertRedirect()->assertSessionHasNoErrors();

        expect(User::findOne($this->user->id)->photoId)->toBe($saved->id);

        post(action(SaveUserController::class), [
            'userId' => $this->user->id,
            'photo' => [],
        ])->assertRedirect()->assertSessionHasNoErrors();

        expect(User::findOne($this->user->id)->photoId)->toBeNull()
            ->and(Asset::findOne($this->photo->id))->not->toBeNull()
            ->and($this->disk->get($this->photo->getPath()))->toBe($this->bytes);
    });

    it('rejects assets outside the configured photo folder', function (string $location) {
        $volume = $location === 'other volume'
            ? Volume::factory()->create(['fs' => 'disk:profile-photos'])
            : $this->volume;
        Volumes::reset();
        $path = match ($location) {
            'parent' => 'profiles',
            'sibling' => 'profiles/another-user',
            'child' => $this->folder->path.'child',
            default => $this->folder->path,
        };
        $folder = app(Folders::class)->ensureFolderByFullPathAndVolume($path, Volumes::getVolumeById($volume->id));
        $photo = AssetFactory::new()->createElement(['volumeId' => $volume->id, 'folderId' => $folder->id]);

        post(action(SaveUserController::class), [
            'userId' => $this->user->id,
            'firstName' => 'Should not save',
            'photo' => [$photo->id],
        ])->assertRedirect()->assertSessionHasErrors('photo');

        expect(User::findOne($this->user->id)->photoId)->toBeNull()
            ->and(User::findOne($this->user->id)->firstName)->toBe($this->user->firstName);
    })->with(['parent', 'sibling', 'child', 'other volume']);

    it('rejects invalid photo selections', function (array $selection) {
        post(action(SaveUserController::class), [
            'userId' => $this->user->id,
            'photo' => $selection,
        ])->assertRedirect()->assertSessionHasErrors();

        expect(User::findOne($this->user->id)->photoId)->toBeNull();
    })->with([
        'missing asset' => [[999999]],
        'non-integer' => [['invalid']],
        'multiple assets' => [[1, 2]],
    ]);

    it('saves an empty photo selection when the user has no photo', function () {
        post(action(SaveUserController::class), [
            'userId' => $this->user->id,
            'photo' => [],
        ])->assertRedirect()->assertSessionHasNoErrors();

        expect(User::findOne($this->user->id)->photoId)->toBeNull();
    });

    it('rejects a non-image asset', function () {
        $photo = AssetFactory::new()->createElement([
            'volumeId' => $this->volume->id,
            'folderId' => $this->folder->id,
            'filename' => 'document.txt',
            'kind' => 'text',
        ]);

        post(action(SaveUserController::class), [
            'userId' => $this->user->id,
            'photo' => [$photo->id],
        ])->assertRedirect()->assertSessionHasErrors('photo');

        expect(User::findOne($this->user->id)->photoId)->toBeNull();
    });

    it('rejects an image the user cannot view', function () {
        $user = UserFactory::new()->createElement();
        ProjectConfig::set('users.photoSubpath', $this->folder->path);

        actingAs($user)->post(action(SaveUserController::class), [
            'userId' => $user->id,
            'photo' => [$this->photo->id],
        ])->assertForbidden();

        expect(User::findOne($user->id)->photoId)->toBeNull();
    });

    it('creates the configured folder and enables uploads to it', function () {
        ProjectConfig::set('users.photoSubpath', 'new-photos/{id}');
        $path = "new-photos/{$this->user->id}";
        $this->disk->assertMissing($path);

        get(cp_url('myaccount'))->assertOk()->assertInertia(function (AssertableInertia $page) {
            $nodes = flattenFormNodes($page->toArray()['props']['form']['nodes']);
            $control = collect($nodes)->firstWhere('control.path', ['photo'])['control'];
            $folder = app(Users::class)->userPhotoFolder($this->user);

            expect($control['props']['sources'])->toBe(["volume:{$this->volume->uid}/folder:{$folder->uid}"])
                ->and($control['props']['criteria'])->toBe(['volumeId' => $this->volume->id, 'folderId' => $folder->id, 'kind' => 'image'])
                ->and($control['props']['canUpload'])->toBeTrue()
                ->and($control['props']['uploadFolderId'])->toBe($folder->id)
                ->and($control['props']['showFolders'])->toBeFalse();
        });

        $this->disk->assertExists($path);
    });

    it('only lists images in the photo folder without child folders', function () {
        AssetFactory::new()->createElement([
            'volumeId' => $this->volume->id,
            'folderId' => $this->folder->id,
            'filename' => 'document.txt',
            'kind' => 'text',
        ]);
        app(Folders::class)->ensureFolderByFullPathAndVolume($this->folder->path.'child', $this->folder->getVolume());

        $response = postJson(action(ElementSelectorModalController::class), [
            'context' => 'modal',
            'elementType' => Asset::class,
            'sources' => ["volume:{$this->volume->uid}/folder:{$this->folder->uid}"],
            'criteria' => ['volumeId' => $this->volume->id, 'folderId' => $this->folder->id, 'kind' => 'image'],
            'showFolders' => false,
        ])->assertOk();

        expect(array_column($response->json('props.data'), 'id'))->toBe([$this->photo->id]);
    });

    it('rejects traversal in the rendered photo subpath', function (string $subpath) {
        ProjectConfig::set('users.photoSubpath', $subpath);

        expect(fn () => app(Users::class)->userPhotoFolder($this->user))
            ->toThrow(InvalidSubpathException::class);
    })->with(['../outside', 'profiles/../outside', 'profiles\\..\\outside']);
});

it('succeeds when user changes their email with correct current password', function () {
    $user = UserFactory::new()->createElement();

    actingAs($user)->post(action(SaveUserController::class), [
        'userId' => $user->id,
        'email' => 'newemail@example.com',
        'password' => 'password',
    ])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    $updatedUser = User::find()->id($user->id)->one();
    expect($updatedUser)->not->toBeNull();
    expect($updatedUser->email)->toBe('newemail@example.com');
});

it('fails when user changes email without providing current password', function () {
    $user = UserFactory::new()->createElement();

    actingAs($user)->post(action(SaveUserController::class), [
        'userId' => $user->id,
        'email' => 'newemail@example.com',
    ])
        ->assertRedirect()
        ->assertSessionHasErrors(['currentPassword']);
});

it('fails when user changes email with incorrect current password', function () {
    $user = UserFactory::new()->createElement();

    actingAs($user)->post(action(SaveUserController::class), [
        'userId' => $user->id,
        'email' => 'newemail@example.com',
        'password' => 'wrongpassword',
    ])
        ->assertRedirect()
        ->assertSessionHasErrors(['currentPassword']);
});

it('succeeds when user sets new password with correct current password', function () {
    $user = UserFactory::new()->createElement();
    $originalPassword = $user->password;

    actingAs($user)->post(action(SaveUserController::class), [
        'userId' => $user->id,
        'newPassword' => 'newSecurePassword123!',
        'password' => 'password',
    ])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    $updatedUser = User::find()->id($user->id)->one();
    expect($updatedUser)->not->toBeNull();
    expect($updatedUser->password)->not->toBe($originalPassword);
});

it('fails when user sets new password without current password', function () {
    $user = UserFactory::new()->createElement();

    actingAs($user)->post(action(SaveUserController::class), [
        'userId' => $user->id,
        'newPassword' => 'newSecurePassword123!',
    ])
        ->assertRedirect()
        ->assertSessionHasErrors(['currentPassword']);
});

it('fails when user sets new password with incorrect current password', function () {
    $user = UserFactory::new()->createElement();

    actingAs($user)->post(action(SaveUserController::class), [
        'userId' => $user->id,
        'newPassword' => 'newSecurePassword123!',
        'password' => 'wrongpassword',
    ])
        ->assertRedirect()
        ->assertSessionHasErrors(['currentPassword']);
});

it('can use currentPassword field instead of password for verification', function () {
    $user = UserFactory::new()->createElement();

    actingAs($user)->post(action(SaveUserController::class), [
        'userId' => $user->id,
        'email' => 'newemail@example.com',
        'currentPassword' => 'password',
    ])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    $updatedUser = User::find()->id($user->id)->one();
    expect($updatedUser)->not->toBeNull();
    expect($updatedUser->email)->toBe('newemail@example.com');
});

it('can update username when editing own profile', function () {
    $user = UserFactory::new()->createElement();

    actingAs($user)->post(action(SaveUserController::class), [
        'userId' => $user->id,
        'username' => 'newusername',
        'password' => 'password',
    ])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    $updatedUser = User::find()->id($user->id)->one();
    expect($updatedUser)->not->toBeNull();
    expect($updatedUser->username)->toBe('newusername');
});

it('properly handles email with leading/trailing spaces when editing', function () {
    $user = UserFactory::new()->createElement();

    actingAs($user)->post(action(SaveUserController::class), [
        'userId' => $user->id,
        'email' => '  trimmed@example.com  ',
        'password' => 'password',
    ])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    $updatedUser = User::find()->id($user->id)->one();
    expect($updatedUser)->not->toBeNull();
    expect($updatedUser->email)->toBe('trimmed@example.com');
});

it('returns proper error message on validation failure', function () {
    $user = UserFactory::new()->createElement();

    $response = actingAs($user)->postJson(action(SaveUserController::class), [
        'userId' => $user->id,
        'email' => 'invalid-email',
    ]);

    $response->assertBadRequest();

    $content = $response->json();
    expect($content)->toHaveKey('errors');
    expect($content['errors'])->toHaveKey('email');
});

it('does not require password when only changing name fields', function () {
    $user = UserFactory::new()->createElement();

    actingAs($user)->post(action(SaveUserController::class), [
        'userId' => $user->id,
        'firstName' => 'John',
        'lastName' => 'Doe',
    ])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    $updatedUser = User::find()->id($user->id)->one();
    expect($updatedUser)->not->toBeNull();
    expect($updatedUser->firstName)->toBe('John');
    expect($updatedUser->lastName)->toBe('Doe');
});

it('returns proper response on success', function () {
    $user = UserFactory::new()->createElement();

    $response = actingAs($user)->postJson(action(SaveUserController::class), [
        'userId' => $user->id,
        'firstName' => 'Updated',
    ]);

    $response->assertOk();

    $content = $response->json();
    expect($content)->toHaveKey('modelId');
    expect($content)->toHaveKey('user');
    expect($content['message'])->toBe('User saved.');
});

it('shares the success flash with the profile screen after a CP save', function () {
    $user = User::findOne();

    actingAs($user)->post(cp_url('actions/users/save-user'), [
        'userId' => $user->id,
        'firstName' => 'Updated',
    ])->assertRedirect(cp_url('myaccount'));

    get(cp_url('myaccount'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('users/Edit')
            ->where('flash.success', t('{type} saved.', ['type' => User::displayName()])));
});

it('shows validation errors on the profile screen after a failed CP save', function () {
    $user = User::findOne();

    actingAs($user)->post(cp_url('actions/users/save-user'), [
        'userId' => $user->id,
        'username' => '',
    ])
        ->assertRedirect()
        ->assertSessionHasErrors(['username']);

    // The error bag must not contain empty attribute entries — Inertia's
    // middleware resolves each entry's first message when rendering the
    // next page, and used to 500 on the empty 'email' entry that the
    // unverifiedEmail error copying left behind.
    $errors = session('errors')->getBag('default')->getMessages();
    expect(array_filter($errors, fn (array $messages) => $messages === []))->toBe([]);

    Pest\Laravel\get(cp_url('myaccount'))->assertOk();
});

it('redirects CP saves without a redirect param back to the profile screen', function () {
    $user = User::findOne();

    actingAs($user)->post(cp_url('actions/users/save-user'), [
        'userId' => $user->id,
        'firstName' => 'Updated',
    ])
        ->assertSessionHasNoErrors()
        ->assertRedirect(cp_url('myaccount'));
});

it('can edit own profile when useEmailAsUsername is true', function () {
    $user = UserFactory::new()->createElement();
    ProjectConfig::set('general.useEmailAsUsername', true);

    actingAs($user)->post(action(SaveUserController::class), [
        'userId' => $user->id,
        'firstName' => 'Updated',
        'password' => 'password',
    ])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    $updatedUser = User::find()->id($user->id)->one();
    expect($updatedUser)->not->toBeNull();
});
