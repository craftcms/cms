<?php

declare(strict_types=1);

use craft\fs\Local;
use CraftCms\Cms\Asset\Models\Volume;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Database\Factories\UserFactory;
use CraftCms\Cms\Edition;
use CraftCms\Cms\Http\Controllers\Users\SaveUserController;
use CraftCms\Cms\Support\Facades\ProjectConfig;
use CraftCms\Cms\User\Elements\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;

use function Pest\Laravel\post;
use function Pest\Laravel\postJson;

beforeEach(function () {
    Edition::set(Edition::Team);

    ProjectConfig::set('users.allowPublicRegistration', true);
});

it('succeeds with valid data', function () {
    $initialCount = User::find()->count();

    $data = [
        'email' => 'newuser@example.com',
        'password' => 'securePassword123!',
        'firstName' => 'John',
        'lastName' => 'Doe',
    ];

    post(action(SaveUserController::class), $data)
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    expect(User::find()->count())->toBe($initialCount + 1);

    $user = User::find()->email($data['email'])->one();
    expect($user)->not->toBeNull();
    expect($user->email)->toBe($data['email']);
    expect($user->firstName)->toBe($data['firstName']);
    expect($user->lastName)->toBe($data['lastName']);
});

it('succeeds with minimal valid data', function () {
    $initialCount = User::find()->count();

    $data = [
        'email' => 'newuser2@example.com',
        'password' => 'securePassword123!',
    ];

    post(action(SaveUserController::class), $data)
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    expect(User::find()->count())->toBe($initialCount + 1);

    $user = User::find()->email($data['email'])->one();
    expect($user)->not->toBeNull();
    expect($user->email)->toBe($data['email']);
});

it('fails when public registration is disabled', function () {
    ProjectConfig::set('users.allowPublicRegistration', false);

    post(action(SaveUserController::class), [
        'email' => 'test@example.com',
        'password' => 'password123',
    ])->assertForbidden();
});

it('fails with missing email', function () {
    post(action(SaveUserController::class), [
        'password' => 'password123',
    ])->assertSessionHasErrors(['email']);
});

it('fails with empty email', function () {
    post(action(SaveUserController::class), [
        'email' => '',
        'password' => 'securePassword123!',
    ])->assertSessionHasErrors(['email']);
});

it('fails with invalid email format', function () {
    post(action(SaveUserController::class), [
        'email' => 'not-an-email',
        'password' => 'securePassword123!',
    ])->assertSessionHasErrors(['email']);
});

it('fails with missing password field', function () {
    post(action(SaveUserController::class), [
        'email' => 'newuser@example.com',
    ])->assertSessionHasErrors(['password']);
});

it('rejects duplicate email from active user', function () {
    $existingUser = UserFactory::new()->active()->createElement([
        'email' => 'existing@example.com',
    ]);

    post(action(SaveUserController::class), [
        'email' => $existingUser->email,
        'password' => 'password123',
    ])->assertSessionHasErrors(['email']);
});

it('reactivates existing inactive user with same email', function () {
    $inactiveUser = UserFactory::new()->createElement([
        'email' => 'inactive@example.com',
        'active' => false,
        'pending' => false,
    ]);

    post(action(SaveUserController::class), [
        'email' => $inactiveUser->email,
        'password' => 'newPassword123!',
    ])->assertRedirect()
        ->assertSessionHasNoErrors();

    $user = User::find()->email($inactiveUser->email)->one();
    expect($user)->not->toBeNull();
    expect($user->active)->toBeTrue();
});

it('tracks affiliated site on registration', function () {
    post(action(SaveUserController::class), [
        'email' => 'siteuser@example.com',
        'password' => 'password123',
    ])->assertRedirect()
        ->assertSessionHasNoErrors();

    $user = User::find()->email('siteuser@example.com')->one();
    expect($user)->not->toBeNull();
    expect($user->affiliatedSiteId)->not->toBeNull();
});

it('creates user as active when deactivateByDefault is false', function () {
    ProjectConfig::set('users.deactivateByDefault', false);

    post(action(SaveUserController::class), [
        'email' => 'activeuser@example.com',
        'password' => 'password123',
    ])->assertRedirect()
        ->assertSessionHasNoErrors();

    $user = User::find()->email('activeuser@example.com')->one();
    expect($user)->not->toBeNull();
    expect($user->active)->toBeTrue();
    expect($user->pending)->toBeFalse();
});

it('creates user as inactive when deactivateByDefault is true', function () {
    ProjectConfig::set('users.deactivateByDefault', true);

    post(action(SaveUserController::class), [
        'email' => 'pendinguser@example.com',
        'password' => 'password123',
    ])->assertRedirect()
        ->assertSessionHasNoErrors();

    $user = User::find()->email('pendinguser@example.com')->one();
    expect($user)->not->toBeNull();
    expect($user->active)->toBeFalse();
    expect($user->pending)->toBeFalse();
});

it('requires Craft Team edition for public registration', function () {
    Edition::set(Edition::Solo);

    post(action(SaveUserController::class), [
        'email' => 'solo@example.com',
        'password' => 'password123',
    ])->assertInternalServerError();
});

it('validates unique email for active and pending users only', function () {
    $activeUser = UserFactory::new()->active()->createElement([
        'email' => 'unique@example.com',
    ]);
    $pendingUser = UserFactory::new()->pending()->createElement([
        'email' => 'pending@example.com',
    ]);

    post(action(SaveUserController::class), [
        'email' => $activeUser->email,
        'password' => 'password123',
    ])->assertSessionHasErrors(['email']);

    post(action(SaveUserController::class), [
        'email' => $pendingUser->email,
        'password' => 'password123',
    ])->assertSessionHasErrors(['email']);
});

it('allows registration with email that belongs to inactive user', function () {
    $inactiveUser = UserFactory::new()->createElement([
        'email' => 'inactive2@example.com',
        'active' => false,
        'pending' => false,
    ]);

    $user = User::find()->email($inactiveUser->email)->one();
    expect($user)->not->toBeNull();
    expect($user->active)->toBeFalse();
    expect($user->pending)->toBeFalse();

    post(action(SaveUserController::class), [
        'email' => $inactiveUser->email,
        'password' => 'password123',
    ])->assertRedirect()
        ->assertSessionHasNoErrors();

    $updatedUser = User::find()->email($inactiveUser->email)->one();
    expect($updatedUser)->not->toBeNull();
    expect($updatedUser->active)->toBeTrue();
});

it('returns proper response on success', function () {
    $response = postJson(action(SaveUserController::class), [
        'email' => 'json@example.com',
        'password' => 'password123',
    ]);

    $response->assertOk();

    $content = $response->json();
    expect($content)->toHaveKey('modelId');
    expect($content)->toHaveKey('user');
    expect($content['message'])->toBe('User saved.');
});

it('sets username from email when useEmailAsUsername is true', function () {
    ProjectConfig::set('general.useEmailAsUsername', true);

    post(action(SaveUserController::class), [
        'email' => 'username@example.com',
        'password' => 'password123',
    ])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    $user = User::find()->email('username@example.com')->one();
    expect($user)->not->toBeNull();
    expect($user->username)->toBe('username@example.com');
});

it('properly handles email with leading/trailing spaces', function () {
    $initialCount = User::find()->count();

    post(action(SaveUserController::class), [
        'email' => '  spaced@example.com  ',
        'password' => 'password123',
    ])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    expect(User::find()->count())->toBe($initialCount + 1);

    $user = User::find()->email('spaced@example.com')->one();
    expect($user)->not->toBeNull();
    expect($user->email)->toBe('spaced@example.com');
});

it('succeeds without password when deferPublicRegistrationPassword is true', function () {
    Cms::config()->deferPublicRegistrationPassword = true;
    ProjectConfig::set('users.deactivateByDefault', true);

    $initialCount = User::find()->count();

    post(action(SaveUserController::class), [
        'email' => 'deferred@example.com',
    ])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    expect(User::find()->count())->toBe($initialCount + 1);

    $user = User::find()->email('deferred@example.com')->one();
    expect($user)->not->toBeNull();
    expect($user->password)->toBeNull();
});

it('logs user in after registration when autoLoginAfterAccountActivation is true', function () {
    Cms::config()->autoLoginAfterAccountActivation = true;

    post(action(SaveUserController::class), [
        'email' => 'autologin@example.com',
        'password' => 'password123',
    ])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    expect(Auth::check())->toBeTrue();
    expect(Auth::user()->email)->toBe('autologin@example.com');
});

it('can upload a photo', function () {
    // @TODO: Bulk ops cause issues
    if (\Illuminate\Support\Facades\DB::getDriverName() === 'mysql') {
        $this->markTestSkipped('Bulk ops cause issues with MySQL');
    }

    ProjectConfig::set('fs.test', [
        'hasUrls' => true,
        'name' => 'Test',
        'settings' => [
            'path' => public_path('test'),
        ],
        'type' => Local::class,
        'url' => '/test',
    ]);

    $volume = Volume::factory()->create([
        'fs' => 'test',
    ]);

    ProjectConfig::set('users.photoVolumeUid', $volume->uid);

    $this->withoutExceptionHandling();

    $data = [
        'email' => 'newuser@example.com',
        'password' => 'securePassword123!',
        'firstName' => 'John',
        'lastName' => 'Doe',
        'photo' => UploadedFile::fake()->image('avatar.jpg'),
    ];

    post(action(SaveUserController::class), $data)
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    $user = User::find()->email('newuser@example.com')->one();

    expect($user->getPhoto())->not->toBeNull();
});

it('can upload a photo with different image formats', function () {
    // @TODO: Bulk ops cause issues
    if (\Illuminate\Support\Facades\DB::getDriverName() === 'mysql') {
        $this->markTestSkipped('Bulk ops cause issues with MySQL');
    }

    ProjectConfig::set('fs.test', [
        'hasUrls' => true,
        'name' => 'Test',
        'settings' => ['path' => public_path('test')],
        'type' => Local::class,
        'url' => '/test',
    ]);

    $volume = Volume::factory()->create(['fs' => 'test']);
    ProjectConfig::set('users.photoVolumeUid', $volume->uid);

    $this->withoutExceptionHandling();

    post(action(SaveUserController::class), [
        'email' => 'pnguser@example.com',
        'password' => 'securePassword123!',
        'photo' => UploadedFile::fake()->image('avatar.png'),
    ])->assertRedirect()->assertSessionHasNoErrors();

    $user = User::find()->email('pnguser@example.com')->one();
    expect($user->getPhoto())->not->toBeNull();
});

it('can upload a photo with base64 encoded data', function () {
    // @TODO: Bulk ops cause issues
    if (\Illuminate\Support\Facades\DB::getDriverName() === 'mysql') {
        $this->markTestSkipped('Bulk ops cause issues with MySQL');
    }

    ProjectConfig::set('fs.test', [
        'hasUrls' => true,
        'name' => 'Test',
        'settings' => ['path' => public_path('test')],
        'type' => Local::class,
        'url' => '/test',
    ]);

    $volume = Volume::factory()->create(['fs' => 'test']);
    ProjectConfig::set('users.photoVolumeUid', $volume->uid);

    $realImage = base64_encode(UploadedFile::fake()->image('avatar.jpg')->getContent());

    post(action(SaveUserController::class), [
        'email' => 'base64user@example.com',
        'password' => 'securePassword123!',
        'photo' => [
            'data' => 'data:image/jpeg;base64,'.$realImage,
            'filename' => 'avatar.jpg',
        ],
    ])->assertRedirect()->assertSessionHasNoErrors();

    $user = User::find()->email('base64user@example.com')->one();
    expect($user->getPhoto())->not->toBeNull();
});

it('assigns default user groups on public registration', function () {
    ProjectConfig::set('users.defaultUserGroups', ['testgroup']);

    post(action(SaveUserController::class), [
        'email' => 'groupuser@example.com',
        'password' => 'password123',
    ])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    $user = User::find()->email('groupuser@example.com')->one();
    expect($user->getGroups())->toHaveCount(1);
});

it('handles base64 photo without filename extension', function () {
    // @TODO: Bulk ops cause issues
    if (\Illuminate\Support\Facades\DB::getDriverName() === 'mysql') {
        $this->markTestSkipped('Bulk ops cause issues with MySQL');
    }

    ProjectConfig::set('fs.test', [
        'hasUrls' => true,
        'name' => 'Test',
        'settings' => ['path' => public_path('test')],
        'type' => Local::class,
        'url' => '/test',
    ]);

    $volume = Volume::factory()->create(['fs' => 'test']);
    ProjectConfig::set('users.photoVolumeUid', $volume->uid);

    $realImage = base64_encode(UploadedFile::fake()->image('avatar.jpg')->getContent());

    post(action(SaveUserController::class), [
        'email' => 'noext@example.com',
        'password' => 'securePassword123!',
        'photo' => [
            'data' => 'data:image/jpeg;base64,'.$realImage,
        ],
    ])->assertRedirect()->assertSessionHasNoErrors();

    $user = User::find()->email('noext@example.com')->one();
    expect($user)->not->toBeNull();
});
