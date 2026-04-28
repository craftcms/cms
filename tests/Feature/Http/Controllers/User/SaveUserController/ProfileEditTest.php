<?php

declare(strict_types=1);

use CraftCms\Cms\Database\Factories\UserFactory;
use CraftCms\Cms\Edition;
use CraftCms\Cms\Http\Controllers\Users\SaveUserController;
use CraftCms\Cms\Support\Facades\ProjectConfig;
use CraftCms\Cms\User\Elements\User;

use function Pest\Laravel\actingAs;

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

    $response->assertStatus(400);

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
