<?php

declare(strict_types=1);

use CraftCms\Cms\Asset\Models\Volume;
use CraftCms\Cms\Database\Factories\UserFactory;
use CraftCms\Cms\Filesystem\Filesystems\Local;
use CraftCms\Cms\Http\Controllers\Users\SaveUserController;
use CraftCms\Cms\Support\Facades\ProjectConfig;
use CraftCms\Cms\User\Elements\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

use function Pest\Laravel\actingAs;

it('succeeds when admin edits another user', function () {
    $admin = UserFactory::new()->admin()->createElement();
    $targetUser = UserFactory::new()->createElement();

    actingAs($admin)->post(action(SaveUserController::class), [
        'userId' => $targetUser->id,
        'firstName' => 'Updated',
        'lastName' => 'User',
    ])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    $updatedUser = User::find()->id($targetUser->id)->one();
    expect($updatedUser)->not->toBeNull();
    expect($updatedUser->firstName)->toBe('Updated');
    expect($updatedUser->lastName)->toBe('User');
});

it('clears existing user name fields', function () {
    $admin = UserFactory::new()->admin()->createElement();
    $targetUser = UserFactory::new()->createElement([
        'firstName' => 'Existing',
        'lastName' => 'User',
    ]);

    actingAs($admin)->post(action(SaveUserController::class), [
        'userId' => $targetUser->id,
        'firstName' => '',
        'lastName' => '',
    ])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    $updatedUser = User::find()->id($targetUser->id)->one();
    expect($updatedUser)->not->toBeNull();
    expect($updatedUser->firstName)->toBeNull();
    expect($updatedUser->lastName)->toBeNull();
});

it('fails when user without permission edits another user', function () {
    $regularUser = UserFactory::new()->createElement();
    $targetUser = UserFactory::new()->createElement();

    actingAs($regularUser)->post(action(SaveUserController::class), [
        'userId' => $targetUser->id,
        'firstName' => 'Updated',
    ])->assertForbidden();
});

it('requires current password when admin changes another users email', function () {
    $admin = UserFactory::new()->admin()->createElement();
    $targetUser = UserFactory::new()->createElement();

    actingAs($admin)->post(action(SaveUserController::class), [
        'userId' => $targetUser->id,
        'email' => 'newemail@example.com',
        'password' => 'password',
    ])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    $updatedUser = User::find()->id($targetUser->id)->one();
    expect($updatedUser)->not->toBeNull();
    expect($updatedUser->email)->toBe('newemail@example.com');
});

it('does not require current password when admin changes another users password', function () {
    $admin = UserFactory::new()->admin()->createElement();
    $targetUser = UserFactory::new()->createElement();
    $originalPassword = $targetUser->password;

    actingAs($admin)->post(action(SaveUserController::class), [
        'userId' => $targetUser->id,
        'newPassword' => 'newPassword123!',
    ])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    $updatedUser = User::find()->id($targetUser->id)->one();
    expect($updatedUser)->not->toBeNull();
    expect($updatedUser->password)->not->toBe($originalPassword);
});

it('can edit another user with various fields', function () {
    $admin = UserFactory::new()->admin()->createElement();
    $targetUser = UserFactory::new()->createElement([
        'passwordResetRequired' => false,
    ]);

    actingAs($admin)->post(action(SaveUserController::class), [
        'userId' => $targetUser->id,
        'firstName' => 'Updated',
        'lastName' => 'User',
    ])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    $updatedUser = User::find()->id($targetUser->id)->one();
    expect($updatedUser)->not->toBeNull();
    expect($updatedUser->firstName)->toBe('Updated');
    expect($updatedUser->lastName)->toBe('User');
});

it('can change username of another user', function () {
    $admin = UserFactory::new()->admin()->createElement();
    $targetUser = UserFactory::new()->createElement();

    actingAs($admin)->post(action(SaveUserController::class), [
        'userId' => $targetUser->id,
        'username' => 'newusername',
    ])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    $updatedUser = User::find()->id($targetUser->id)->one();
    expect($updatedUser)->not->toBeNull();
    expect($updatedUser->username)->toBe('newusername');
});

it('rejects duplicate email when editing another user', function () {
    $admin = UserFactory::new()->admin()->createElement();
    $targetUser = UserFactory::new()->createElement();
    $existingUser = UserFactory::new()->active()->createElement([
        'email' => 'existing@example.com',
    ]);

    actingAs($admin)->post(action(SaveUserController::class), [
        'userId' => $targetUser->id,
        'email' => $existingUser->email,
    ])->assertSessionHasErrors(['email']);
});

it('properly handles email with leading/trailing spaces when editing another user', function () {
    $admin = UserFactory::new()->admin()->createElement();
    $targetUser = UserFactory::new()->createElement();

    actingAs($admin)->post(action(SaveUserController::class), [
        'userId' => $targetUser->id,
        'email' => '  trimmed@example.com  ',
        'password' => 'password',
    ])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    $updatedUser = User::find()->id($targetUser->id)->one();
    expect($updatedUser)->not->toBeNull();
    expect($updatedUser->email)->toBe('trimmed@example.com');
});

it('can upload a photo for another user', function () {
    // @TODO: Bulk ops cause issues
    if (DB::isMysql()) {
        $this->markTestSkipped('Bulk ops cause issues with MySQL');
    }

    $admin = UserFactory::new()->admin()->createElement();
    $targetUser = UserFactory::new()->createElement();

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

    actingAs($admin)->post(action(SaveUserController::class), [
        'userId' => $targetUser->id,
        'photo' => UploadedFile::fake()->image('avatar.jpg'),
    ])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    $updatedUser = User::find()->id($targetUser->id)->one();
    expect($updatedUser)->not->toBeNull();
    expect($updatedUser->getPhoto())->not->toBeNull();
});

it('returns proper error message on validation failure when editing another user', function () {
    $admin = UserFactory::new()->admin()->createElement();
    $targetUser = UserFactory::new()->createElement();

    $response = actingAs($admin)->postJson(action(SaveUserController::class), [
        'userId' => $targetUser->id,
        'email' => 'invalid-email',
    ]);

    $response->assertBadRequest();

    $content = $response->json();
    expect($content)->toHaveKey('errors');
    expect($content['errors'])->toHaveKey('email');
});

it('returns proper response on success when editing another user', function () {
    $admin = UserFactory::new()->admin()->createElement();
    $targetUser = UserFactory::new()->createElement();

    $response = actingAs($admin)->postJson(action(SaveUserController::class), [
        'userId' => $targetUser->id,
        'firstName' => 'Updated',
    ]);

    $response->assertOk();

    $content = $response->json();
    expect($content)->toHaveKey('modelId');
    expect($content)->toHaveKey('user');
    expect($content['message'])->toBe('User saved.');
});

it('does not set affiliated site when editing another user', function () {
    $admin = UserFactory::new()->admin()->createElement();
    $targetUser = UserFactory::new()->createElement();

    actingAs($admin)->post(action(SaveUserController::class), [
        'userId' => $targetUser->id,
        'firstName' => 'Updated',
    ])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    $updatedUser = User::find()->id($targetUser->id)->one();
    expect($updatedUser)->not->toBeNull();
    expect($updatedUser->affiliatedSiteId)->toBeNull();
});
