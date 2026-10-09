<?php

use CraftCms\Cms\Edition;
use CraftCms\Cms\Http\Controllers\Users\ActivateController;
use CraftCms\Cms\Support\Facades\UserPermissions;
use CraftCms\Cms\User\Elements\User as UserElement;
use CraftCms\Cms\User\Models\User;
use CraftCms\Cms\User\Notifications\ActivationNotification;
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
    UserPermissions::saveUserPermissions($user->id, ['accessCp']);

    $user2 = User::factory()->create([
        'active' => false,
    ]);

    actingAs($user->asElement());

    postJson(action([ActivateController::class, 'activate']), [
        'userId' => $user2->id,
    ])->assertForbidden();

    UserPermissions::saveUserPermissions($user->id, [
        'accessCp',
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

    UserPermissions::saveUserPermissions($user->id, [
        'accessCp',
    ]);

    postJson(action([ActivateController::class, 'deactivate']), [
        'userId' => $user2->id,
    ])->assertForbidden();

    UserPermissions::saveUserPermissions($user->id, [
        'accessCp',
        'viewUsers',
        'editUsers',
        'administrateUsers',
    ]);

    postJson(action([ActivateController::class, 'deactivate']), [
        'userId' => $user2->id,
    ])->assertOk();

    expect($user2->fresh()->active)->toBeFalse();
});

test('deactivate prevents non-admin administrators from deactivating admins', function () {
    Edition::set(Edition::Pro);

    $user = User::factory()->create();
    $admin = User::factory()->admin()->create([
        'active' => true,
    ]);

    UserPermissions::saveUserPermissions($user->id, [
        'accessCp',
        'viewUsers',
        'editUsers',
        'administrateUsers',
    ]);

    actingAs($user->asElement());

    postJson(action([ActivateController::class, 'deactivate']), [
        'userId' => $admin->id,
    ])->assertForbidden();

    expect($admin->fresh()->active)->toBeTrue();
});

it('requires login for sendActivationEmail', function () {
    postJson(action([ActivateController::class, 'sendActivationEmail']))->assertUnauthorized();
});

test('sendActivationEmail allows editUsers to email pending users', function () {
    Notification::fake();

    Edition::set(Edition::Pro);

    $user = User::factory()->create();
    UserPermissions::saveUserPermissions($user->id, [
        'accessCp',
    ]);

    $pendingUser = User::factory()->create([
        'active' => false,
        'pending' => true,
    ]);

    actingAs($user->asElement());

    postJson(action([ActivateController::class, 'sendActivationEmail']), [
        'userId' => $pendingUser->id,
    ])->assertForbidden();

    UserPermissions::saveUserPermissions($user->id, [
        'accessCp',
        'viewUsers',
        'editUsers',
    ]);

    postJson(action([ActivateController::class, 'sendActivationEmail']), [
        'userId' => $pendingUser->id,
    ])->assertOk();

    Notification::assertSentTimes(ActivationNotification::class, 1);
});

test('sendActivationEmail requires moderateUsers for inactive (non-pending) users', function () {
    Notification::fake();

    Edition::set(Edition::Pro);

    $user = User::factory()->create();
    UserPermissions::saveUserPermissions($user->id, [
        'accessCp',
    ]);
    $inactiveUser = User::factory()->create([
        'active' => false,
        'pending' => false,
    ]);

    actingAs($user->asElement());

    postJson(action([ActivateController::class, 'sendActivationEmail']), [
        'userId' => $inactiveUser->id,
    ])->assertForbidden();

    UserPermissions::saveUserPermissions($user->id, [
        'accessCp',
        'viewUsers',
        'editUsers',
    ]);

    postJson(action([ActivateController::class, 'sendActivationEmail']), [
        'userId' => $inactiveUser->id,
    ])->assertForbidden();

    UserPermissions::saveUserPermissions($user->id, [
        'accessCp',
        'viewUsers',
        'editUsers',
        'moderateUsers',
    ]);

    postJson(action([ActivateController::class, 'sendActivationEmail']), [
        'userId' => $inactiveUser->id,
    ])->assertOk();

    expect($inactiveUser->fresh()->pending)->toBeTrue();
});

it('returns 400 for non-existent user on sendActivationEmail', function () {
    Edition::set(Edition::Pro);

    actingAs(UserElement::findOne());

    postJson(action([ActivateController::class, 'sendActivationEmail']), ['userId' => 999999])->assertBadRequest()
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

    actingAs(User::factory()->admin()->createElement());

    postJson(action([ActivateController::class, 'sendActivationEmail']), [
        'userId' => $activeUser->id,
    ])->assertBadRequest()
        ->assertJsonPath('message', 'Activation emails can only be sent to inactive or pending users');
});
