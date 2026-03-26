<?php

declare(strict_types=1);

use CraftCms\Cms\Asset\Models\Volume;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Database\Factories\UserFactory;
use CraftCms\Cms\Edition;
use CraftCms\Cms\Filesystem\Filesystems\Local;
use CraftCms\Cms\Http\Controllers\Users\SaveUserController;
use CraftCms\Cms\Support\Facades\ProjectConfig;
use CraftCms\Cms\User\Elements\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\post;
use function Pest\Laravel\postJson;

beforeEach(function () {
    $this->admin = UserFactory::new()->admin()->createElement();

    actingAs($this->admin);

    Edition::set(Edition::Team);
});

it('succeeds when authenticated admin registers a new user', function () {
    $initialCount = User::find()->count();

    $data = [
        'email' => 'newuser@example.com',
        'username' => 'newuser',
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
    expect($user->username)->toBe($data['username']);
    expect($user->firstName)->toBe($data['firstName']);
    expect($user->lastName)->toBe($data['lastName']);
});

it('succeeds with minimal data when authenticated', function () {
    $initialCount = User::find()->count();

    $data = [
        'email' => 'minimal@example.com',
    ];

    post(action(SaveUserController::class), $data)
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    expect(User::find()->count())->toBe($initialCount + 1);

    $user = User::find()->email($data['email'])->one();
    expect($user)->not->toBeNull();
    expect($user->email)->toBe($data['email']);
});

it('requires registerUsers permission for non-admin users', function () {
    $user = UserFactory::new()->createElement();

    actingAs($user)->post(action(SaveUserController::class), [
        'email' => 'noperm@example.com',
    ])->assertForbidden();
});

it('fails with missing email', function () {
    post(action(SaveUserController::class), [
        'username' => 'noemail',
    ])->assertSessionHasErrors(['email']);
});

it('rejects duplicate email from active user', function () {
    $existingUser = UserFactory::new()->active()->createElement([
        'email' => 'existing@example.com',
    ]);

    post(action(SaveUserController::class), [
        'email' => $existingUser->email,
    ])->assertSessionHasErrors(['email']);
});

it('does not reactivate inactive user with same email', function () {
    $inactiveUser = UserFactory::new()->createElement([
        'email' => 'inactive@example.com',
        'active' => false,
        'pending' => false,
    ]);

    post(action(SaveUserController::class), [
        'email' => $inactiveUser->email,
    ])->assertRedirect()
        ->assertSessionHasNoErrors();

    $newUser = User::find()->email($inactiveUser->email)->all();
    expect(count($newUser))->toBe(2);
});

it('does not set affiliated site for authenticated registration', function () {
    post(action(SaveUserController::class), [
        'email' => 'nosite@example.com',
    ])->assertRedirect()
        ->assertSessionHasNoErrors();

    $user = User::find()->email('nosite@example.com')->one();
    expect($user)->not->toBeNull();
    expect($user->affiliatedSiteId)->toBeNull();
});

it('creates user as active when deactivateByDefault is false', function () {
    ProjectConfig::set('users.deactivateByDefault', false);

    post(action(SaveUserController::class), [
        'email' => 'activeuser@example.com',
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
        'email' => 'inactiveuser@example.com',
    ])->assertRedirect()
        ->assertSessionHasNoErrors();

    $user = User::find()->email('inactiveuser@example.com')->one();
    expect($user)->not->toBeNull();
    expect($user->active)->toBeFalse();
    expect($user->pending)->toBeFalse();
});

it('fails when Craft Team edition is required but Solo is set', function () {
    Edition::set(Edition::Solo);

    post(action(SaveUserController::class), [
        'email' => 'solo@example.com',
    ])->assertInternalServerError();
});

it('returns proper response on success', function () {
    $response = postJson(action(SaveUserController::class), [
        'email' => 'json@example.com',
    ]);

    $response->assertOk();

    $content = $response->json();
    expect($content)->toHaveKey('modelId');
    expect($content)->toHaveKey('user');
    expect($content['message'])->toBe('User saved.');
});

it('properly handles email with leading/trailing spaces', function () {
    $initialCount = User::find()->count();

    post(action(SaveUserController::class), [
        'email' => '  spaced@example.com  ',
    ])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    expect(User::find()->count())->toBe($initialCount + 1);

    $user = User::find()->email('spaced@example.com')->one();
    expect($user)->not->toBeNull();
    expect($user->email)->toBe('spaced@example.com');
});

it('can set custom username', function () {
    post(action(SaveUserController::class), [
        'email' => 'custom@example.com',
        'username' => 'customuser',
    ])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    $user = User::find()->email('custom@example.com')->one();
    expect($user)->not->toBeNull();
    expect($user->username)->toBe('customuser');
});

it('does not auto-login user after registration', function () {
    Cms::config()->autoLoginAfterAccountActivation = true;

    post(action(SaveUserController::class), [
        'email' => 'noautologin@example.com',
    ])->assertRedirect()
        ->assertSessionHasNoErrors();

    expect(Auth::id())->toBe($this->admin->id);
});

it('can upload a photo for new user', function () {
    // @TODO: Bulk ops cause issues
    if (DB::isMysql()) {
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

    $data = [
        'email' => 'photouser@example.com',
        'username' => 'photouser',
        'firstName' => 'Photo',
        'lastName' => 'User',
        'photo' => UploadedFile::fake()->image('avatar.jpg'),
    ];

    post(action(SaveUserController::class), $data)
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    $user = User::find()->email('photouser@example.com')->one();

    expect($user->getPhoto())->not->toBeNull();
});

it('returns proper error message on validation failure', function () {
    $response = postJson(action(SaveUserController::class), [
        'email' => 'invalid-email',
    ]);

    $response->assertStatus(400);

    $content = $response->json();
    expect($content)->toHaveKey('errors');
    expect($content['errors'])->toHaveKey('email');
});
