<?php

use CraftCms\Cms\Edition;
use CraftCms\Cms\Http\Controllers\Users\ActivateController;
use CraftCms\Cms\Support\Facades\UserPermissions;
use CraftCms\Cms\User\Elements\User as UserElement;
use CraftCms\Cms\User\Models\User;
use Illuminate\Support\Facades\Notification;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\postJson;

it('requires login', function () {
    postJson(action([ActivateController::class, 'activate']))->assertUnauthorized();
    postJson(action([ActivateController::class, 'deactivate']))->assertUnauthorized();
});

test('activate requires administrateUsers permission and activates users', function () {
    Edition::set(Edition::Pro);

    $user = User::factory()->create();
    $user2 = User::factory()->create([
        'active' => false,
    ]);

    actingAs($user->asElement());

    postJson(action([ActivateController::class, 'activate']), [
        'userId' => $user2->id,
    ])->assertForbidden();

    UserPermissions::saveUserPermissions($user->id, [
        'viewUsers',
        'editUsers',
        'administrateUsers',
    ]);

    postJson(action([ActivateController::class, 'activate']), [
        'userId' => $user2->id,
    ])->assertOk();

    expect($user2->fresh()->active)->toBeTrue();
});

test('deactivate requires administrateUsers permission and deactivates users', function () {
    Edition::set(Edition::Pro);

    $user = User::factory()->create();
    $user2 = User::factory()->create([
        'active' => true,
    ]);

    actingAs($user->asElement());

    postJson(action([ActivateController::class, 'deactivate']), [
        'userId' => $user2->id,
    ])->assertForbidden();

    UserPermissions::saveUserPermissions($user->id, [
        'viewUsers',
        'editUsers',
        'administrateUsers',
    ]);

    postJson(action([ActivateController::class, 'deactivate']), [
        'userId' => $user2->id,
    ])->assertOk();

    expect($user2->fresh()->active)->toBeFalse();
});

it('requires login for sendActivationEmail', function () {
    postJson(action([ActivateController::class, 'sendActivationEmail']))->assertUnauthorized();
});

test('sendActivationEmail requires moderateUsers for pending users', function () {
    Notification::fake();

    Edition::set(Edition::Pro);

    $user = User::factory()->create();
    $pendingUser = User::factory()->create([
        'active' => false,
        'pending' => true,
    ]);

    actingAs($user->asElement());

    postJson(action([ActivateController::class, 'sendActivationEmail']), [
        'userId' => $pendingUser->id,
    ])->assertForbidden();

    UserPermissions::saveUserPermissions($user->id, [
        'viewUsers',
        'editUsers',
        'moderateUsers',
    ]);

    postJson(action([ActivateController::class, 'sendActivationEmail']), [
        'userId' => $pendingUser->id,
    ])->assertOk();
});

test('sendActivationEmail requires moderateUsers for inactive (non-pending) users', function () {
    Notification::fake();

    Edition::set(Edition::Pro);

    $user = User::factory()->create();
    $inactiveUser = User::factory()->create([
        'active' => false,
        'pending' => false,
    ]);

    actingAs($user->asElement());

    postJson(action([ActivateController::class, 'sendActivationEmail']), [
        'userId' => $inactiveUser->id,
    ])->assertForbidden();

    UserPermissions::saveUserPermissions($user->id, [
        'viewUsers',
        'editUsers',
        'moderateUsers',
    ]);

    postJson(action([ActivateController::class, 'sendActivationEmail']), [
        'userId' => $inactiveUser->id,
    ])->assertOk();
});

it('returns 400 for non-existent user on sendActivationEmail', function () {
    Edition::set(Edition::Pro);

    actingAs(UserElement::findOne());

    postJson(action([ActivateController::class, 'sendActivationEmail']), ['userId' => 999999])
        ->assertStatus(400)
        ->assertJsonPath('message', 'User not found');
});

it('validates userId is required for sendActivationEmail', function () {
    Edition::set(Edition::Pro);

    actingAs(UserElement::findOne());

    postJson(action([ActivateController::class, 'sendActivationEmail']))
        ->assertJsonValidationErrorFor('userId');
});

it('returns 400 for active users on sendActivationEmail', function () {
    Edition::set(Edition::Pro);

    $activeUser = User::factory()->create([
        'active' => true,
    ]);

    actingAs(UserElement::findOne());

    postJson(action([ActivateController::class, 'sendActivationEmail']), [
        'userId' => $activeUser->id,
    ])->assertStatus(400)
        ->assertJsonPath('message', 'Activation emails can only be sent to inactive or pending users');
});
